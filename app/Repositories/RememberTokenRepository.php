<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

/**
 * Persistencia de tokens "recuérdame" usando el patrón selector/validador:
 * el selector identifica la fila (búsqueda directa, sin filtrar por secreto),
 * y el validador se compara únicamente en memoria mediante hash_equals()
 * contra su hash almacenado. Esto evita timing attacks y permite revocar
 * o rotar tokens individualmente sin exponer el secreto en la base de datos.
 */
final class RememberTokenRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function create(int $userId, string $selector, string $validatorHash, string $expiresAt): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO remember_tokens (user_id, selector, validator_hash, expires_at, created_at)
             VALUES (:user_id, :selector, :validator_hash, :expires_at, NOW())'
        );
        $stmt->execute([
            'user_id' => $userId,
            'selector' => $selector,
            'validator_hash' => $validatorHash,
            'expires_at' => $expiresAt,
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findValidBySelector(string $selector): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM remember_tokens WHERE selector = :selector AND expires_at > NOW() LIMIT 1'
        );
        $stmt->execute(['selector' => $selector]);

        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function updateValidator(int $id, string $validatorHash, string $expiresAt): void
    {
        $stmt = $this->db->prepare(
            'UPDATE remember_tokens SET validator_hash = :validator_hash, expires_at = :expires_at WHERE id = :id'
        );
        $stmt->execute([
            'validator_hash' => $validatorHash,
            'expires_at' => $expiresAt,
            'id' => $id,
        ]);
    }

    public function deleteBySelector(string $selector): void
    {
        $stmt = $this->db->prepare('DELETE FROM remember_tokens WHERE selector = :selector');
        $stmt->execute(['selector' => $selector]);
    }

    /**
     * Revoca todos los tokens de un usuario. Se usa como medida de contención si
     * se detecta el reuso de un validador ya rotado (posible robo de cookie).
     */
    public function deleteAllForUser(int $userId): void
    {
        $stmt = $this->db->prepare('DELETE FROM remember_tokens WHERE user_id = :user_id');
        $stmt->execute(['user_id' => $userId]);
    }
}
