<?php

namespace App\Services;

use PDO;
use PDOException;
use Exception;
use App\Utils\Logger;

/**
 * Servicio para cumplimiento normativo ARCA 2025
 * Resolución General N° 5616
 * 
 * Maneja validaciones, migraciones y compliance para los cambios
 * normativos que implementará ARCA durante 2025.
 */
class Arca2025ComplianceService
{
    private PDO $db;
    private Logger $logger;
    private array $config;

    // Códigos de condiciones IVA oficiales ARCA
    const CONDICIONES_IVA = [
        1 => 'IVA Responsable Inscripto',
        2 => 'IVA Responsable no Inscripto', 
        3 => 'IVA no Responsable',
        4 => 'IVA Sujeto Exento',
        5 => 'Consumidor Final',
        6 => 'Responsable Monotributo',
        7 => 'Sujeto no Categorizado',
        8 => 'Proveedor del Exterior',
        9 => 'Cliente del Exterior',
        10 => 'IVA Liberado – Ley Nº 19.640',
        11 => 'IVA Responsable Inscripto – Agente de Percepción',
        12 => 'Pequeño Contribuyente Eventual',
        13 => 'Monotributista Social',
        14 => 'Pequeño Contribuyente Eventual Social'
    ];

    public function __construct(PDO $database, Logger $logger)
    {
        $this->db = $database;
        $this->logger = $logger;
        $this->loadConfig();
    }

    /**
     * Cargar configuración ARCA 2025
     */
    private function loadConfig(): void
    {
        $this->config = include __DIR__ . '/../../config/arca_2025.php';
    }

