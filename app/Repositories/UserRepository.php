<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class UserRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);

        $user = $stmt->fetch();

        return $user ?: null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);

        $user = $stmt->fetch();

        return $user ?: null;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): int
    {
        $sql = 'INSERT INTO users (uuid, name, email, password_hash, role, privacy_accepted_at, created_at, updated_at)
                VALUES (:uuid, :name, :email, :password_hash, :role, :privacy_accepted_at, NOW(), NOW())';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'uuid' => $data['uuid'],
            'name' => $data['name'],
            'email' => $data['email'],
            'password_hash' => $data['password_hash'],
            'role' => $data['role'] ?? 'user',
            'privacy_accepted_at' => $data['privacy_accepted_at'],
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE users SET name = :name, email = :email, pronouns = :pronouns,
             professional_title = :professional_title, age = :age, updated_at = NOW()
             WHERE id = :id'
        );

        return $stmt->execute([
            'id' => $id,
            'name' => $data['name'],
            'email' => $data['email'],
            'pronouns' => $data['pronouns'] ?? null,
            'professional_title' => $data['professional_title'] ?? null,
            'age' => $data['age'] ?? null,
        ]);
    }

    public function updateLastLogin(int $id): void
    {
        $stmt = $this->db->prepare('UPDATE users SET last_login_at = NOW() WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function updatePasswordHash(int $id, string $passwordHash): void
    {
        $stmt = $this->db->prepare('UPDATE users SET password_hash = :password_hash, updated_at = NOW() WHERE id = :id');
        $stmt->execute(['id' => $id, 'password_hash' => $passwordHash]);
    }

    public function markEmailVerified(int $id): void
    {
        $stmt = $this->db->prepare('UPDATE users SET email_verified_at = NOW() WHERE id = :id AND email_verified_at IS NULL');
        $stmt->execute(['id' => $id]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function all(int $limit = 100, int $offset = 0): array
    {
        $stmt = $this->db->prepare('SELECT id, uuid, name, email, role, is_active, email_verified_at, created_at, last_login_at FROM users ORDER BY created_at DESC LIMIT :limit OFFSET :offset');
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function count(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM users')->fetchColumn();
    }
}
