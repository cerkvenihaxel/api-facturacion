<?php
/**
 * API Multi-tenant de Facturación AFIP
 * Soporte para WSFE y WSBFE
 * 
 * @version 2.0
 * @author Sistema de Facturación AFIP
 */

// Configuración de errores para producción
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Autoload de Composer
require_once __DIR__ . '/../vendor/autoload.php';

// Inicializar base de datos
try {
    \AfipApi\Core\Database::initialize();
} catch (Exception $e) {
    http_response_code(503);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'Error de inicialización del sistema',
        'code' => 503
    ]);
    exit;
}

// Configurar zona horaria
date_default_timezone_set('America/Argentina/Buenos_Aires');

// Crear directorio de logs si no existe
$logsDir = __DIR__ . '/../logs';
if (!is_dir($logsDir)) {
    mkdir($logsDir, 0755, true);
}

// Crear directorio de facturas si no existe
$facturasDir = __DIR__ . '/facturas';
if (!is_dir($facturasDir)) {
    mkdir($facturasDir, 0755, true);
}

// Crear directorio de base de datos si no existe
$dbDir = __DIR__ . '/../database';
if (!is_dir($dbDir)) {
    mkdir($dbDir, 0755, true);
}

// Manejar la petición
try {
    $controller = new \AfipApi\Controllers\FacturacionController();
    $controller->handle();
} catch (Exception $e) {
    // Log del error
    $logFile = $logsDir . '/system_error.log';
    $timestamp = date('Y-m-d H:i:s');
    $errorMsg = "[$timestamp] SYSTEM ERROR: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . PHP_EOL;
    file_put_contents($logFile, $errorMsg, FILE_APPEND | LOCK_EX);
    
    // Respuesta de error
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'Error interno del sistema',
        'code' => 500,
        'timestamp' => date('c')
    ]);
} 