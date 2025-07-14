<?php

namespace AfipApi\Core;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $instance = null;
    private static array $config;

    public static function initialize(array $config = null): void
    {
        if ($config === null) {
            self::$config = require __DIR__ . '/../../config/database.php';
        } else {
            self::$config = $config;
        }
    }

    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            if (!isset(self::$config)) {
                self::initialize();
            }

            try {
                $dsn = 'sqlite:' . self::$config['path'];
                self::$instance = new PDO($dsn, null, null, self::$config['options']);
                self::createTables();
            } catch (PDOException $e) {
                throw new \Exception("Error al conectar con la base de datos: " . $e->getMessage());
            }
        }

        return self::$instance;
    }

    private static function createTables(): void
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS clients (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                uuid VARCHAR(36) UNIQUE NOT NULL,
                name VARCHAR(255) NOT NULL,
                cuit VARCHAR(11) UNIQUE NOT NULL,
                email VARCHAR(255),
                api_key VARCHAR(255) UNIQUE NOT NULL,
                status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
                certificate_path VARCHAR(500),
                private_key_path VARCHAR(500),
                environment ENUM('prod', 'homo') DEFAULT 'prod',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS client_configurations (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                client_id INTEGER NOT NULL,
                config_key VARCHAR(100) NOT NULL,
                config_value TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
                UNIQUE(client_id, config_key)
            );

            CREATE TABLE IF NOT EXISTS invoices (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                client_id INTEGER NOT NULL,
                uuid VARCHAR(36) UNIQUE NOT NULL,
                invoice_number INTEGER NOT NULL,
                point_of_sale INTEGER NOT NULL,
                document_type INTEGER NOT NULL,
                client_cuit VARCHAR(11) NOT NULL,
                invoice_date DATE NOT NULL,
                total_amount DECIMAL(15,2) NOT NULL,
                cae VARCHAR(20),
                cae_expiration DATE,
                pdf_filename VARCHAR(255),
                status ENUM('pending', 'authorized', 'rejected', 'cancelled') DEFAULT 'pending',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
            );

            CREATE TABLE IF NOT EXISTS invoice_items (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                invoice_id INTEGER NOT NULL,
                product_code VARCHAR(50),
                description TEXT NOT NULL,
                quantity DECIMAL(10,3) NOT NULL,
                unit_price DECIMAL(15,2) NOT NULL,
                subtotal DECIMAL(15,2) NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE
            );

            CREATE TABLE IF NOT EXISTS api_logs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                client_id INTEGER,
                endpoint VARCHAR(255),
                method VARCHAR(10),
                request_data TEXT,
                response_data TEXT,
                status_code INTEGER,
                ip_address VARCHAR(45),
                user_agent TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE SET NULL
            );

            CREATE INDEX IF NOT EXISTS idx_clients_uuid ON clients(uuid);
            CREATE INDEX IF NOT EXISTS idx_clients_api_key ON clients(api_key);
            CREATE INDEX IF NOT EXISTS idx_clients_cuit ON clients(cuit);
            CREATE INDEX IF NOT EXISTS idx_invoices_client_id ON invoices(client_id);
            CREATE INDEX IF NOT EXISTS idx_invoices_uuid ON invoices(uuid);
            CREATE INDEX IF NOT EXISTS idx_api_logs_client_id ON api_logs(client_id);
            CREATE INDEX IF NOT EXISTS idx_api_logs_created_at ON api_logs(created_at);
        ";

        self::$instance->exec($sql);
    }

    public static function beginTransaction(): bool
    {
        return self::getConnection()->beginTransaction();
    }

    public static function commit(): bool
    {
        return self::getConnection()->commit();
    }

    public static function rollback(): bool
    {
        return self::getConnection()->rollback();
    }
} 