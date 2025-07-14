#!/usr/bin/env php
<?php
/**
 * Script para gestionar clientes en el sistema multi-tenant
 * 
 * Uso:
 * php bin/client-manager.php create --name="Cliente Test" --cuit="20123456789" --email="test@test.com"
 * php bin/client-manager.php list
 * php bin/client-manager.php show --cuit="20123456789"
 * php bin/client-manager.php regenerate-key --cuit="20123456789"
 * php bin/client-manager.php update-certs --cuit="20123456789" --cert="/path/to/cert.crt" --key="/path/to/key.key"
 */

require_once __DIR__ . '/../vendor/autoload.php';

use AfipApi\Core\Database;
use AfipApi\Core\Client;

// Inicializar base de datos
Database::initialize();

// Procesar argumentos de línea de comandos
$action = $argv[1] ?? null;
$options = [];

// Parsear opciones
for ($i = 2; $i < count($argv); $i++) {
    if (strpos($argv[$i], '--') === 0) {
        $option = substr($argv[$i], 2);
        if (strpos($option, '=') !== false) {
            [$key, $value] = explode('=', $option, 2);
            $options[$key] = $value;
        } else {
            $options[$option] = true;
        }
    }
}

function showUsage(): void
{
    echo "Uso del script de gestión de clientes:\n\n";
    echo "Comandos disponibles:\n";
    echo "  create    Crear un nuevo cliente\n";
    echo "  list      Listar todos los clientes\n";
    echo "  show      Mostrar detalles de un cliente\n";
    echo "  regenerate-key  Regenerar API key de un cliente\n";
    echo "  update-certs    Actualizar certificados de un cliente\n";
    echo "  activate  Activar un cliente\n";
    echo "  deactivate  Desactivar un cliente\n\n";
    
    echo "Ejemplos:\n";
    echo "  php bin/client-manager.php create --name=\"Cliente Test\" --cuit=\"20123456789\" --email=\"test@test.com\"\n";
    echo "  php bin/client-manager.php list\n";
    echo "  php bin/client-manager.php show --cuit=\"20123456789\"\n";
    echo "  php bin/client-manager.php regenerate-key --cuit=\"20123456789\"\n";
    echo "  php bin/client-manager.php update-certs --cuit=\"20123456789\" --cert=\"/path/to/cert.crt\" --key=\"/path/to/key.key\"\n";
    echo "  php bin/client-manager.php activate --cuit=\"20123456789\"\n";
    echo "  php bin/client-manager.php deactivate --cuit=\"20123456789\"\n";
}

function createClient(array $options): void
{
    if (!isset($options['name']) || !isset($options['cuit'])) {
        echo "Error: name y cuit son requeridos para crear un cliente\n";
        return;
    }

    // Validar CUIT
    $cuit = preg_replace('/[^0-9]/', '', $options['cuit']);
    if (strlen($cuit) !== 11) {
        echo "Error: CUIT debe tener 11 dígitos\n";
        return;
    }

    // Verificar si ya existe
    $existing = Client::findByCuit($cuit);
    if ($existing) {
        echo "Error: Ya existe un cliente con CUIT $cuit\n";
        return;
    }

    $client = new Client();
    $client->setName($options['name']);
    $client->setCuit($cuit);
    $client->setEmail($options['email'] ?? null);
    $client->setEnvironment($options['environment'] ?? 'prod');

    if ($client->save()) {
        echo "Cliente creado exitosamente:\n";
        echo "  UUID: " . $client->getUuid() . "\n";
        echo "  Nombre: " . $client->getName() . "\n";
        echo "  CUIT: " . $client->getCuit() . "\n";
        echo "  Email: " . ($client->getEmail() ?? 'N/A') . "\n";
        echo "  API Key: " . $client->getApiKey() . "\n";
        echo "  Ambiente: " . $client->getEnvironment() . "\n";
    } else {
        echo "Error: No se pudo crear el cliente\n";
    }
}

function listClients(): void
{
    $pdo = Database::getConnection();
    $stmt = $pdo->query('SELECT * FROM clients ORDER BY created_at DESC');
    $clients = $stmt->fetchAll();

    if (empty($clients)) {
        echo "No hay clientes registrados\n";
        return;
    }

    printf("%-36s %-20s %-12s %-10s %-8s %-12s\n", 
           'UUID', 'Nombre', 'CUIT', 'Estado', 'Ambiente', 'Creado');
    echo str_repeat('-', 100) . "\n";

    foreach ($clients as $clientData) {
        $client = new Client($clientData);
        printf("%-36s %-20s %-12s %-10s %-8s %-12s\n",
               substr($client->getUuid(), 0, 8) . '...',
               substr($client->getName(), 0, 20),
               $client->getCuit(),
               $client->getStatus(),
               $client->getEnvironment(),
               date('Y-m-d', strtotime($client->getCreatedAt()))
        );
    }
}

