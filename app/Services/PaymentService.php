<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\ErrorHandler;
use App\Repositories\EnrollmentRepository;
use App\Repositories\MasterclassRepository;
use App\Repositories\PaymentRepository;
use App\Repositories\UserRepository;

/**
 * Orquesta el ciclo de vida de un pago: creación del intento de checkout y
 * procesamiento idempotente de las actualizaciones de estado que llegan por
 * webhook desde Stripe o Mercado Pago.
 *
 * Regla fundamental (impuesta también por el flujo de los controladores):
 * un pago solo se considera confirmado por el webhook del proveedor, nunca
 * por el simple hecho de que el usuario regrese a una URL de "éxito".
 */
final class PaymentService
{
    private PaymentRepository $payments;
    private EnrollmentRepository $enrollments;
    private UserRepository $users;
    private MasterclassRepository $masterclasses;

    public function __construct()
    {
        $this->payments = new PaymentRepository();
        $this->enrollments = new EnrollmentRepository();
        $this->users = new UserRepository();
        $this->masterclasses = new MasterclassRepository();
    }

    /**
     * Crea un nuevo intento de pago (fila en `payments`, estado 'pending').
     * NO crea ninguna inscripción todavía: eso solo ocurre cuando el webhook
     * confirma el pago como aprobado (ver activateAccess()).
     *
     * @param array<string, mixed> $user
     * @param array<string, mixed> $masterclass
     * @param array{amount: float, currency: string} $checkoutAmount Monto/moneda reales del checkout de este proveedor.
     * @return array{payment: array<string, mixed>}
     * @throws \RuntimeException si el usuario ya tiene un lugar confirmado (código ALREADY_ENROLLED).
     */
    public function createCheckoutAttempt(array $user, array $masterclass, string $provider, array $checkoutAmount): array
    {
        $userId = (int) $user['id'];
        $masterclassId = (int) $masterclass['id'];

        $existingEnrollment = $this->enrollments->findByUserAndMasterclass($userId, $masterclassId);

        if ($existingEnrollment !== null && $existingEnrollment['status'] === 'paid') {
            throw new \RuntimeException('ALREADY_ENROLLED');
        }

        $uuid = uuid_v4();

        $paymentId = $this->payments->create([
            'uuid' => $uuid,
            'user_id' => $userId,
            'masterclass_id' => $masterclassId,
            'provider' => $provider,
            'amount' => $checkoutAmount['amount'],
            'currency' => $checkoutAmount['currency'],
            'idempotency_key' => $uuid,
            'metadata' => [
                'commercial_price' => (float) $masterclass['price'],
                'commercial_currency' => (string) $masterclass['currency'],
            ],
        ]);

        return ['payment' => $this->payments->findById($paymentId)];
    }

    public function attachProviderPreference(int $paymentId, string $providerReferenceId): void
    {
        $this->payments->setProviderPreferenceId($paymentId, $providerReferenceId);
    }

    /**
     * Núcleo idempotente del procesamiento de webhooks.
     *
     * Localiza el pago (primero por provider_payment_id, luego por el uuid de
     * referencia externa), actualiza su estado y, únicamente si transiciona a
     * 'approved' por primera vez, activa el acceso (crea/confirma la
     * inscripción) y dispara el correo de confirmación. Reprocesar el mismo
     * evento (o eventos distintos que reporten el mismo estado terminal) no
     * duplica inscripciones, correos ni accesos.
     *
     * @param array<string, mixed> $context Datos crudos relevantes del evento, solo para auditoría.
     * @return array{handled: bool, reason?: string, payment_id?: int, status?: string}
     */
    public function applyStatusUpdate(
        string $provider,
        ?string $providerPaymentId,
        ?string $externalReferenceUuid,
        string $canonicalStatus,
        ?float $receivedAmount,
        ?string $receivedCurrency,
        array $context = []
    ): array {
        $payment = null;

        if ($providerPaymentId !== null && $providerPaymentId !== '') {
            $payment = $this->payments->findByProviderPaymentId($provider, $providerPaymentId);
        }

        if ($payment === null && $externalReferenceUuid !== null && $externalReferenceUuid !== '') {
            $payment = $this->payments->findByUuid($externalReferenceUuid);
        }

        if ($payment === null) {
            ErrorHandler::log('Webhook payment not found', [
                'provider' => $provider,
                'provider_payment_id' => $providerPaymentId,
                'external_reference' => $externalReferenceUuid,
            ]);

            return ['handled' => false, 'reason' => 'payment_not_found'];
        }

        if ((string) $payment['provider'] !== $provider) {
            return ['handled' => false, 'reason' => 'provider_mismatch'];
        }

        $paymentId = (int) $payment['id'];
        $previousStatus = (string) $payment['status'];

        $terminalStates = ['approved', 'refunded', 'chargeback', 'cancelled'];

        if (in_array($previousStatus, $terminalStates, true) && $previousStatus === $canonicalStatus) {
            // Evento duplicado/tardío para un pago que ya está en su estado final: no-op idempotente.
            return ['handled' => true, 'reason' => 'already_in_status', 'payment_id' => $paymentId, 'status' => $previousStatus];
        }

        $metadata = is_array($payment['metadata']) ? $payment['metadata'] : [];
        $metadata['last_webhook_context'] = $context;

        $this->payments->updateFromWebhook($paymentId, [
            'status' => $canonicalStatus,
            'provider_payment_id' => $providerPaymentId,
            'amount' => $receivedAmount,
            'currency' => $receivedCurrency,
            'metadata' => $metadata,
        ]);

        $wasApproved = $previousStatus === 'approved';

        if ($canonicalStatus === 'approved' && !$wasApproved) {
            $this->activateAccess($paymentId, (int) $payment['user_id'], (int) $payment['masterclass_id']);
        } elseif (in_array($canonicalStatus, ['refunded', 'chargeback'], true) && $payment['enrollment_id'] !== null) {
            $this->revokeAccess((int) $payment['enrollment_id'], $canonicalStatus);
        }
        // failed/cancelled/pending/unknown antes de la aprobación no requieren
        // ninguna acción sobre enrollments: la inscripción aún no existe.

        return ['handled' => true, 'payment_id' => $paymentId, 'status' => $canonicalStatus];
    }

