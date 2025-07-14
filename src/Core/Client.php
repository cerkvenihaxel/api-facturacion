<?php

namespace AfipApi\Core;

use PDO;
use Exception;
use Ramsey\Uuid\Uuid;

class Client
{
    private int $id;
    private string $uuid;
    private string $name;
    private string $cuit;
    private ?string $email;
    private string $apiKey;
    private string $status;
    private ?string $certificatePath;
    private ?string $privateKeyPath;
    private string $environment;
    private ?string $createdAt;
    private ?string $updatedAt;

    public function __construct(array $data = [])
    {
        if (!empty($data)) {
            $this->hydrate($data);
        }
    }

    public function hydrate(array $data): void
    {
        $this->id = $data['id'] ?? 0;
        $this->uuid = $data['uuid'] ?? '';
        $this->name = $data['name'] ?? '';
        $this->cuit = $data['cuit'] ?? '';
        $this->email = $data['email'] ?? null;
        $this->apiKey = $data['api_key'] ?? '';
        $this->status = $data['status'] ?? 'active';
        $this->certificatePath = $data['certificate_path'] ?? null;
        $this->privateKeyPath = $data['private_key_path'] ?? null;
        $this->environment = $data['environment'] ?? 'prod';
        $this->createdAt = $data['created_at'] ?? null;
        $this->updatedAt = $data['updated_at'] ?? null;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'cuit' => $this->cuit,
            'email' => $this->email,
            'api_key' => $this->apiKey,
            'status' => $this->status,
            'certificate_path' => $this->certificatePath,
            'private_key_path' => $this->privateKeyPath,
            'environment' => $this->environment,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt
        ];
    }

    public static function findByApiKey(string $apiKey): ?self
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM clients WHERE api_key = ? AND status = "active"');
        $stmt->execute([$apiKey]);
        $data = $stmt->fetch();

        return $data ? new self($data) : null;
    }

    public static function findByUuid(string $uuid): ?self
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM clients WHERE uuid = ?');
        $stmt->execute([$uuid]);
        $data = $stmt->fetch();

        return $data ? new self($data) : null;
    }

    public static function findByCuit(string $cuit): ?self
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM clients WHERE cuit = ?');
        $stmt->execute([$cuit]);
        $data = $stmt->fetch();

        return $data ? new self($data) : null;
    }

    public function save(): bool
    {
        $pdo = Database::getConnection();
        
        if ($this->id) {
            return $this->update($pdo);
        } else {
            return $this->create($pdo);
        }
    }

    private function create(PDO $pdo): bool
    {
        $this->uuid = Uuid::uuid4()->toString();
        $this->apiKey = $this->generateApiKey();
        $this->createdAt = date('Y-m-d H:i:s');
        $this->updatedAt = $this->createdAt;

        $sql = "INSERT INTO clients (uuid, name, cuit, email, api_key, status, certificate_path, private_key_path, environment, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            $this->uuid,
            $this->name,
            $this->cuit,
            $this->email,
            $this->apiKey,
            $this->status,
            $this->certificatePath,
            $this->privateKeyPath,
            $this->environment,
            $this->createdAt,
            $this->updatedAt
        ]);

        if ($result) {
            $this->id = $pdo->lastInsertId();
        }

        return $result;
    }

    private function update(PDO $pdo): bool
    {
        $this->updatedAt = date('Y-m-d H:i:s');

        $sql = "UPDATE clients SET name = ?, cuit = ?, email = ?, status = ?, certificate_path = ?, private_key_path = ?, environment = ?, updated_at = ? WHERE id = ?";
        
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            $this->name,
            $this->cuit,
            $this->email,
            $this->status,
            $this->certificatePath,
            $this->privateKeyPath,
            $this->environment,
            $this->updatedAt,
            $this->id
        ]);
    }

    private function generateApiKey(): string
    {
        return 'ak_' . bin2hex(random_bytes(32));
    }

    public function regenerateApiKey(): string
    {
        $this->apiKey = $this->generateApiKey();
        return $this->apiKey;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function hasCertificates(): bool
    {
        return !empty($this->certificatePath) && !empty($this->privateKeyPath) &&
               file_exists($this->certificatePath) && file_exists($this->privateKeyPath);
    }

    // Getters
    public function getId(): int { return $this->id; }
    public function getUuid(): string { return $this->uuid; }
    public function getName(): string { return $this->name; }
    public function getCuit(): string { return $this->cuit; }
    public function getEmail(): ?string { return $this->email; }
    public function getApiKey(): string { return $this->apiKey; }
    public function getStatus(): string { return $this->status; }
    public function getCertificatePath(): ?string { return $this->certificatePath; }
    public function getPrivateKeyPath(): ?string { return $this->privateKeyPath; }
    public function getEnvironment(): string { return $this->environment; }
    public function getCreatedAt(): ?string { return $this->createdAt; }
    public function getUpdatedAt(): ?string { return $this->updatedAt; }

    // Setters
    public function setName(string $name): void { $this->name = $name; }
    public function setCuit(string $cuit): void { $this->cuit = $cuit; }
    public function setEmail(?string $email): void { $this->email = $email; }
    public function setStatus(string $status): void { $this->status = $status; }
    public function setCertificatePath(?string $certificatePath): void { $this->certificatePath = $certificatePath; }
    public function setPrivateKeyPath(?string $privateKeyPath): void { $this->privateKeyPath = $privateKeyPath; }
    public function setEnvironment(string $environment): void { $this->environment = $environment; }
} 