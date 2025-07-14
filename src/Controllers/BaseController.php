<?php

namespace AfipApi\Controllers;

use AfipApi\Core\Client;
use AfipApi\Middleware\AuthMiddleware;
use Exception;

abstract class BaseController
{
    protected Client $client;

    public function __construct()
    {
        $this->setHeaders();
        $this->client = AuthMiddleware::authenticate();
    }

    protected function setHeaders(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key');
    }

    protected function handleOptions(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
    }

    protected function getJsonInput(): array
    {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
            $this->sendError('JSON inválido: ' . json_last_error_msg(), 400);
        }
        
        return $data ?? [];
    }

    protected function sendSuccess(array $data, int $code = 200): void
    {
        AuthMiddleware::logRequest(
            $this->client,
            $_SERVER['REQUEST_URI'] ?? '/',
            $_SERVER['REQUEST_METHOD'] ?? 'GET',
            $this->getJsonInput(),
            $data,
            $code
        );
        
        http_response_code($code);
        echo json_encode([
            'success' => true,
            'data' => $data,
            'timestamp' => date('c'),
            'client' => $this->client->getName()
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    protected function sendError(string $message, int $code = 400, array $details = []): void
    {
        $errorData = [
            'success' => false,
            'error' => $message,
            'code' => $code,
            'timestamp' => date('c')
        ];
        
        if (!empty($details)) {
            $errorData['details'] = $details;
        }
        
        AuthMiddleware::logRequest(
            $this->client,
            $_SERVER['REQUEST_URI'] ?? '/',
            $_SERVER['REQUEST_METHOD'] ?? 'GET',
            $this->getJsonInput(),
            $errorData,
            $code
        );
        
        http_response_code($code);
        echo json_encode($errorData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    protected function validateRequired(array $data, array $required): void
    {
        $missing = [];
        foreach ($required as $field) {
            if (!isset($data[$field]) || $data[$field] === '' || $data[$field] === null) {
                $missing[] = $field;
            }
        }
        
        if (!empty($missing)) {
            $this->sendError('Campos requeridos faltantes: ' . implode(', ', $missing), 400);
        }
    }

    protected function validateMethod(string $expectedMethod): void
    {
        $actualMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if ($actualMethod !== $expectedMethod) {
            $this->sendError("Método $actualMethod no permitido. Se esperaba $expectedMethod", 405);
        }
    }

    protected function logInfo(string $message): void
    {
        $logFile = __DIR__ . "/../../logs/api_{$this->client->getCuit()}.log";
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[$timestamp] [INFO] $message" . PHP_EOL;
        @file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }

    protected function logError(string $message, Exception $e = null): void
    {
        $logFile = __DIR__ . "/../../logs/api_error_{$this->client->getCuit()}.log";
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[$timestamp] [ERROR] $message";
        
        if ($e) {
            $logEntry .= " - Exception: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine();
        }
        
        $logEntry .= PHP_EOL;
        @file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
} 