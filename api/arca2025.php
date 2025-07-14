<?php

/**
 * API REST para gestión ARCA 2025 Compliance
 * Resolución General N° 5616
 * 
 * Endpoints específicos para el cumplimiento normativo ARCA 2025
 * en la API de facturación electrónica multi-tenant.
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Middleware/AuthenticationMiddleware.php';

use App\Services\Arca2025ComplianceService;
use App\Middleware\AuthenticationMiddleware;
use App\Utils\Logger;
use App\Utils\ResponseHelper;

// Configurar headers CORS y JSON
ResponseHelper::setCorsHeaders();
header('Content-Type: application/json');

// Inicializar servicios
$logger = new Logger();
$complianceService = new Arca2025ComplianceService($pdo, $logger);
$authMiddleware = new AuthenticationMiddleware($pdo, $logger);

// Obtener método HTTP y ruta
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$pathParts = array_filter(explode('/', $path));
$endpoint = end($pathParts);

// Manejar OPTIONS para CORS
if ($method === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    // Autenticación
    $authResult = $authMiddleware->authenticate();
    if (!$authResult['success']) {
        ResponseHelper::sendError($authResult['message'], 401);
        exit();
    }
    
    $clientId = $authResult['client_id'];
    
    // Logging de request
    $logger->info("ARCA 2025 API Request", [
        'method' => $method,
        'endpoint' => $endpoint,
        'client_id' => $clientId,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]);
    
    // Router principal
    switch ($endpoint) {
        case 'status':
            handleComplianceStatus($complianceService, $clientId, $method);
            break;
            
        case 'configure':
            handleClientConfiguration($complianceService, $clientId, $method);
            break;
            
        case 'validate':
            handleInvoiceValidation($complianceService, $clientId, $method);
            break;
            
        case 'migrate':
            handleClientMigration($complianceService, $clientId, $method);
            break;
            
        case 'migration-status':
            handleMigrationStatus($pdo, $clientId, $method);
            break;
            
        case 'validate-cuit':
            handleCuitValidation($complianceService, $clientId, $method);
            break;
            
        case 'report':
            handleComplianceReport($complianceService, $clientId, $method);
            break;
            
        case 'condiciones-iva':
            handleCondicionesIva($pdo, $method);
            break;
            
        case 'notifications':
            handleNotifications($pdo, $clientId, $method);
            break;
            
        default:
            ResponseHelper::sendError('Endpoint not found', 404);
    }
    
} catch (Exception $e) {
    $logger->error('ARCA 2025 API Error: ' . $e->getMessage(), [
        'trace' => $e->getTraceAsString(),
        'client_id' => $clientId ?? 'unknown'
    ]);
    
    ResponseHelper::sendError('Internal server error', 500);
}

// =========================================================================
// HANDLERS DE ENDPOINTS
// =========================================================================

/**
 * GET /api/arca2025/status
 * Obtener estado de compliance del cliente
 */
function handleComplianceStatus($complianceService, $clientId, $method) {
    if ($method !== 'GET') {
        ResponseHelper::sendError('Method not allowed', 405);
        return;
    }
    
    $status = $complianceService->getClientComplianceStatus($clientId);
    
    if (empty($status)) {
        ResponseHelper::sendError('Client not found or not configured for ARCA 2025', 404);
        return;
    }
    
    ResponseHelper::sendSuccess($status);
}

/**
 * GET/POST /api/arca2025/configure
 * Obtener o actualizar configuración ARCA 2025 del cliente
 */
function handleClientConfiguration($complianceService, $clientId, $method) {
    global $pdo;
    
    if ($method === 'GET') {
        // Obtener configuración actual
        $stmt = $pdo->prepare("
            SELECT * FROM client_arca_2025_config 
            WHERE client_id = ?
        ");
        $stmt->execute([$clientId]);
        $config = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$config) {
            ResponseHelper::sendError('Client not configured for ARCA 2025', 404);
            return;
        }
        
        // Decodificar JSON config si existe
        if ($config['config_json']) {
            $config['config_json'] = json_decode($config['config_json'], true);
        }
        
        ResponseHelper::sendSuccess($config);
        
    } elseif ($method === 'POST') {
        // Actualizar configuración
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            ResponseHelper::sendError('Invalid JSON input', 400);
            return;
        }
        
        // Validar campos requeridos
        $allowedFields = [
            'migration_status', 'validacion_iva_enabled', 'validacion_padron_enabled',
            'moneda_extranjera_strict', 'webservice_version', 'auto_migrate_enabled',
            'compliance_deadline', 'notificaciones_enabled', 'config_json'
        ];
        
        $config = [];
        foreach ($allowedFields as $field) {
            if (isset($input[$field])) {
                $config[$field] = $input[$field];
            }
        }
        
        if (empty($config)) {
            ResponseHelper::sendError('No valid configuration fields provided', 400);
            return;
        }
        
        $success = $complianceService->configureClientForArca2025($clientId, $config);
        
        if ($success) {
            ResponseHelper::sendSuccess([
                'message' => 'Client configuration updated successfully',
                'client_id' => $clientId,
                'updated_fields' => array_keys($config)
            ]);
        } else {
            ResponseHelper::sendError('Failed to update client configuration', 500);
        }
        
    } else {
        ResponseHelper::sendError('Method not allowed', 405);
    }
}

