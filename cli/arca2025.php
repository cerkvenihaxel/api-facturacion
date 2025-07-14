#!/usr/bin/env php
<?php

/**
 * CLI para gestión ARCA 2025 Compliance
 * Resolución General N° 5616
 * 
 * Herramienta de línea de comandos para gestionar el cumplimiento
 * normativo ARCA 2025 en la API de facturación electrónica.
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';

use App\Services\Arca2025ComplianceService;
use App\Utils\Logger;

// Configuración CLI
$logger = new Logger();
$complianceService = new Arca2025ComplianceService($pdo, $logger);

// Colores para terminal
function colorize($text, $color) {
    $colors = [
        'red' => "\033[31m",
        'green' => "\033[32m",
        'yellow' => "\033[33m",
        'blue' => "\033[34m",
        'cyan' => "\033[36m",
        'white' => "\033[37m",
        'reset' => "\033[0m"
    ];
    return $colors[$color] . $text . $colors['reset'];
}

function printHeader($title) {
    echo "\n" . colorize("=== ARCA 2025 COMPLIANCE CLI ===", 'cyan') . "\n";
    echo colorize($title, 'yellow') . "\n";
    echo str_repeat('-', 50) . "\n\n";
}

function printSuccess($message) {
    echo colorize("✓ " . $message, 'green') . "\n";
}

function printError($message) {
    echo colorize("✗ " . $message, 'red') . "\n";
}

function printWarning($message) {
    echo colorize("⚠ " . $message, 'yellow') . "\n";
}

function printInfo($message) {
    echo colorize("ℹ " . $message, 'blue') . "\n";
}

// Función para mostrar ayuda
function showHelp() {
    printHeader("Ayuda - Comandos disponibles");
    
    echo colorize("Gestión de compliance:", 'yellow') . "\n";
    echo "  php arca2025.php status <client_id>          - Ver estado de compliance\n";
    echo "  php arca2025.php validate <client_id>        - Validar compliance de facturas\n";
    echo "  php arca2025.php configure <client_id>       - Configurar cliente para ARCA 2025\n";
    echo "\n";
    
    echo colorize("Migraciones:", 'yellow') . "\n";
    echo "  php arca2025.php migrate <client_id>         - Migrar cliente a ARCA 2025\n";
    echo "  php arca2025.php migrate-all                 - Migrar todos los clientes\n";
    echo "  php arca2025.php migration-status <id>       - Ver estado de migración\n";
    echo "\n";
    
    echo colorize("Reportes:", 'yellow') . "\n";
    echo "  php arca2025.php report                      - Reporte general de compliance\n";
    echo "  php arca2025.php report <client_id>          - Reporte específico de cliente\n";
    echo "\n";
    
    echo colorize("Utilidades:", 'yellow') . "\n";
    echo "  php arca2025.php test-cuit <cuit>            - Probar validación de CUIT\n";
    echo "  php arca2025.php cleanup-cache               - Limpiar caché de padrón\n";
    echo "  php arca2025.php check-db                    - Verificar estructura de BD\n";
    echo "\n";
    
    echo colorize("Opciones globales:", 'yellow') . "\n";
    echo "  --help, -h                                   - Mostrar esta ayuda\n";
    echo "  --verbose, -v                                - Modo detallado\n";
    echo "  --dry-run                                    - Simular sin hacer cambios\n";
    echo "\n";
}

// Función para verificar estructura de base de datos
function checkDatabase($pdo) {
    printHeader("Verificación de estructura de base de datos");
    
    $tables = [
        'condiciones_iva',
        'client_arca_2025_config', 
        'arca_2025_audit',
        'padron_arca_cache',
        'compliance_notifications',
        'arca_2025_migrations'
    ];
    
    $tablesExist = true;
    
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("DESCRIBE {$table}");
            printSuccess("Tabla '{$table}' existe");
        } catch (PDOException $e) {
            printError("Tabla '{$table}' NO existe");
            $tablesExist = false;
        }
    }
    
    if ($tablesExist) {
        printSuccess("Todas las tablas requeridas están presentes");
        
        // Verificar datos básicos
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM condiciones_iva");
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        if ($count > 0) {
            printSuccess("Códigos de condiciones IVA cargados ({$count} registros)");
        } else {
            printWarning("No hay códigos de condiciones IVA cargados");
        }
        
    } else {
        printError("Faltan tablas requeridas. Ejecutar migración SQL primero.");
        return false;
    }
    
    return true;
}

// Función para mostrar estado de compliance
function showComplianceStatus($complianceService, $clientId) {
    printHeader("Estado de Compliance ARCA 2025");
    
    $status = $complianceService->getClientComplianceStatus($clientId);
    
    if (empty($status)) {
        printError("Cliente no encontrado o sin configuración ARCA 2025");
        return;
    }
    
    echo colorize("Cliente: ", 'yellow') . $status['client_id'] . "\n";
    echo colorize("Empresa: ", 'yellow') . ($status['business_name'] ?? 'N/A') . "\n";
    echo colorize("Estado de migración: ", 'yellow') . $status['migration_status'] . "\n";
    echo colorize("Facturas totales: ", 'yellow') . $status['total_invoices'] . "\n";
    echo colorize("Facturas compliance: ", 'yellow') . $status['compliant_invoices'] . "\n";
    echo colorize("Porcentaje compliance: ", 'yellow') . $status['compliance_percentage'] . "%\n";
    
    // Color del porcentaje
    if ($status['compliance_percentage'] >= 95) {
        printSuccess("Compliance excelente (≥95%)");
    } elseif ($status['compliance_percentage'] >= 80) {
        printWarning("Compliance bueno (≥80%)");
    } else {
        printError("Compliance bajo (<80%) - Requiere atención");
    }
    
    echo "\n" . colorize("Configuraciones:", 'yellow') . "\n";
    echo "  Validación IVA: " . ($status['validacion_iva_enabled'] ? 'Habilitada' : 'Deshabilitada') . "\n";
    echo "  Validación padrón: " . ($status['validacion_padron_enabled'] ? 'Habilitada' : 'Deshabilitada') . "\n";
    echo "  Moneda extranjera strict: " . ($status['moneda_extranjera_strict'] ? 'Habilitada' : 'Deshabilitada') . "\n";
    echo "  Versión webservice: " . $status['webservice_version'] . "\n";
    
    if ($status['compliance_deadline']) {
        echo "  Fecha límite compliance: " . $status['compliance_deadline'] . "\n";
    }
}

// Función para configurar cliente
function configureClient($complianceService, $clientId) {
    printHeader("Configuración ARCA 2025 para cliente: {$clientId}");
    
    echo "Configurando cliente para compliance ARCA 2025...\n\n";
    
    // Configuración interactiva
    echo "¿Habilitar validación de condición IVA? (s/n): ";
    $ivaEnabled = trim(fgets(STDIN)) === 's';
    
    echo "¿Habilitar validación contra padrón ARCA? (s/n): ";
    $padronEnabled = trim(fgets(STDIN)) === 's';
    
    echo "¿Habilitar validación estricta de moneda extranjera? (s/n): ";
    $monedaStrict = trim(fgets(STDIN)) === 's';
    
    echo "Estado de migración (preparacion/testing/produccion): ";
    $migrationStatus = trim(fgets(STDIN)) ?: 'preparacion';
    
    echo "Fecha límite de compliance (YYYY-MM-DD, opcional): ";
    $deadline = trim(fgets(STDIN)) ?: null;
    
    $config = [
        'migration_status' => $migrationStatus,
        'validacion_iva_enabled' => $ivaEnabled,
        'validacion_padron_enabled' => $padronEnabled,
        'moneda_extranjera_strict' => $monedaStrict,
        'webservice_version' => '2025.1',
        'auto_migrate_enabled' => false,
        'compliance_deadline' => $deadline,
        'notificaciones_enabled' => true
    ];
    
    if ($complianceService->configureClientForArca2025($clientId, $config)) {
        printSuccess("Cliente configurado exitosamente");
        
        echo "\n" . colorize("Configuración aplicada:", 'yellow') . "\n";
        foreach ($config as $key => $value) {
            $displayValue = is_bool($value) ? ($value ? 'Sí' : 'No') : $value;
            echo "  {$key}: {$displayValue}\n";
        }
    } else {
        printError("Error configurando cliente");
    }
}

// Función para migrar cliente
function migrateClient($complianceService, $clientId, $options = []) {
    printHeader("Migración ARCA 2025 para cliente: {$clientId}");
    
    printInfo("Iniciando migración de datos legacy a ARCA 2025...");
    
    $result = $complianceService->migrateClientToArca2025($clientId, $options);
    
    if ($result['success']) {
        printSuccess("Migración completada exitosamente");
        echo "  Total de registros: " . $result['total_records'] . "\n";
        echo "  Registros procesados: " . $result['processed_records'] . "\n";
        echo "  ID de migración: " . $result['migration_id'] . "\n";
    } else {
        printError("Error en la migración:");
        foreach ($result['errors'] as $error) {
            echo "  - {$error}\n";
        }
    }
}

// Función para validar CUIT
function testCuit($complianceService, $cuit) {
    printHeader("Prueba de validación CUIT: {$cuit}");
    
    $result = $complianceService->validateReceptorCondicionIva($cuit);
    
    echo colorize("CUIT: ", 'yellow') . $cuit . "\n";
    echo colorize("Válido: ", 'yellow') . ($result['valid'] ? 'Sí' : 'No') . "\n";
    
    if ($result['valid']) {
        echo colorize("Código: ", 'yellow') . $result['codigo'] . "\n";
        echo colorize("Descripción: ", 'yellow') . $result['descripcion'] . "\n";
        echo colorize("Fuente: ", 'yellow') . $result['source'] . "\n";
        printSuccess("Validación exitosa");
    } else {
        echo colorize("Errores:", 'red') . "\n";
        foreach ($result['errors'] as $error) {
            echo "  - {$error}\n";
        }
    }
}

// Función para generar reporte
function generateReport($pdo, $clientId = null) {
    printHeader("Reporte de Compliance ARCA 2025");
    
    if ($clientId) {
        $sql = "SELECT * FROM arca_2025_compliance_status WHERE client_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$clientId]);
        $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $sql = "SELECT * FROM arca_2025_compliance_status ORDER BY compliance_percentage DESC";
        $stmt = $pdo->query($sql);
        $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    if (empty($clients)) {
        printWarning("No hay datos de compliance disponibles");
        return;
    }
    
    printf("%-15s %-30s %-15s %-10s %-10s %-12s\n", 
           'CLIENT_ID', 'BUSINESS_NAME', 'MIGRATION', 'TOTAL', 'COMPLIANT', 'PERCENTAGE');
    echo str_repeat('-', 95) . "\n";
    
    foreach ($clients as $client) {
        $percentage = $client['compliance_percentage'];
        $status = $client['migration_status'];
        
        // Color según porcentaje
        if ($percentage >= 95) {
            $color = 'green';
        } elseif ($percentage >= 80) {
            $color = 'yellow';
        } else {
            $color = 'red';
        }
        
        printf("%-15s %-30s %-15s %-10s %-10s %s\n",
               $client['client_id'],
               substr($client['business_name'] ?? 'N/A', 0, 28),
               $status,
               $client['total_invoices'],
               $client['compliant_invoices'],
               colorize(sprintf('%10.1f%%', $percentage), $color)
        );
    }
    
    echo "\n";
    
    // Estadísticas generales
    if (!$clientId) {
        $totalClients = count($clients);
        $avgCompliance = array_sum(array_column($clients, 'compliance_percentage')) / $totalClients;
        $readyClients = count(array_filter($clients, fn($c) => $c['compliance_percentage'] >= 95));
        
        echo colorize("Estadísticas generales:", 'yellow') . "\n";
        echo "  Total de clientes: {$totalClients}\n";
        echo "  Compliance promedio: " . sprintf('%.1f%%', $avgCompliance) . "\n";
        echo "  Clientes listos (≥95%): {$readyClients}\n";
        echo "  Clientes pendientes: " . ($totalClients - $readyClients) . "\n";
    }
}

// Función para limpiar caché
function cleanupCache($pdo) {
    printHeader("Limpieza de caché de padrón ARCA");
    
    try {
        $stmt = $pdo->query("DELETE FROM padron_arca_cache WHERE expires_at < NOW()");
        $deleted = $stmt->rowCount();
        
        printSuccess("Cache limpiado: {$deleted} registros eliminados");
        
        // Estadísticas del cache
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM padron_arca_cache");
        $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        echo "Registros restantes en cache: {$total}\n";
        
    } catch (PDOException $e) {
        printError("Error limpiando cache: " . $e->getMessage());
    }
}

// =========================================================================
// PROCESAMIENTO DE ARGUMENTOS
// =========================================================================

$options = getopt('hv', ['help', 'verbose', 'dry-run']);
$args = array_slice($argv, 1);

// Filtrar opciones de argumentos
$args = array_filter($args, function($arg) {
    return !in_array($arg, ['-h', '--help', '-v', '--verbose', '--dry-run']);
});
$args = array_values($args);

$verbose = isset($options['v']) || isset($options['verbose']);
$dryRun = isset($options['dry-run']);

if (isset($options['h']) || isset($options['help']) || empty($args)) {
    showHelp();
    exit(0);
}

$command = $args[0];
$clientId = $args[1] ?? null;

// Verificar estructura de BD para comandos que la requieren
$dbCommands = ['status', 'validate', 'configure', 'migrate', 'migrate-all', 'report'];
if (in_array($command, $dbCommands) && !checkDatabase($pdo)) {
    printError("La estructura de base de datos no está lista para ARCA 2025");
    exit(1);
}

// =========================================================================
// EJECUCIÓN DE COMANDOS
// =========================================================================

switch ($command) {
    case 'status':
        if (!$clientId) {
            printError("Especificar client_id");
            exit(1);
        }
        showComplianceStatus($complianceService, $clientId);
        break;
        
    case 'configure':
        if (!$clientId) {
            printError("Especificar client_id");
            exit(1);
        }
        configureClient($complianceService, $clientId);
        break;
        
    case 'migrate':
        if (!$clientId) {
            printError("Especificar client_id");
            exit(1);
        }
        migrateClient($complianceService, $clientId);
        break;
        
    case 'test-cuit':
        if (!$clientId) {
            printError("Especificar CUIT");
            exit(1);
        }
        testCuit($complianceService, $clientId);
        break;
        
    case 'report':
        generateReport($pdo, $clientId);
        break;
        
    case 'cleanup-cache':
        cleanupCache($pdo);
        break;
        
    case 'check-db':
        checkDatabase($pdo);
        break;
        
    default:
        printError("Comando desconocido: {$command}");
        showHelp();
        exit(1);
}

echo "\n";
exit(0); 