<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/config.php';
use AfipApi\AfipWs;
use AfipApi\FacturaPDF;

// Obtener la URI de la petición de manera compatible con el servidor de desarrollo de PHP
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Limpiar la URI de parámetros de consulta
$requestUri = parse_url($requestUri, PHP_URL_PATH);

// Debug: Log de la petición para troubleshooting
error_log("Request URI: $requestUri, Method: $requestMethod");

// Health Check endpoint
if ($requestMethod === 'GET' && ($requestUri === '/health' || $requestUri === '/health/')) {
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    
    $status = [
        'status' => 'ok',
        'php_version' => PHP_VERSION,
        'server_type' => 'PHP Development Server',
        'request_uri' => $requestUri,
        'config' => file_exists(__DIR__ . '/../config/config.php'),
        'cert' => file_exists(__DIR__ . '/../certs/certificado.crt'),
        'key' => file_exists(__DIR__ . '/../certs/rehabilitarte.key'),
        'logs_writable' => is_writable(__DIR__ . '/../logs/'),
        'facturas_writable' => is_writable(__DIR__ . '/facturas/'),
        'vendor_exists' => file_exists(__DIR__ . '/../vendor/autoload.php'),
    ];
    
    $all_ok = $status['config'] && $status['cert'] && $status['key'] && $status['logs_writable'] && $status['facturas_writable'] && $status['vendor_exists'];
    $status['status'] = $all_ok ? 'ok' : 'error';
    $status['all_checks_passed'] = $all_ok;
    
    http_response_code($all_ok ? 200 : 500);
    echo json_encode($status, JSON_PRETTY_PRINT);
    exit;
}

// OPTIONS request para CORS
if ($requestMethod === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Content-Length: 0');
    http_response_code(200);
    exit;
}

// Endpoint principal para crear facturas
if ($requestMethod === 'POST' && ($requestUri === '/' || $requestUri === '')) {
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if ($input === null && json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'JSON inválido: ' . json_last_error_msg()]);
        exit;
    }
    
    try {
        $afip = new AfipWs(CUIT_EMISOR, CERT_PATH, KEY_PATH);

        // Consultar facturador
        try {
            $facturadorData = $afip->consultarCUIT('27280873301');
        } catch (\Exception $e) {
            file_put_contents(LOG_DIR . 'facturador_error.log', "Error al consultar facturador (27280873301): " . $e->getMessage());
            $facturadorData = ['error' => $e->getMessage()];
        }

        // Consultar facturado
        try {
            $facturadoData = $afip->consultarCUIT($input['facCuit']);
        } catch (\Exception $e) {
            file_put_contents(LOG_DIR . 'facturado_error.log', "Error al consultar facturado ({$input['facCuit']}): " . $e->getMessage());
            $facturadoData = ['error' => $e->getMessage()];
        }

        $lastCMP = $afip->getLastCMP((int)$input['PtoVta'], (int)$input['TipoComp']);
        $nro = $lastCMP + 1;

        $facturaData = [
            'PtoVta' => (int)$input['PtoVta'],
            'TipoComp' => (int)$input['TipoComp'],
            'facCuit' => $input['facCuit'],
            'nro' => $nro,
            'FechaComp' => $input['FechaComp'],
            'facTotal' => (float)$input['facTotal'],
            'facPeriodo_inicio' => $input['facPeriodo_inicio'],
            'facPeriodo_fin' => $input['facPeriodo_fin'],
            'fechaUltimoDia' => $input['fechaUltimoDia'],
            'facturador' => $facturadorData,
            'facturado' => $facturadoData
        ];

        $result = $afip->crearFactura($facturaData);
        $pdf = new FacturaPDF();
        $facturaData['CAE'] = $result['CAE'];
        $facturaData['Vencimiento'] = $result['Vencimiento'];
        $pdfFilename = $pdf->generarFactura($facturaData);

        $baseUrl = 'http://' . $_SERVER['HTTP_HOST'] . '/facturas/';
        $downloadLink = $baseUrl . $pdfFilename;

        echo json_encode([
            'success' => true,
            'nro' => $nro,
            'CAE' => $result['CAE'],
            'Vencimiento' => $result['Vencimiento'],
            'pdfFilename' => $pdfFilename,
            'downloadLink' => $downloadLink,
            'facturador' => $facturadorData,
            'facturado' => $facturadoData
        ], JSON_PRETTY_PRINT);
    } catch (\Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_PRETTY_PRINT);
    }
} else {
    // Endpoint no encontrado o método no permitido
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    
    if ($requestMethod !== 'GET' && $requestMethod !== 'POST') {
        http_response_code(405);
        echo json_encode([
            'error' => 'Método no permitido',
            'allowed_methods' => ['GET', 'POST'],
            'request_method' => $requestMethod,
            'request_uri' => $requestUri
        ], JSON_PRETTY_PRINT);
    } else {
        http_response_code(404);
        echo json_encode([
            'error' => 'Endpoint no encontrado',
            'available_endpoints' => [
                'GET /health' => 'Health check del sistema',
                'POST /' => 'Crear factura electrónica'
            ],
            'request_uri' => $requestUri,
            'request_method' => $requestMethod
        ], JSON_PRETTY_PRINT);
    }
}