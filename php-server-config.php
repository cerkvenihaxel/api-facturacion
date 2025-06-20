<?php
/**
 * Configuración para el servidor de desarrollo de PHP
 * 
 * Para iniciar el servidor:
 * php -S localhost:8000 -t public php-server-config.php
 * 
 * O simplemente:
 * cd public && php -S localhost:8000
 */

// Configurar zona horaria
date_default_timezone_set('America/Argentina/Buenos_Aires');

// Configurar manejo de errores para desarrollo
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/php_errors.log');

// Configurar límites de memoria y tiempo
ini_set('memory_limit', '256M');
ini_set('max_execution_time', 300);

// Función para manejar rutas en el servidor de desarrollo
function handleRequest($uri) {
    // Si es un archivo existente, servirlo directamente
    if (is_file(__DIR__ . $uri)) {
        return false; // Dejar que PHP sirva el archivo
    }
    
    // Si es un directorio existente, buscar index.php
    if (is_dir(__DIR__ . $uri)) {
        $indexFile = __DIR__ . $uri . '/index.php';
        if (is_file($indexFile)) {
            return $indexFile;
        }
    }
    
    // Para todas las demás rutas, usar index.php
    return __DIR__ . '/index.php';
}

// Obtener la URI de la petición
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Manejar la ruta
$file = handleRequest($uri);

if ($file === false) {
    // Dejar que PHP maneje archivos estáticos
    return false;
} else {
    // Incluir el archivo de la aplicación
    include $file;
} 