    /**
     * Verificar si ARCA 2025 está habilitado para un cliente
     */
    public function isEnabledForClient(string $clientId): bool
    {
        try {
            $stmt = $this->db->prepare("
                SELECT migration_status, validacion_iva_enabled 
                FROM client_arca_2025_config 
                WHERE client_id = ?
            ");
            $stmt->execute([$clientId]);
            $config = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$config) {
                return false;
            }

            return $config['migration_status'] !== 'preparacion' || 
                   $config['validacion_iva_enabled'] === 1;
        } catch (PDOException $e) {
            $this->logger->error("Error checking ARCA 2025 status for client {$clientId}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Validar condición IVA del receptor
     */
    public function validateReceptorCondicionIva(string $cuit, ?int $condicionCodigo = null): array
    {
        $result = [
            'valid' => false,
            'codigo' => null,
            'descripcion' => null,
            'source' => 'cache',
            'errors' => []
        ];

        try {
            // 1. Verificar en caché primero
            $cached = $this->getCachedPadronData($cuit);
            if ($cached && !$this->isCacheExpired($cached)) {
                $result['valid'] = true;
                $result['codigo'] = $cached['condicion_iva_codigo'];
                $result['descripcion'] = $cached['condicion_iva_descripcion'];
                $result['source'] = 'cache';
                return $result;
            }

            // 2. Si hay código proporcionado, validarlo
            if ($condicionCodigo !== null) {
                if (!isset(self::CONDICIONES_IVA[$condicionCodigo])) {
                    $result['errors'][] = "Código de condición IVA inválido: {$condicionCodigo}";
                    return $result;
                }

                $result['valid'] = true;
                $result['codigo'] = $condicionCodigo;
                $result['descripcion'] = self::CONDICIONES_IVA[$condicionCodigo];
                $result['source'] = 'provided';

                // Cachear el resultado
                $this->cachePadronData($cuit, $condicionCodigo, $result['descripcion']);
                return $result;
            }

            // 3. Consultar padrón ARCA si está habilitado
            if ($this->config['validacion_padron']['enabled']) {
                $padronResult = $this->queryPadronArca($cuit);
                if ($padronResult['success']) {
                    $result['valid'] = true;
                    $result['codigo'] = $padronResult['condicion_codigo'];
                    $result['descripcion'] = $padronResult['condicion_descripcion'];
                    $result['source'] = 'padron_arca';

                    // Cachear resultado
                    $this->cachePadronData(
                        $cuit, 
                        $padronResult['condicion_codigo'], 
                        $padronResult['condicion_descripcion'],
                        $padronResult
                    );
                    return $result;
                }
            }

            // 4. Usar valor por defecto si está configurado
            if ($this->config['validacion_padron']['fallback_on_error']) {
                $defaultCondition = $this->config['migration']['default_receptor_condition'];
                $result['valid'] = true;
                $result['codigo'] = $defaultCondition;
                $result['descripcion'] = self::CONDICIONES_IVA[$defaultCondition];
                $result['source'] = 'default_fallback';
            } else {
                $result['errors'][] = 'No se pudo determinar la condición IVA del receptor';
            }

        } catch (Exception $e) {
            $this->logger->error("Error validating receptor IVA condition for CUIT {$cuit}: " . $e->getMessage());
            $result['errors'][] = 'Error interno validando condición IVA';
        }

        return $result;
    }

    /**
     * Validar operación en moneda extranjera
     */
    public function validateMonedaExtranjera(array $invoiceData): array
    {
        $result = [
            'valid' => true,
            'warnings' => [],
            'errors' => []
        ];

        $moneda = $invoiceData['currency'] ?? 'ARS';
        $cotizacion = $invoiceData['exchange_rate'] ?? null;
        $fechaCotizacion = $invoiceData['moneda_cotizacion_fecha'] ?? null;

        // Si es peso argentino, no hay validaciones adicionales
        if ($moneda === 'ARS') {
            return $result;
        }

        // Validaciones para moneda extranjera
        if ($this->config['moneda_extranjera']['strict_validation']) {
            
            // Verificar cotización
            if (empty($cotizacion) || $cotizacion <= 0) {
                $result['valid'] = false;
                $result['errors'][] = 'La cotización es obligatoria para operaciones en moneda extranjera';
            }

            // Verificar fecha de cotización
            if ($this->config['moneda_extranjera']['require_cotizacion_date']) {
                if (empty($fechaCotizacion)) {
                    $result['valid'] = false;
                    $result['errors'][] = 'La fecha de cotización es obligatoria para moneda extranjera';
                } else {
                    $maxAge = $this->config['moneda_extranjera']['max_cotizacion_age_days'];
                    $fechaCotizacionTime = strtotime($fechaCotizacion);
                    $maxAgeTime = time() - ($maxAge * 24 * 60 * 60);

                    if ($fechaCotizacionTime < $maxAgeTime) {
                        $result['warnings'][] = "La cotización tiene más de {$maxAge} días de antigüedad";
                    }
                }
            }

            // Verificar moneda válida
            $monedasPermitidas = array_keys($this->config['codigos']['monedas_extranjeras']);
            if (!in_array($moneda, $monedasPermitidas)) {
                $result['warnings'][] = "La moneda {$moneda} no está en la lista de monedas reconocidas por ARCA";
            }
        }

        return $result;
    }

    /**
     * Validar compliance completo de una factura
     */
    public function validateInvoiceCompliance(int $invoiceId, string $clientId): array
    {
        $result = [
            'compliant' => false,
            'errors' => [],
            'warnings' => [],
            'validations' => []
        ];

        try {
            // Verificar si está habilitado para el cliente
            if (!$this->isEnabledForClient($clientId)) {
                $result['compliant'] = true;
                $result['warnings'][] = 'ARCA 2025 no está habilitado para este cliente';
                return $result;
            }

            // Obtener datos de la factura
            $stmt = $this->db->prepare("
                SELECT * FROM invoices WHERE id = ? AND client_id = ?
            ");
            $stmt->execute([$invoiceId, $clientId]);
            $invoice = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$invoice) {
                $result['errors'][] = 'Factura no encontrada';
                return $result;
            }

            // 1. Validar condición IVA del receptor
            $ivaValidation = $this->validateReceptorCondicionIva(
                $invoice['recipient_cuit'], 
                $invoice['receptor_condicion_iva_codigo']
            );
            
            $result['validations']['receptor_iva'] = $ivaValidation;
            
            if (!$ivaValidation['valid']) {
                $result['errors'] = array_merge($result['errors'], $ivaValidation['errors']);
            }

            // 2. Validar moneda extranjera
            $monedaValidation = $this->validateMonedaExtranjera($invoice);
            $result['validations']['moneda_extranjera'] = $monedaValidation;
            
            if (!$monedaValidation['valid']) {
                $result['errors'] = array_merge($result['errors'], $monedaValidation['errors']);
            }
            $result['warnings'] = array_merge($result['warnings'], $monedaValidation['warnings']);

            // 3. Determinar compliance general
            $result['compliant'] = empty($result['errors']);

            // 4. Actualizar estado en la base de datos
            $this->updateInvoiceComplianceStatus($invoiceId, $result['compliant'], $result);

            // 5. Registrar auditoría
            $this->auditComplianceValidation($clientId, $invoiceId, $result);

        } catch (Exception $e) {
            $this->logger->error("Error validating compliance for invoice {$invoiceId}: " . $e->getMessage());
            $result['errors'][] = 'Error interno validando compliance';
        }

        return $result;
    }

    /**
     * Migrar cliente a ARCA 2025
     */
    public function migrateClientToArca2025(string $clientId, array $options = []): array
    {
        $result = [
            'success' => false,
            'migration_id' => null,
            'total_records' => 0,
            'processed_records' => 0,
            'errors' => []
        ];

        try {
            // Crear registro de migración
            $stmt = $this->db->prepare("
                INSERT INTO arca_2025_migrations 
                (client_id, migration_type, status, start_time, total_records)
                SELECT ?, 'client_migration', 'in_progress', NOW(), COUNT(*)
                FROM invoices WHERE client_id = ? AND arca_2025_compliant = FALSE
            ");
            $stmt->execute([$clientId, $clientId]);
            $migrationId = $this->db->lastInsertId();
            $result['migration_id'] = $migrationId;

            // Obtener total de registros
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as total 
                FROM invoices 
                WHERE client_id = ? AND arca_2025_compliant = FALSE
            ");
            $stmt->execute([$clientId]);
            $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            $result['total_records'] = $total;

            if ($total === 0) {
                $this->completeMigration($migrationId, 'completed');
                $result['success'] = true;
                return $result;
            }

            // Procesar facturas en lotes
            $batchSize = $options['batch_size'] ?? $this->config['migration']['batch_size'];
            $processed = 0;

            while ($processed < $total) {
                $stmt = $this->db->prepare("
                    SELECT id, recipient_cuit 
                    FROM invoices 
                    WHERE client_id = ? AND arca_2025_compliant = FALSE
                    LIMIT ?
                ");
                $stmt->execute([$clientId, $batchSize]);
                $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (empty($invoices)) {
                    break;
                }

                foreach ($invoices as $invoice) {
                    $this->migrateInvoiceToArca2025($invoice['id'], $clientId);
                    $processed++;
                }

                // Actualizar progreso
                $this->updateMigrationProgress($migrationId, $processed);
            }

            // Completar migración
            $this->completeMigration($migrationId, 'completed');
            $result['success'] = true;
            $result['processed_records'] = $processed;

        } catch (Exception $e) {
            $this->logger->error("Error migrating client {$clientId} to ARCA 2025: " . $e->getMessage());
            $result['errors'][] = $e->getMessage();
            
            if (isset($migrationId)) {
                $this->completeMigration($migrationId, 'failed', $e->getMessage());
            }
        }

        return $result;
    }

    /**
     * Obtener estado de compliance para un cliente
     */
    public function getClientComplianceStatus(string $clientId): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM arca_2025_compliance_status 
                WHERE client_id = ?
            ");
            $stmt->execute([$clientId]);
            $status = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$status) {
                return [
                    'client_id' => $clientId,
                    'migration_status' => 'preparacion',
                    'compliance_percentage' => 0,
                    'total_invoices' => 0,
                    'compliant_invoices' => 0
                ];
            }

            return $status;
        } catch (PDOException $e) {
            $this->logger->error("Error getting compliance status for client {$clientId}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Configurar cliente para ARCA 2025
     */
    public function configureClientForArca2025(string $clientId, array $config): bool
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO client_arca_2025_config 
                (client_id, migration_status, validacion_iva_enabled, validacion_padron_enabled, 
                 moneda_extranjera_strict, webservice_version, auto_migrate_enabled, 
                 compliance_deadline, notificaciones_enabled, config_json)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                migration_status = VALUES(migration_status),
                validacion_iva_enabled = VALUES(validacion_iva_enabled),
                validacion_padron_enabled = VALUES(validacion_padron_enabled),
                moneda_extranjera_strict = VALUES(moneda_extranjera_strict),
                webservice_version = VALUES(webservice_version),
                auto_migrate_enabled = VALUES(auto_migrate_enabled),
                compliance_deadline = VALUES(compliance_deadline),
                notificaciones_enabled = VALUES(notificaciones_enabled),
                config_json = VALUES(config_json),
                updated_at = CURRENT_TIMESTAMP
            ");

            return $stmt->execute([
                $clientId,
                $config['migration_status'] ?? 'preparacion',
                $config['validacion_iva_enabled'] ?? false,
                $config['validacion_padron_enabled'] ?? false,
                $config['moneda_extranjera_strict'] ?? false,
                $config['webservice_version'] ?? '1.0',
                $config['auto_migrate_enabled'] ?? false,
                $config['compliance_deadline'] ?? null,
                $config['notificaciones_enabled'] ?? true,
                isset($config['config_json']) ? json_encode($config['config_json']) : null
            ]);

        } catch (PDOException $e) {
            $this->logger->error("Error configuring client {$clientId} for ARCA 2025: " . $e->getMessage());
            return false;
        }
    }

    // =========================================================================
    // MÉTODOS PRIVADOS
    // =========================================================================

    private function getCachedPadronData(string $cuit): ?array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM padron_arca_cache 
            WHERE cuit = ? AND expires_at > NOW()
        ");
        $stmt->execute([$cuit]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    private function isCacheExpired(array $cached): bool
    {
        return strtotime($cached['expires_at']) < time();
    }

    private function cachePadronData(string $cuit, int $condicionCodigo, string $condicionDescripcion, array $fullData = null): void
    {
        $ttl = $this->config['validacion_padron']['cache_duration'];
        $expiresAt = date('Y-m-d H:i:s', time() + $ttl);

        $stmt = $this->db->prepare("
            INSERT INTO padron_arca_cache 
            (cuit, condicion_iva_codigo, condicion_iva_descripcion, response_data, expires_at)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
            condicion_iva_codigo = VALUES(condicion_iva_codigo),
            condicion_iva_descripcion = VALUES(condicion_iva_descripcion),
            response_data = VALUES(response_data),
            expires_at = VALUES(expires_at),
            cached_at = CURRENT_TIMESTAMP
        ");

        $stmt->execute([
            $cuit,
            $condicionCodigo,
            $condicionDescripcion,
            $fullData ? json_encode($fullData) : null,
            $expiresAt
        ]);
    }

    private function queryPadronArca(string $cuit): array
    {
        // TODO: Implementar consulta real al padrón ARCA cuando esté disponible
        // Por ahora retornamos un mock para testing
        
        if ($this->config['testing']['mock_padron_responses']) {
            return [
                'success' => true,
                'condicion_codigo' => 5, // Consumidor Final por defecto
                'condicion_descripcion' => 'Consumidor Final',
                'razon_social' => 'Contribuyente Test',
                'estado' => 'ACTIVO'
            ];
        }

        // Implementación real pendiente de URLs oficiales
        return [
            'success' => false,
            'error' => 'Servicio de padrón ARCA no disponible'
        ];
    }

    private function updateInvoiceComplianceStatus(int $invoiceId, bool $compliant, array $validationResult): void
    {
        $stmt = $this->db->prepare("
            UPDATE invoices 
            SET arca_2025_compliant = ?, 
                arca_2025_migration_date = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $stmt->execute([$compliant ? 1 : 0, $invoiceId]);
    }

    private function auditComplianceValidation(string $clientId, int $invoiceId, array $result): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO arca_2025_audit 
            (client_id, invoice_id, action, description, compliance_status, validation_errors)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $clientId,
            $invoiceId,
            'compliance_validation',
            'Validación de compliance ARCA 2025',
            $result['compliant'] ? 'compliant' : 'non_compliant',
            json_encode([
                'errors' => $result['errors'],
                'warnings' => $result['warnings'],
                'validations' => $result['validations']
            ])
        ]);
    }

    private function migrateInvoiceToArca2025(int $invoiceId, string $clientId): void
    {
        // Aplicar valores por defecto para compliance ARCA 2025
        $defaultCondition = $this->config['migration']['default_receptor_condition'];
        
        $stmt = $this->db->prepare("
            UPDATE invoices 
            SET receptor_condicion_iva_codigo = ?,
                receptor_condicion_iva_descripcion = ?,
                arca_2025_compliant = TRUE,
                arca_2025_migration_date = CURRENT_TIMESTAMP
            WHERE id = ? AND client_id = ?
        ");

        $stmt->execute([
            $defaultCondition,
            self::CONDICIONES_IVA[$defaultCondition],
            $invoiceId,
            $clientId
        ]);
    }

    private function updateMigrationProgress(int $migrationId, int $processed): void
    {
        $stmt = $this->db->prepare("
            UPDATE arca_2025_migrations 
            SET processed_records = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $stmt->execute([$processed, $migrationId]);
    }

    private function completeMigration(int $migrationId, string $status, string $error = null): void
    {
        $stmt = $this->db->prepare("
            UPDATE arca_2025_migrations 
            SET status = ?, end_time = CURRENT_TIMESTAMP, error_details = ?
            WHERE id = ?
        ");
        $stmt->execute([$status, $error ? json_encode(['error' => $error]) : null, $migrationId]);
    }
} 