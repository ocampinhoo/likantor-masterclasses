<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class EmailVerificationTokenRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function create(int $userId, string $tokenHash, string $expiresAt): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO email_verification_tokens (user_id, token_hash, expires_at, created_at)
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
     * @return array<string, mixed>|null
     */
    public function findValidByHash(string $tokenHash): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM email_verification_tokens
             WHERE token_hash = :token_hash AND used_at IS NULL AND expires_at > NOW()
             LIMIT 1'
        );
        $stmt->execute(['token_hash' => $tokenHash]);

        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function markUsed(int $id): void
    {
        $stmt = $this->db->prepare('UPDATE email_verification_tokens SET used_at = NOW() WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function invalidateAllForUser(int $userId): void
    {
        $stmt = $this->db->prepare(
            'UPDATE email_verification_tokens SET used_at = NOW() WHERE user_id = :user_id AND used_at IS NULL'
        );
        $stmt->execute(['user_id' => $userId]);
    }
}
