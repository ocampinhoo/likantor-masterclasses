<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class EmailLogRepository
{
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
            'INSERT INTO email_logs (user_id, template_slug, to_email, sendgrid_message_id, status, created_at)
             VALUES (:user_id, :template_slug, :to_email, :sendgrid_message_id, :status, NOW())'
        );

        $stmt->execute([
            'user_id' => $data['user_id'] ?? null,
            'template_slug' => $data['template_slug'],
            'to_email' => $data['to_email'],
            'sendgrid_message_id' => $data['sendgrid_message_id'] ?? null,
            'status' => $data['status'],
        ]);

        return (int) $this->db->lastInsertId();
    }
}
