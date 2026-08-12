<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class PaymentRepository
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
            'INSERT INTO payments
                (uuid, user_id, masterclass_id, enrollment_id, provider, amount, currency, status, idempotency_key, metadata, created_at, updated_at)
             VALUES
                (:uuid, :user_id, :masterclass_id, NULL, :provider, :amount, :currency, "pending", :idempotency_key, :metadata, NOW(), NOW())'
        );

        $stmt->execute([
            'uuid' => $data['uuid'],
            'user_id' => $data['user_id'],
            'masterclass_id' => $data['masterclass_id'],
            'provider' => $data['provider'],
            'amount' => $data['amount'],
            'currency' => $data['currency'],
            'idempotency_key' => $data['idempotency_key'],
            'metadata' => json_encode($data['metadata'] ?? [], JSON_UNESCAPED_UNICODE),
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM payments WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);

        return $this->decorate($stmt->fetch());
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findByUuid(string $uuid): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM payments WHERE uuid = :uuid LIMIT 1');
        $stmt->execute(['uuid' => $uuid]);

        return $this->decorate($stmt->fetch());
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findByProviderPaymentId(string $provider, string $providerPaymentId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM payments WHERE provider = :provider AND provider_payment_id = :provider_payment_id LIMIT 1'
        );
        $stmt->execute(['provider' => $provider, 'provider_payment_id' => $providerPaymentId]);

        return $this->decorate($stmt->fetch());
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findLatestForUser(int $userId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT p.*, m.name AS masterclass_name, m.slug AS masterclass_slug
             FROM payments p
             INNER JOIN masterclasses m ON m.id = p.masterclass_id
             WHERE p.user_id = :user_id
             ORDER BY p.created_at DESC
             LIMIT 1'
        );
        $stmt->execute(['user_id' => $userId]);

        return $this->decorate($stmt->fetch());
    }

    /**
     * Pagos aún no confirmados del usuario (pendientes de webhook).
     *
     * @return array<int, array<string, mixed>>
     */
    public function findPendingByUserId(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT p.*,
                    m.name AS masterclass_name,
                    m.slug AS masterclass_slug,
                    m.event_starts_at,
                    m.timezone,
                    m.duration_minutes
             FROM payments p
             INNER JOIN masterclasses m ON m.id = p.masterclass_id
             WHERE p.user_id = :user_id AND p.status = \'pending\'
             ORDER BY p.created_at DESC'
        );
        $stmt->execute(['user_id' => $userId]);

        return array_map(
            fn (array $row): array => $this->decorate($row) ?? $row,
            $stmt->fetchAll()
        );
    }

    public function setProviderPreferenceId(int $id, string $providerPreferenceId): void
    {
        $stmt = $this->db->prepare('UPDATE payments SET provider_preference_id = :value, updated_at = NOW() WHERE id = :id');
        $stmt->execute(['value' => $providerPreferenceId, 'id' => $id]);
    }

    public function attachEnrollment(int $paymentId, int $enrollmentId): void
    {
        $stmt = $this->db->prepare('UPDATE payments SET enrollment_id = :enrollment_id, updated_at = NOW() WHERE id = :id');
        $stmt->execute(['enrollment_id' => $enrollmentId, 'id' => $paymentId]);
    }

    /**
     * Actualiza un pago a partir de un evento de webhook ya validado. Los
     * campos amount/currency/provider_payment_id son opcionales: si vienen en
     * null, se conserva el valor previamente almacenado (COALESCE).
     *
     * @param array{status:string, provider_payment_id:?string, amount:?float, currency:?string, metadata:array<string,mixed>} $data
     */
    public function updateFromWebhook(int $id, array $data): void
    {
        $stmt = $this->db->prepare(
            'UPDATE payments SET
                status = :status,
                provider_payment_id = COALESCE(:provider_payment_id, provider_payment_id),
                amount = COALESCE(:amount, amount),
                currency = COALESCE(:currency, currency),
                metadata = :metadata,
                webhook_received_at = NOW(),
                updated_at = NOW()
             WHERE id = :id'
        );

        $stmt->execute([
            'status' => $data['status'],
            'provider_payment_id' => $data['provider_payment_id'],
            'amount' => $data['amount'],
            'currency' => $data['currency'],
            'metadata' => json_encode($data['metadata'], JSON_UNESCAPED_UNICODE),
            'id' => $id,
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function recent(int $limit = 200): array
    {
        $stmt = $this->db->prepare(
            'SELECT p.*, u.name AS user_name, u.email AS user_email, m.name AS masterclass_name
             FROM payments p
             INNER JOIN users u ON u.id = p.user_id
             INNER JOIN masterclasses m ON m.id = p.masterclass_id
             ORDER BY p.created_at DESC
             LIMIT :limit'
        );
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return array_map(fn (array $row): array => $this->decorate($row) ?? $row, $stmt->fetchAll());
    }

    /**
     * Resumen para el panel admin: conteos por estado e ingresos aprobados
     * agrupados por moneda (nunca se suman monedas distintas entre sí).
     *
     * @return array{counts: array<string, int>, revenue_by_currency: array<string, float>, total_sales: int}
     */
    public function summary(): array
    {
        $counts = [
            'pending' => 0, 'approved' => 0, 'failed' => 0,
            'cancelled' => 0, 'refunded' => 0, 'chargeback' => 0, 'unknown' => 0,
        ];

        $stmt = $this->db->query('SELECT status, COUNT(*) AS total FROM payments GROUP BY status');

        foreach ($stmt->fetchAll() as $row) {
            $status = (string) $row['status'];
            $counts[$status] = ($counts[$status] ?? 0) + (int) $row['total'];
        }

        $revenueByCurrency = [];
        $totalSales = 0;

        $stmt = $this->db->query(
            "SELECT currency, COUNT(*) AS total, SUM(amount) AS amount_sum
             FROM payments WHERE status = 'approved' GROUP BY currency"
        );

        foreach ($stmt->fetchAll() as $row) {
            $currency = (string) $row['currency'];
            $revenueByCurrency[$currency] = ($revenueByCurrency[$currency] ?? 0) + (float) $row['amount_sum'];
            $totalSales += (int) $row['total'];
        }

        return ['counts' => $counts, 'revenue_by_currency' => $revenueByCurrency, 'total_sales' => $totalSales];
    }

    /**
     * @param array<string, mixed>|false $row
     * @return array<string, mixed>|null
     */
    private function decorate(array|false $row): ?array
    {
        if ($row === false) {
            return null;
        }

        $metadata = $row['metadata'] ?? null;
        $row['metadata'] = is_string($metadata) ? (json_decode($metadata, true) ?: []) : (is_array($metadata) ? $metadata : []);

        return $row;
    }
}
