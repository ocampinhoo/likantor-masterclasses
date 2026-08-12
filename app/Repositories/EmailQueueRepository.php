<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class EmailQueueRepository
{
    private const MAX_ATTEMPTS = 3;

    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO email_queue (template_slug, to_email, to_name, payload, scheduled_at, status, created_at)
             VALUES (:template_slug, :to_email, :to_name, :payload, :scheduled_at, "pending", NOW())'
        );

        $stmt->execute([
            'template_slug' => $data['template_slug'],
            'to_email' => $data['to_email'],
            'to_name' => $data['to_name'] ?? null,
            'payload' => json_encode($data['payload'] ?? [], JSON_UNESCAPED_UNICODE),
            'scheduled_at' => $data['scheduled_at'] ?? date('Y-m-d H:i:s'),
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM email_queue WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);

        $row = $stmt->fetch();

        return $row ?: null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function pending(int $limit = 20): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM email_queue WHERE status = "pending" AND scheduled_at <= NOW() ORDER BY scheduled_at ASC LIMIT :limit'
        );
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function markSent(int $id): void
    {
        $stmt = $this->db->prepare('UPDATE email_queue SET status = "sent", sent_at = NOW() WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    /**
     * Marca el intento como fallido. Si aún no se alcanza el máximo de reintentos,
     * el registro vuelve a quedar "pending" para que el cron lo reintente después.
     */
    public function markFailedOrRetry(int $id, string $error): void
    {
        $select = $this->db->prepare('SELECT attempts FROM email_queue WHERE id = :id LIMIT 1');
        $select->execute(['id' => $id]);
        $row = $select->fetch();

        $attempts = $row !== false ? ((int) $row['attempts'] + 1) : 1;
        $status = $attempts >= self::MAX_ATTEMPTS ? 'failed' : 'pending';

        $update = $this->db->prepare(
            'UPDATE email_queue SET status = :status, attempts = :attempts, last_error = :error WHERE id = :id'
        );
        $update->execute([
            'status' => $status,
            'attempts' => $attempts,
            'error' => mb_substr($error, 0, 2000),
            'id' => $id,
        ]);
    }
}