/**
 * POST /api/arca2025/validate
 * Validar compliance de una factura específica
 */
function handleInvoiceValidation($complianceService, $clientId, $method) {
    if ($method !== 'POST') {
        ResponseHelper::sendError('Method not allowed', 405);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['invoice_id'])) {
        ResponseHelper::sendError('invoice_id is required', 400);
        return;
    }
    
    $invoiceId = (int)$input['invoice_id'];
    $result = $complianceService->validateInvoiceCompliance($invoiceId, $clientId);
    
    ResponseHelper::sendSuccess([
        'invoice_id' => $invoiceId,
        'compliance_result' => $result
    ]);
}

/**
 * POST /api/arca2025/migrate
 * Iniciar migración del cliente a ARCA 2025
 */
function handleClientMigration($complianceService, $clientId, $method) {
    if ($method !== 'POST') {
        ResponseHelper::sendError('Method not allowed', 405);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    
    // Opciones de migración
    $options = [
        'batch_size' => $input['batch_size'] ?? 1000,
        'dry_run' => $input['dry_run'] ?? false
    ];
    
    if ($options['dry_run']) {
        ResponseHelper::sendSuccess([
            'message' => 'Dry run mode - no changes made',
            'client_id' => $clientId,
            'options' => $options
        ]);
        return;
    }
    
    $result = $complianceService->migrateClientToArca2025($clientId, $options);
    
    if ($result['success']) {
        ResponseHelper::sendSuccess([
            'message' => 'Migration completed successfully',
            'migration_id' => $result['migration_id'],
            'total_records' => $result['total_records'],
            'processed_records' => $result['processed_records']
        ]);
    } else {
        ResponseHelper::sendError('Migration failed: ' . implode(', ', $result['errors']), 500);
    }
}

/**
 * GET /api/arca2025/migration-status/{migration_id}
 * Obtener estado de una migración específica
 */
function handleMigrationStatus($pdo, $clientId, $method) {
    if ($method !== 'GET') {
        ResponseHelper::sendError('Method not allowed', 405);
        return;
    }
    
    // Obtener migration_id de la URL
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $pathParts = array_filter(explode('/', $path));
    $migrationId = end($pathParts);
    
    if (!is_numeric($migrationId)) {
        ResponseHelper::sendError('Invalid migration_id', 400);
        return;
    }
    
    $stmt = $pdo->prepare("
        SELECT * FROM arca_2025_migrations 
        WHERE id = ? AND client_id = ?
    ");
    $stmt->execute([$migrationId, $clientId]);
    $migration = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$migration) {
        ResponseHelper::sendError('Migration not found', 404);
        return;
    }
    
    // Decodificar error_details si existe
    if ($migration['error_details']) {
        $migration['error_details'] = json_decode($migration['error_details'], true);
    }
    
    ResponseHelper::sendSuccess($migration);
}

/**
 * POST /api/arca2025/validate-cuit
 * Validar condición IVA de un CUIT específico
 */
function handleCuitValidation($complianceService, $clientId, $method) {
    if ($method !== 'POST') {
        ResponseHelper::sendError('Method not allowed', 405);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['cuit'])) {
        ResponseHelper::sendError('cuit is required', 400);
        return;
    }
    
    $cuit = $input['cuit'];
    $condicionCodigo = $input['condicion_codigo'] ?? null;
    
    // Validar formato CUIT básico
    if (!preg_match('/^\d{11}$/', $cuit)) {
        ResponseHelper::sendError('Invalid CUIT format (must be 11 digits)', 400);
        return;
    }
    
    $result = $complianceService->validateReceptorCondicionIva($cuit, $condicionCodigo);
    
    ResponseHelper::sendSuccess([
        'cuit' => $cuit,
        'validation_result' => $result
    ]);
}

/**
 * GET /api/arca2025/report
 * Generar reporte de compliance del cliente
 */
