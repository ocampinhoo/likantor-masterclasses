<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\ErrorHandler;
use App\Repositories\LeadRepository;

/**
 * Orquesta el flujo completo de captura de un lead:
 * honeypot -> validación -> deduplicación en MySQL -> registro de interacción -> email vía SendGrid.
 */
final class LeadService
{
    private LeadRepository $leads;
    private EmailService $emailService;

    public function __construct()
    {
        $this->leads = new LeadRepository();
        $this->emailService = new EmailService();
    }

    /**
     * @param array<string, mixed> $input   name, email, privacy, website (honeypot)
     * @param array<string, mixed> $context masterclass (array|null), source, campaign, utm (array), ip, user_agent
     * @return array{success: bool, message?: string, lead_id?: int, silent?: bool}
     */
    public function capture(array $input, array $context): array
    {
        // Honeypot: si el campo trampa viene lleno, es un bot. Respondemos "éxito" sin
        // guardar nada ni delatar la detección, tal como recomienda la buena práctica anti-bot.
        $honeypot = trim((string) ($input['website'] ?? ''));

        if ($honeypot !== '') {
            ErrorHandler::log('Lead honeypot triggered', ['ip' => $context['ip'] ?? null]);

            return ['success' => true, 'silent' => true];
        }

        $name = sanitize_string((string) ($input['name'] ?? ''), 150);
        $email = sanitize_email((string) ($input['email'] ?? ''));
        $privacyAccepted = (string) ($input['privacy'] ?? '') === '1';

        $errors = [];

        if ($name === '' || mb_strlen($name) < 2) {
            $errors[] = 'Ingresa tu nombre completo.';
        }

        if (!validate_email($email)) {
            $errors[] = 'Ingresa un correo electrónico válido.';
        } elseif (!$this->isEmailDomainValid($email)) {
            $errors[] = 'No pudimos validar el dominio de ese correo electrónico.';
        }

        if (!$privacyAccepted) {
            $errors[] = 'Debes aceptar el aviso de privacidad.';
        }

        if ($errors !== []) {
            return ['success' => false, 'message' => implode(' ', $errors)];
        }

        $masterclass = $context['masterclass'] ?? null;
        $masterclassId = is_array($masterclass) && isset($masterclass['id']) ? (int) $masterclass['id'] : null;
        $source = (string) ($context['source'] ?? 'syllabus_form');
        $utm = is_array($context['utm'] ?? null) ? $context['utm'] : [];
        $campaign = $this->nullableString($context['campaign'] ?? ($utm['campaign'] ?? null), 100);
        $ip = $context['ip'] ?? null;
        $userAgent = $this->nullableString($context['user_agent'] ?? null, 255);
        $now = date('Y-m-d H:i:s');

        $leadData = [
            'name' => $name,
            'email' => $email,
            'source' => $source,
            'campaign' => $campaign,
            'utm_source' => $this->nullableString($utm['utm_source'] ?? null, 100),
            'utm_medium' => $this->nullableString($utm['utm_medium'] ?? null, 100),
            'utm_campaign' => $this->nullableString($utm['utm_campaign'] ?? null, 150),
            'utm_content' => $this->nullableString($utm['utm_content'] ?? null, 150),
            'utm_term' => $this->nullableString($utm['utm_term'] ?? null, 150),
            'masterclass_id' => $masterclassId,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'privacy_accepted_at' => $now,
        ];

        try {
            $leadId = $this->leads->upsert($leadData);
            $this->leads->logInteraction(array_merge($leadData, ['lead_id' => $leadId]));
        } catch (\Throwable $e) {
            ErrorHandler::log('Lead capture failed', ['error' => $e->getMessage()]);

            return ['success' => false, 'message' => 'No pudimos procesar tu solicitud. Intenta de nuevo en unos minutos.'];
        }

        $this->sendSyllabusEmail($leadId, $name, $email, is_array($masterclass) ? $masterclass : null);

        return ['success' => true, 'lead_id' => $leadId];
    }

    /**
     * @param array<string, mixed>|null $masterclass
     */
    private function sendSyllabusEmail(int $leadId, string $name, string $email, ?array $masterclass): void
    {
        $masterclassName = (string) ($masterclass['name'] ?? 'nuestra próxima Masterclass');
        $slug = (string) ($masterclass['slug'] ?? 'revision-estructuras-post-sismo');
        $ctaUrl = url('/masterclass/' . $slug);

        [$eventDate, $eventTime] = $this->formatEventDateTime($masterclass);

        try {
            $queueId = $this->emailService->queueEmail('syllabus_request', $email, $name, [
                'name' => $name,
                'masterclass_name' => $masterclassName,
                'event_date' => $eventDate,
                'event_time' => $eventTime,
                'cta_url' => $ctaUrl,
            ]);

            $this->emailService->sendQueuedImmediately($queueId);
        } catch (\Throwable $e) {
            // No hacemos fallar la captura del lead si el correo falla; el registro
            // ya quedó guardado y podrá reintentarse (o reenviarse manualmente) después.
            ErrorHandler::log('Lead email queue failed', ['lead_id' => $leadId, 'error' => $e->getMessage()]);
        }
    }

    /**
     * @param array<string, mixed>|null $masterclass
     * @return array{0: string, 1: string}
     */
    private function formatEventDateTime(?array $masterclass): array
    {
        $default = ['17 de septiembre de 2026', '6:30 PM (Ciudad de México)'];

        if ($masterclass === null || empty($masterclass['event_starts_at'])) {
            return $default;
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
            $timeStr = ltrim($date->format('g:i A'), '0') . ' (hora de Ciudad de México)';

            return [$dateStr, $timeStr];
        } catch (\Throwable) {
            return $default;
        }
    }

    private function isEmailDomainValid(string $email): bool
    {
        if (!function_exists('checkdnsrr')) {
            return true;
        }

        $domain = substr((string) strrchr($email, '@'), 1);

        if ($domain === '') {
            return false;
        }

        try {
            return checkdnsrr($domain, 'MX') || checkdnsrr($domain, 'A');
        } catch (\Throwable) {
            // Algunos hostings restringen resoluciones DNS salientes; no bloqueamos por eso.
            return true;
        }
    }

    private function nullableString(mixed $value, int $maxLength): ?string
    {
        if ($value === null) {
            return null;
        }

        $str = sanitize_string((string) $value, $maxLength);

        return $str === '' ? null : $str;
    }
}
