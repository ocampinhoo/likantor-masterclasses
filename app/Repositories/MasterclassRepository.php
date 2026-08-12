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

    /**
     * Quita campos de acceso Zoom/grabación antes de enviar la Masterclass a vistas públicas.
     *
     * @param array<string, mixed> $masterclass
     * @return array<string, mixed>
     */
    public function withoutSensitiveAccessFields(array $masterclass): array
    {
        unset(
            $masterclass['zoom_meeting_url'],
            $masterclass['zoom_meeting_id'],
            $masterclass['zoom_passcode'],
            $masterclass['recording_url']
        );

        return $masterclass;
    }

    /**
     * @param array<int, array<string, mixed>> $masterclasses
     * @return array<int, array<string, mixed>>
     */
    public function mapWithoutSensitiveAccessFields(array $masterclasses): array
    {
        return array_map([$this, 'withoutSensitiveAccessFields'], $masterclasses);
    }

    /**
     * Actualiza únicamente los campos de acceso Zoom de una Masterclass existente.
     *
     * @param array{zoom_meeting_url:?string, zoom_meeting_id:?string, zoom_passcode:?string} $data
     */
    public function updateZoomAccess(int $id, array $data): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE masterclasses SET
                zoom_meeting_url = :zoom_meeting_url,
                zoom_meeting_id = :zoom_meeting_id,
                zoom_passcode = :zoom_passcode,
                updated_at = NOW()
             WHERE id = :id'
        );

        $stmt->execute([
            'zoom_meeting_url' => $data['zoom_meeting_url'],
            'zoom_meeting_id' => $data['zoom_meeting_id'],
            'zoom_passcode' => $data['zoom_passcode'],
            'id' => $id,
        ]);

        return $stmt->rowCount() > 0 || $this->findById($id) !== null;
    }
}
