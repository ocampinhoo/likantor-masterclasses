<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class MasterclassRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function published(): array
    {
        $stmt = $this->db->query(
            "SELECT * FROM masterclasses
             WHERE status IN ('published', 'registration_closed', 'live', 'completed')
             AND published_at IS NOT NULL
             ORDER BY event_starts_at ASC"
        );

        return $stmt->fetchAll();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findBySlug(string $slug): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM masterclasses WHERE slug = :slug LIMIT 1');
        $stmt->execute(['slug' => $slug]);

        $row = $stmt->fetch();

        return $row ?: null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM masterclasses WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);

        $row = $stmt->fetch();

        return $row ?: null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function all(): array
    {
        $stmt = $this->db->query('SELECT * FROM masterclasses ORDER BY event_starts_at DESC');

        return $stmt->fetchAll();
    }

    public function count(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM masterclasses')->fetchColumn();
    }
}