    private function activateAccess(int $paymentId, int $userId, int $masterclassId): void
    {
        $result = $this->enrollments->findOrCreatePaid($userId, $masterclassId, $paymentId);
        $this->payments->attachEnrollment($paymentId, $result['id']);

        if ($result['already_paid']) {
            // El usuario ya contaba con acceso activo (p. ej. un segundo pago
            // aprobado por error). No se reenvía el correo de confirmación.
            return;
        }

        $user = $this->users->findById($userId);
        $masterclass = $this->masterclasses->findById($masterclassId);

        if ($user !== null && $masterclass !== null) {
            $this->sendConfirmationEmail($user, $masterclass, $paymentId);
        }
    }

    private function revokeAccess(int $enrollmentId, string $canonicalStatus): void
    {
        $status = $canonicalStatus === 'chargeback' ? 'access_revoked' : 'refunded';
        $this->enrollments->updateStatus($enrollmentId, $status);
    }

    /**
     * @param array<string, mixed> $user
     * @param array<string, mixed> $masterclass
     */
    private function sendConfirmationEmail(array $user, array $masterclass, int $paymentId): void
    {
        try {
            $payment = $this->payments->findById($paymentId);

            if ($payment === null) {
                return;
            }

            $folio = 'LKT-' . strtoupper(substr(str_replace('-', '', (string) $payment['uuid']), 0, 8));
            [$eventDate, $eventTime] = $this->formatEventDateTime($masterclass);

            $emailService = new EmailService();
            $queueId = $emailService->queueEmail('payment_confirmed', (string) $user['email'], (string) $user['name'], [
                'name' => $user['name'],
                'user_name' => $user['name'],
                'masterclass_name' => $masterclass['name'],
                'event_date' => $eventDate,
                'event_time' => $eventTime,
                'amount' => number_format((float) $payment['amount'], 2),
                'currency' => $payment['currency'],
                'folio' => $folio,
                'login_url' => url('/login'),
            ]);

            $emailService->sendQueuedImmediately($queueId, (int) $user['id']);
        } catch (\Throwable $e) {
            ErrorHandler::log('sendConfirmationEmail failed', ['error' => $e->getMessage(), 'payment_id' => $paymentId]);
        }
    }

    /**
     * @param array<string, mixed> $masterclass
     * @return array{0: string, 1: string}
     */
    private function formatEventDateTime(array $masterclass): array
    {
        if (empty($masterclass['event_starts_at'])) {
            return ['', ''];
        }

        try {
            $timezoneName = (string) ($masterclass['timezone'] ?? 'America/Mexico_City');
            $date = new \DateTimeImmutable((string) $masterclass['event_starts_at'], new \DateTimeZone('UTC'));
            $date = $date->setTimezone(new \DateTimeZone($timezoneName));

            $months = [
                1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
                5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto',
                9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre',
            ];

            $dateStr = sprintf('%d de %s de %s', (int) $date->format('j'), $months[(int) $date->format('n')], $date->format('Y'));
            $timeStr = ltrim($date->format('g:i A'), '0');

            return [$dateStr, $timeStr];
        } catch (\Throwable) {
            return [(string) $masterclass['event_starts_at'], ''];
        }
    }
}
