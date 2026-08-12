<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class LeadRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    /**
     * Inserta un lead nuevo o actualiza el existente (deduplicado por email).
     *
     * @param array<string, mixed> $data
     * @return int ID del lead (nuevo o existente)
     */
    public function upsert(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO leads (
                name, email, source, campaign, utm_source, utm_medium, utm_campaign, utm_content, utm_term,
                masterclass_id, ip_address, user_agent, privacy_accepted_at, created_at, updated_at
            ) VALUES (
                :name, :email, :source, :campaign, :utm_source, :utm_medium, :utm_campaign, :utm_content, :utm_term,
                :masterclass_id, :ip_address, :user_agent, :privacy_accepted_at, NOW(), NOW()
            )
            ON DUPLICATE KEY UPDATE
                id = LAST_INSERT_ID(id),
                name = VALUES(name),
                source = VALUES(source),
                campaign = COALESCE(VALUES(campaign), campaign),
                utm_source = COALESCE(VALUES(utm_source), utm_source),
                utm_medium = COALESCE(VALUES(utm_medium), utm_medium),
                utm_campaign = COALESCE(VALUES(utm_campaign), utm_campaign),
                utm_content = COALESCE(VALUES(utm_content), utm_content),
                utm_term = COALESCE(VALUES(utm_term), utm_term),
                masterclass_id = VALUES(masterclass_id),
                ip_address = VALUES(ip_address),
                user_agent = VALUES(user_agent),
                updated_at = NOW()'
        );

        $stmt->execute([
            'name' => $data['name'],
            'email' => $data['email'],
            'source' => $data['source'] ?? 'syllabus_form',
            'campaign' => $data['campaign'] ?? null,
            'utm_source' => $data['utm_source'] ?? null,
            'utm_medium' => $data['utm_medium'] ?? null,
            'utm_campaign' => $data['utm_campaign'] ?? null,
            'utm_content' => $data['utm_content'] ?? null,
            'utm_term' => $data['utm_term'] ?? null,
            'masterclass_id' => $data['masterclass_id'] ?? null,
            'ip_address' => $this->packIp($data['ip_address'] ?? null),
            'user_agent' => $data['user_agent'] ?? null,
            'privacy_accepted_at' => $data['privacy_accepted_at'],
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Registra cada envío de formulario como una interacción (histórico append-only).
     *
     * @param array<string, mixed> $data
     */
    public function logInteraction(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO lead_interactions (
                lead_id, masterclass_id, source, campaign, utm_source, utm_medium, utm_campaign, utm_content, utm_term,
                ip_address, user_agent, created_at
            ) VALUES (
                :lead_id, :masterclass_id, :source, :campaign, :utm_source, :utm_medium, :utm_campaign, :utm_content, :utm_term,
                :ip_address, :user_agent, NOW()
            )'
        );

        $stmt->execute([
            'lead_id' => $data['lead_id'],
            'masterclass_id' => $data['masterclass_id'] ?? null,
            'source' => $data['source'] ?? 'syllabus_form',
            'campaign' => $data['campaign'] ?? null,
            'utm_source' => $data['utm_source'] ?? null,
            'utm_medium' => $data['utm_medium'] ?? null,
            'utm_campaign' => $data['utm_campaign'] ?? null,
            'utm_content' => $data['utm_content'] ?? null,
            'utm_term' => $data['utm_term'] ?? null,
            'ip_address' => $this->packIp($data['ip_address'] ?? null),
            'user_agent' => $data['user_agent'] ?? null,
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM leads WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);

        $row = $stmt->fetch();

        return $row ?: null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function all(int $limit = 200): array
    {
        $stmt = $this->db->prepare(
            'SELECT l.*, m.name AS masterclass_name,
                (SELECT COUNT(*) FROM lead_interactions li WHERE li.lead_id = l.id) AS interactions_count
             FROM leads l
             LEFT JOIN masterclasses m ON m.id = l.masterclass_id
             ORDER BY l.created_at DESC
             LIMIT :limit'
        );
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function count(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM leads')->fetchColumn();
    }

    private function packIp(?string $ip): ?string
    {
        if ($ip === null || $ip === '') {
            return null;
        }

        $packed = @inet_pton($ip);

        return $packed !== false ? $packed : null;
    }
}
