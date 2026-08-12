<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\EnrollmentRepository;
use App\Repositories\MasterclassRepository;
use App\Repositories\PaymentRepository;

/**
 * Autorización de acceso a Zoom de una Masterclass.
 *
 * Regla: usuario autenticado + enrollment propio + status paid + masterclass activa.
 * El user_id siempre proviene de la sesión (AuthService), nunca de la petición.
 */
final class MasterclassAccessService
{
    /** Estados de masterclass en los que un inscrito pagado puede obtener el enlace. */
    private const ACTIVE_STATUSES = ['published', 'registration_closed', 'live', 'completed'];

    private EnrollmentRepository $enrollments;
    private MasterclassRepository $masterclasses;
    private PaymentRepository $payments;

    public function __construct()
    {
        $this->enrollments = new EnrollmentRepository();
        $this->masterclasses = new MasterclassRepository();
        $this->payments = new PaymentRepository();
    }

    /**
     * Tarjetas para /mi-cuenta: pagado, pendiente o sin inscripción.
     * Nunca incluye zoom_meeting_url ni passcode.
     *
     * @return array<int, array<string, mixed>>
     */
    public function dashboardCardsForUser(int $userId): array
    {
        $enrollments = $this->enrollments->findByUserId($userId);
        $enrollmentsByMasterclass = [];

        foreach ($enrollments as $enrollment) {
            $enrollmentsByMasterclass[(int) $enrollment['masterclass_id']] = $enrollment;
        }

        $pendingPayments = $this->payments->findPendingByUserId($userId);
        $pendingByMasterclass = [];

        foreach ($pendingPayments as $payment) {
            $mcId = (int) $payment['masterclass_id'];

            if (!isset($pendingByMasterclass[$mcId])) {
                $pendingByMasterclass[$mcId] = $payment;
            }
        }

        $cards = [];
        $seenMasterclassIds = [];

        foreach ($enrollments as $enrollment) {
            $mcId = (int) $enrollment['masterclass_id'];
            $seenMasterclassIds[$mcId] = true;
            $cards[] = $this->buildCardFromEnrollment($enrollment);
        }

        foreach ($pendingByMasterclass as $mcId => $payment) {
            if (isset($seenMasterclassIds[$mcId])) {
                continue;
            }

            $seenMasterclassIds[$mcId] = true;
            $cards[] = $this->buildPendingCard(
                (string) $payment['masterclass_name'],
                (string) $payment['masterclass_slug'],
                $payment['event_starts_at'] ?? null,
                (string) ($payment['timezone'] ?? 'America/Mexico_City'),
                (int) ($payment['duration_minutes'] ?? 0)
            );
        }

        foreach ($this->masterclasses->published() as $masterclass) {
            $mcId = (int) $masterclass['id'];

            if (isset($seenMasterclassIds[$mcId])) {
                continue;
            }

            $cards[] = $this->buildReserveCard($masterclass);
        }

        return $cards;
    }

    /**
     * Autoriza y, si procede, registra zoom_revealed_at y devuelve la URL de Zoom.
     *
     * @return array{allowed:bool, url?:string, message?:string}
     */
    public function grantZoomAccess(int $userId, string $slug): array
    {
        $masterclass = $this->masterclasses->findBySlug($slug);

        if ($masterclass === null) {
            return ['allowed' => false, 'message' => 'Masterclass no encontrada.'];
        }

        if (!$this->isMasterclassActive($masterclass)) {
            return ['allowed' => false, 'message' => 'Esta Masterclass no está disponible en este momento.'];
        }

        $enrollment = $this->enrollments->findByUserAndMasterclass($userId, (int) $masterclass['id']);

        if ($enrollment === null || (string) $enrollment['status'] !== 'paid') {
            return ['allowed' => false, 'message' => 'No tienes acceso a esta Masterclass.'];
        }

        $zoomUrl = trim((string) ($masterclass['zoom_meeting_url'] ?? ''));

        if ($zoomUrl === '' || !$this->isSafeExternalUrl($zoomUrl)) {
            return ['allowed' => false, 'message' => 'El enlace de Zoom aún no está disponible. Vuelve a intentarlo más tarde.'];
        }

        $this->enrollments->markZoomRevealed((int) $enrollment['id']);

        return ['allowed' => true, 'url' => $zoomUrl];
    }