function showClient(array $options): void
{
    if (!isset($options['cuit'])) {
        echo "Error: cuit es requerido\n";
        return;
    }

    $cuit = preg_replace('/[^0-9]/', '', $options['cuit']);
    $client = Client::findByCuit($cuit);

    if (!$client) {
        echo "Error: Cliente con CUIT $cuit no encontrado\n";
        return;
    }

    echo "Detalles del cliente:\n";
    echo "  UUID: " . $client->getUuid() . "\n";
    echo "  Nombre: " . $client->getName() . "\n";
    echo "  CUIT: " . $client->getCuit() . "\n";
    echo "  Email: " . ($client->getEmail() ?? 'N/A') . "\n";
    echo "  Estado: " . $client->getStatus() . "\n";
    echo "  Ambiente: " . $client->getEnvironment() . "\n";
    echo "  API Key: " . $client->getApiKey() . "\n";
    echo "  Certificado: " . ($client->getCertificatePath() ?? 'N/A') . "\n";
    echo "  Clave privada: " . ($client->getPrivateKeyPath() ?? 'N/A') . "\n";
    echo "  Certificados válidos: " . ($client->hasCertificates() ? 'Sí' : 'No') . "\n";
    echo "  Creado: " . $client->getCreatedAt() . "\n";
    echo "  Actualizado: " . $client->getUpdatedAt() . "\n";
}

function regenerateApiKey(array $options): void
{
    if (!isset($options['cuit'])) {
        echo "Error: cuit es requerido\n";
        return;
    }

    $cuit = preg_replace('/[^0-9]/', '', $options['cuit']);
    $client = Client::findByCuit($cuit);

    if (!$client) {
        echo "Error: Cliente con CUIT $cuit no encontrado\n";
        return;
    }

    $oldKey = $client->getApiKey();
    $newKey = $client->regenerateApiKey();

    if ($client->save()) {
        echo "API Key regenerada exitosamente:\n";
        echo "  API Key anterior: $oldKey\n";
        echo "  Nueva API Key: $newKey\n";
        echo "  IMPORTANTE: Actualiza tus aplicaciones con la nueva API Key\n";
    } else {
        echo "Error: No se pudo regenerar la API Key\n";
    }
}

function updateCertificates(array $options): void
{
    if (!isset($options['cuit']) || !isset($options['cert']) || !isset($options['key'])) {
        echo "Error: cuit, cert y key son requeridos\n";
        return;
    }

    $cuit = preg_replace('/[^0-9]/', '', $options['cuit']);
    $client = Client::findByCuit($cuit);

    if (!$client) {
        echo "Error: Cliente con CUIT $cuit no encontrado\n";
        return;
    }

    $certPath = $options['cert'];
    $keyPath = $options['key'];

    // Verificar que los archivos existen
    if (!file_exists($certPath)) {
        echo "Error: Archivo de certificado no encontrado: $certPath\n";
        return;
    }

    if (!file_exists($keyPath)) {
        echo "Error: Archivo de clave privada no encontrado: $keyPath\n";
        return;
    }

    $client->setCertificatePath($certPath);
    $client->setPrivateKeyPath($keyPath);

    if ($client->save()) {
        echo "Certificados actualizados exitosamente:\n";
        echo "  Certificado: $certPath\n";
        echo "  Clave privada: $keyPath\n";
        echo "  Certificados válidos: " . ($client->hasCertificates() ? 'Sí' : 'No') . "\n";
    } else {
        echo "Error: No se pudieron actualizar los certificados\n";
    }
}

function activateClient(array $options): void
{
    updateClientStatus($options, 'active');
}

function deactivateClient(array $options): void
{
    updateClientStatus($options, 'inactive');
}

function updateClientStatus(array $options, string $status): void
{
    if (!isset($options['cuit'])) {
        echo "Error: cuit es requerido\n";
        return;
    }

    $cuit = preg_replace('/[^0-9]/', '', $options['cuit']);
    $client = Client::findByCuit($cuit);

    if (!$client) {
        echo "Error: Cliente con CUIT $cuit no encontrado\n";
        return;
    }

    $oldStatus = $client->getStatus();
    $client->setStatus($status);

    if ($client->save()) {
        echo "Estado del cliente actualizado:\n";
        echo "  Estado anterior: $oldStatus\n";
        echo "  Nuevo estado: $status\n";
    } else {
        echo "Error: No se pudo actualizar el estado del cliente\n";
    }
}

// Ejecutar acción
try {
    switch ($action) {
        case 'create':
            createClient($options);
            break;
        case 'list':
            listClients();
            break;
        case 'show':
            showClient($options);
            break;
        case 'regenerate-key':
            regenerateApiKey($options);
            break;
        case 'update-certs':
            updateCertificates($options);
            break;
        case 'activate':
            activateClient($options);
            break;
        case 'deactivate':
            deactivateClient($options);
            break;
        case null:
        case 'help':
        case '--help':
        case '-h':
            showUsage();
            break;
        default:
            echo "Acción desconocida: $action\n\n";
            showUsage();
            exit(1);
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
} 