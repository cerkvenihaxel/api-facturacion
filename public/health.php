<?php
header('Content-Type: application/json');

$status = [
    'status' => 'ok',
    'php_version' => PHP_VERSION,
    'config' => file_exists(__DIR__ . '/../config/config.php'),
    'cert' => file_exists(__DIR__ . '/../certs/certificado.crt'),
    'key' => file_exists(__DIR__ . '/../certs/rehabilitarte.key'),
    'logs_writable' => is_writable(__DIR__ . '/../logs/'),
    'facturas_writable' => is_writable(__DIR__ . '/facturas/'),
];

$all_ok = $status['config'] && $status['cert'] && $status['key'] && $status['logs_writable'] && $status['facturas_writable'];
$status['status'] = $all_ok ? 'ok' : 'error';

http_response_code($all_ok ? 200 : 500);
echo json_encode($status);
