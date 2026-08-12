<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class PasswordResetTokenRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function create(int $userId, string $tokenHash, string $expiresAt): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO password_reset_tokens (user_id, token_hash, expires_at, created_at)
             VALUES (:user_id, :token_hash, :expires_at, NOW())'
        );
        $stmt->execute([
            'user_id' => $userId,
            'token_hash' => $tokenHash,
            'expires_at' => $expiresAt,
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Devuelve el token únicamente si existe, no ha sido usado y no ha expirado.
     *
     * @return array<string, mixed>|null
     */
    public function findValidByHash(string $tokenHash): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM password_reset_tokens
             WHERE token_hash = :token_hash AND used_at IS NULL AND expires_at > NOW()
             LIMIT 1'
        );
        $stmt->execute(['token_hash' => $tokenHash]);

        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function markUsed(int $id): void
    {
        $stmt = $this->db->prepare('UPDATE password_reset_tokens SET used_at = NOW() WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    /**
     * Invalida todos los tokens vigentes de un usuario (por ejemplo, al emitir uno nuevo
     * o después de un cambio de contraseña exitoso).
     */
    public function invalidateAllForUser(int $userId): void
    {
        $stmt = $this->db->prepare(
            'UPDATE password_reset_tokens SET used_at = NOW() WHERE user_id = :user_id AND used_at IS NULL'
        );
        $stmt->execute(['user_id' => $userId]);
    }
}
