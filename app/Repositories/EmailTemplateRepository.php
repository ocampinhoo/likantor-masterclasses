<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class EmailTemplateRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findBySlug(string $slug): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM email_templates WHERE slug = :slug LIMIT 1');
        $stmt->execute(['slug' => $slug]);

        $row = $stmt->fetch();

        return $row ?: null;
    }
}