function handleComplianceReport($complianceService, $clientId, $method) {
    global $pdo;
    
    if ($method !== 'GET') {
        ResponseHelper::sendError('Method not allowed', 405);
        return;
    }
    
    // Parámetros de consulta
    $period = $_GET['period'] ?? '30'; // días
    $includeDetails = isset($_GET['details']) && $_GET['details'] === 'true';
    
    // Reporte general
    $status = $complianceService->getClientComplianceStatus($clientId);
    
    if (empty($status)) {
        ResponseHelper::sendError('Client not configured for ARCA 2025', 404);
        return;
    }
    
    $report = [
        'client_summary' => $status,
        'period_days' => (int)$period,
        'generated_at' => date('Y-m-d H:i:s')
    ];
    
    if ($includeDetails) {
        // Facturas recientes con problemas de compliance
        $stmt = $pdo->prepare("
            SELECT i.id, i.invoice_number, i.created_at, i.arca_2025_compliant,
                   i.receptor_condicion_iva_codigo, i.currency, i.total_amount
            FROM invoices i
            WHERE i.client_id = ? 
            AND i.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            AND i.arca_2025_compliant = FALSE
            ORDER BY i.created_at DESC
            LIMIT 50
        ");
        $stmt->execute([$clientId, $period]);
        $nonCompliantInvoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Auditoría reciente
        $stmt = $pdo->prepare("
            SELECT action, description, compliance_status, created_at
            FROM arca_2025_audit
            WHERE client_id = ?
            AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            ORDER BY created_at DESC
            LIMIT 20
        ");
        $stmt->execute([$clientId, $period]);
        $auditLog = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $report['details'] = [
            'non_compliant_invoices' => $nonCompliantInvoices,
            'recent_audit_log' => $auditLog
        ];
    }
    
    ResponseHelper::sendSuccess($report);
}

/**
 * GET /api/arca2025/condiciones-iva
 * Obtener catálogo de condiciones IVA oficiales
 */
function handleCondicionesIva($pdo, $method) {
    if ($method !== 'GET') {
        ResponseHelper::sendError('Method not allowed', 405);
        return;
    }
    
    $stmt = $pdo->query("
        SELECT codigo, descripcion, descripcion_corta, activa, obligatoria_2025
        FROM condiciones_iva
        WHERE activa = TRUE
        ORDER BY codigo
    ");
    $condiciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    ResponseHelper::sendSuccess([
        'condiciones_iva' => $condiciones,
        'total' => count($condiciones)
    ]);
}

/**
 * GET/POST /api/arca2025/notifications
 * Gestionar notificaciones de compliance
 */
function handleNotifications($pdo, $clientId, $method) {
    if ($method === 'GET') {
        // Obtener notificaciones del cliente
        $unreadOnly = isset($_GET['unread']) && $_GET['unread'] === 'true';
        $limit = min((int)($_GET['limit'] ?? 50), 100);
        
        $whereClause = "WHERE client_id = ?";
        $params = [$clientId];
        
        if ($unreadOnly) {
            $whereClause .= " AND read_at IS NULL";
        }
        
        $stmt = $pdo->prepare("
            SELECT id, type, title, message, data, read_at, sent_at, 
                   expires_at, created_at
            FROM compliance_notifications
            {$whereClause}
            AND (expires_at IS NULL OR expires_at > NOW())
            ORDER BY created_at DESC
            LIMIT ?
        ");
        
        $params[] = $limit;
        $stmt->execute($params);
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Decodificar campo data
        foreach ($notifications as &$notification) {
            if ($notification['data']) {
                $notification['data'] = json_decode($notification['data'], true);
            }
        }
        
        ResponseHelper::sendSuccess([
            'notifications' => $notifications,
            'unread_only' => $unreadOnly,
            'total' => count($notifications)
        ]);
        
    } elseif ($method === 'POST') {
        // Marcar notificación como leída
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['notification_id'])) {
            ResponseHelper::sendError('notification_id is required', 400);
            return;
        }
        
        $stmt = $pdo->prepare("
            UPDATE compliance_notifications 
            SET read_at = CURRENT_TIMESTAMP
            WHERE id = ? AND client_id = ? AND read_at IS NULL
        ");
        $stmt->execute([$input['notification_id'], $clientId]);
        
        $updated = $stmt->rowCount();
        
        if ($updated > 0) {
            ResponseHelper::sendSuccess([
                'message' => 'Notification marked as read',
                'notification_id' => $input['notification_id']
            ]);
        } else {
            ResponseHelper::sendError('Notification not found or already read', 404);
        }
        
    } else {
        ResponseHelper::sendError('Method not allowed', 405);
    }
} 