    /**
     * @param array<string, mixed> $enrollment
     * @return array<string, mixed>
     */
    private function buildCardFromEnrollment(array $enrollment): array
    {
        $status = (string) $enrollment['status'];
        [$eventDate, $eventTime] = $this->formatEventDateTime(
            $enrollment['event_starts_at'] ?? null,
            (string) ($enrollment['timezone'] ?? 'America/Mexico_City')
        );

        $base = [
            'masterclass_id' => (int) $enrollment['masterclass_id'],
            'name' => (string) $enrollment['masterclass_name'],
            'slug' => (string) $enrollment['masterclass_slug'],
            'event_date' => $eventDate,
            'event_time' => $eventTime,
            'duration_minutes' => (int) ($enrollment['duration_minutes'] ?? 0),
            'zoom_ready' => (bool) ($enrollment['has_zoom_url'] ?? false),
        ];

        if ($status === 'paid') {
            return $base + [
                'access_state' => 'paid',
                'headline' => 'Tu lugar está confirmado',
                'status_label' => 'Acceso confirmado',
            ];
        }

        if (in_array($status, ['pending', 'awaiting_payment'], true)) {
            return $base + [
                'access_state' => 'pending',
                'headline' => 'Tu pago está pendiente de confirmación.',
                'status_label' => 'Pendiente',
            ];
        }

        return $base + [
            'access_state' => 'unavailable',
            'headline' => 'Tu acceso a esta Masterclass no está activo.',
            'status_label' => $status,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPendingCard(
        string $name,
        string $slug,
        mixed $eventStartsAt,
        string $timezone,
        int $durationMinutes
    ): array {
        [$eventDate, $eventTime] = $this->formatEventDateTime($eventStartsAt, $timezone);

        return [
            'masterclass_id' => 0,
            'name' => $name,
            'slug' => $slug,
            'event_date' => $eventDate,
            'event_time' => $eventTime,
            'duration_minutes' => $durationMinutes,
            'zoom_ready' => false,
            'access_state' => 'pending',
            'headline' => 'Tu pago está pendiente de confirmación.',
            'status_label' => 'Pendiente',
        ];
    }

    /**
     * @param array<string, mixed> $masterclass
     * @return array<string, mixed>
     */
    private function buildReserveCard(array $masterclass): array
    {
        [$eventDate, $eventTime] = $this->formatEventDateTime(
            $masterclass['event_starts_at'] ?? null,
            (string) ($masterclass['timezone'] ?? 'America/Mexico_City')
        );

        return [
            'masterclass_id' => (int) $masterclass['id'],
            'name' => (string) $masterclass['name'],
            'slug' => (string) $masterclass['slug'],
            'event_date' => $eventDate,
            'event_time' => $eventTime,
            'duration_minutes' => (int) ($masterclass['duration_minutes'] ?? 0),
            'zoom_ready' => false,
            'access_state' => 'none',
            'headline' => 'Reserva tu lugar.',
            'status_label' => 'Sin inscripción',
        ];
    }

    /**
     * @param array<string, mixed> $masterclass
     */
    private function isMasterclassActive(array $masterclass): bool
    {
        return in_array((string) ($masterclass['status'] ?? ''), self::ACTIVE_STATUSES, true);
    }

    private function isSafeExternalUrl(string $url): bool
    {
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        return in_array($scheme, ['https', 'http'], true);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function formatEventDateTime(mixed $eventStartsAt, string $timezoneName): array
    {
        if ($eventStartsAt === null || $eventStartsAt === '') {
            return ['', ''];
        }

        try {
            $date = new \DateTimeImmutable((string) $eventStartsAt, new \DateTimeZone('UTC'));
            $date = $date->setTimezone(new \DateTimeZone($timezoneName !== '' ? $timezoneName : 'America/Mexico_City'));

            $months = [
                1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
                5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto',
                9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre',
            ];

            $dateStr = sprintf(
                '%d de %s de %s',
                (int) $date->format('j'),
                $months[(int) $date->format('n')],
                $date->format('Y')
            );
            $timeStr = ltrim($date->format('g:i A'), '0');

            return [$dateStr, $timeStr];
        } catch (\Throwable) {
            return [(string) $eventStartsAt, ''];
        }
    }
}
