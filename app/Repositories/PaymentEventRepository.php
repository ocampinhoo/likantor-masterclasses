<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

/**
 * Bitácora de idempotencia para webhooks de pago. Cada fila representa un
 * evento único (provider + payload_hash); intentar insertar el mismo hash dos
 * veces choca con la restricción UNIQUE de la tabla, lo que garantiza a nivel
 * de base de datos que un mismo evento nunca se procese más de una vez,
 * incluso ante reintentos concurrentes del proveedor.
 */
final class PaymentEventRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findByHash(string $provider, string $payloadHash): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM payment_events WHERE provider = :provider AND payload_hash = :hash LIMIT 1');
        $stmt->execute(['provider' => $provider, 'hash' => $payloadHash]);

        $row = $stmt->fetch();

        return $row ?: null;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO payment_events (provider, event_type, provider_event_id, payload_hash, payload, processed, created_at)
             VALUES (:provider, :event_type, :provider_event_id, :payload_hash, :payload, 0, NOW())'
        );

        $stmt->execute([
            'provider' => $data['provider'],
            'event_type' => $data['event_type'],
            'provider_event_id' => $data['provider_event_id'] ?? null,
            'payload_hash' => $data['payload_hash'],
            'payload' => $data['payload'] ?? null,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function markProcessed(int $id, ?int $paymentId): void
    {
        $stmt = $this->db->prepare(
            'UPDATE payment_events SET processed = 1, payment_id = :payment_id, error_message = NULL, processed_at = NOW() WHERE id = :id'
        );
        $stmt->execute(['payment_id' => $paymentId, 'id' => $id]);
    }

    public function markError(int $id, string $error): void
    {
        $stmt = $this->db->prepare('UPDATE payment_events SET error_message = :error WHERE id = :id');
        $stmt->execute(['error' => substr($error, 0, 2000), 'id' => $id]);
    }
}
