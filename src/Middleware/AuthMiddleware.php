<?php

namespace AfipApi\Middleware;

use AfipApi\Core\Client;
use Exception;

class AuthMiddleware
{
    public static function authenticate(): Client
    {
        $apiKey = self::extractApiKey();
        
        if (!$apiKey) {
            self::sendUnauthorized('API key requerida');
        }

        $client = Client::findByApiKey($apiKey);
        
        if (!$client) {
            self::sendUnauthorized('API key inválida');
        }

        if (!$client->isActive()) {
            self::sendUnauthorized('Cliente inactivo');
        }

        if (!$client->hasCertificates()) {
            self::sendError('Certificados no configurados para este cliente', 400);
        }

        return $client;
    }

    private static function extractApiKey(): ?string
    {
        // Buscar en header Authorization
        $headers = getallheaders();
        if (isset($headers['Authorization'])) {
            $auth = $headers['Authorization'];
            if (preg_match('/Bearer\s+(.*)$/i', $auth, $matches)) {
                return $matches[1];
            }
        }

        // Buscar en header X-API-Key
        if (isset($headers['X-API-Key'])) {
            return $headers['X-API-Key'];
        }

        // Buscar en query parameter
        if (isset($_GET['api_key'])) {
            return $_GET['api_key'];
        }

        return null;
    }

    private static function sendUnauthorized(string $message): void
    {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => $message,
            'code' => 'UNAUTHORIZED'
        ], JSON_PRETTY_PRINT);
        exit;
    }

    private static function sendError(string $message, int $code = 400): void
    {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => $message,
            'code' => 'CLIENT_ERROR'
        ], JSON_PRETTY_PRINT);
        exit;
    }

    public static function logRequest(Client $client, string $endpoint, string $method, array $requestData = [], array $responseData = [], int $statusCode = 200): void
    {
        try {
            $pdo = \AfipApi\Core\Database::getConnection();
            $stmt = $pdo->prepare("
                INSERT INTO api_logs (client_id, endpoint, method, request_data, response_data, status_code, ip_address, user_agent) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $client->getId(),
                $endpoint,
                $method,
                json_encode($requestData),
                json_encode($responseData),
                $statusCode,
                $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]);
        } catch (Exception $e) {
            // Log error pero no interrumpir la ejecución
            error_log("Error logging API request: " . $e->getMessage());
        }
    }
} 