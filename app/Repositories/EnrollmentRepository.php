<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class EnrollmentRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findByUserId(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT e.*,
                    m.name AS masterclass_name,
                    m.slug AS masterclass_slug,
                    m.event_starts_at,
                    m.timezone,
                    m.duration_minutes,
                    m.status AS masterclass_status,
                    CASE
                        WHEN m.zoom_meeting_url IS NOT NULL AND TRIM(m.zoom_meeting_url) <> \'\' THEN 1
                        ELSE 0
                    END AS has_zoom_url
             FROM enrollments e
             INNER JOIN masterclasses m ON m.id = e.masterclass_id
             WHERE e.user_id = :user_id
             ORDER BY m.event_starts_at DESC'
        );
        $stmt->execute(['user_id' => $userId]);

        return $stmt->fetchAll();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findByUserAndMasterclass(int $userId, int $masterclassId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM enrollments WHERE user_id = :user_id AND masterclass_id = :masterclass_id LIMIT 1'
        );
        $stmt->execute(['user_id' => $userId, 'masterclass_id' => $masterclassId]);

        $row = $stmt->fetch();

        return $row ?: null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function recentWithDetails(int $limit = 300): array
    {
        $stmt = $this->db->prepare(
            'SELECT e.*, u.name AS user_name, u.email AS user_email, m.name AS masterclass_name,
                    p.provider AS payment_provider, p.amount AS payment_amount, p.currency AS payment_currency
             FROM enrollments e
             INNER JOIN users u ON u.id = e.user_id
             INNER JOIN masterclasses m ON m.id = e.masterclass_id
             LEFT JOIN payments p ON p.id = e.payment_id
             ORDER BY e.created_at DESC
             LIMIT :limit'
        );
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function count(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM enrollments')->fetchColumn();
    }

    public function countPaid(): int
    {
        return (int) $this->db->query("SELECT COUNT(*) FROM enrollments WHERE status = 'paid'")->fetchColumn();
    }

    /**
     * Crea o confirma una inscripción como pagada. Por diseño, la inscripción
     * (enrollments) solo se crea/confirma cuando un pago queda 'approved'; nunca
     * se crea de antemano con estados pending/failed/cancelled.
     *
     * Es idempotente: si la inscripción ya existía y ya estaba 'paid' (por
     * ejemplo, por un segundo pago aprobado por error o un reintento de
     * webhook), no se duplica ni se sobrescribe el payment_id original.
     *
     * @return array{id:int, already_paid:bool}
     */
    public function findOrCreatePaid(int $userId, int $masterclassId, int $paymentId): array
    {
        $existing = $this->findByUserAndMasterclass($userId, $masterclassId);

        if ($existing !== null) {
            if ($existing['status'] === 'paid') {
                return ['id' => (int) $existing['id'], 'already_paid' => true];
            }

            $stmt = $this->db->prepare(
                "UPDATE enrollments SET status = 'paid', payment_id = :payment_id, access_granted_at = NOW(), updated_at = NOW() WHERE id = :id"
            );
            $stmt->execute(['payment_id' => $paymentId, 'id' => $existing['id']]);

            return ['id' => (int) $existing['id'], 'already_paid' => false];
        }

        try {
            $stmt = $this->db->prepare(
                "INSERT INTO enrollments (user_id, masterclass_id, status, payment_id, access_granted_at, created_at, updated_at)
                 VALUES (:user_id, :masterclass_id, 'paid', :payment_id, NOW(), NOW(), NOW())"
            );
            $stmt->execute(['user_id' => $userId, 'masterclass_id' => $masterclassId, 'payment_id' => $paymentId]);

            return ['id' => (int) $this->db->lastInsertId(), 'already_paid' => false];
        } catch (\PDOException $e) {
            // Condición de carrera: otra petición concurrente ya creó la inscripción
            // (protegido por el índice único user_id+masterclass_id).
            if ((string) $e->getCode() === '23000') {
                $existing = $this->findByUserAndMasterclass($userId, $masterclassId);

                return ['id' => (int) ($existing['id'] ?? 0), 'already_paid' => true];
            }

            throw $e;
        }
    }

    public function updateStatus(int $id, string $status): void
    {
        $stmt = $this->db->prepare('UPDATE enrollments SET status = :status, updated_at = NOW() WHERE id = :id');
        $stmt->execute(['status' => $status, 'id' => $id]);
    }

    /**
     * Marca la primera revelación del enlace Zoom. Idempotente: no sobrescribe
     * un zoom_revealed_at ya existente.
     */
    public function markZoomRevealed(int $enrollmentId): void
    {
        $stmt = $this->db->prepare(
            'UPDATE enrollments
             SET zoom_revealed_at = COALESCE(zoom_revealed_at, NOW()), updated_at = NOW()
             WHERE id = :id'
        );
        $stmt->execute(['id' => $enrollmentId]);
    }
}
