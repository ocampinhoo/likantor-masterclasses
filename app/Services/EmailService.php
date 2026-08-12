<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\ErrorHandler;
use App\Repositories\EmailLogRepository;
use App\Repositories\EmailQueueRepository;
use App\Repositories\EmailTemplateRepository;

/**
 * Orquesta el envío de emails transaccionales: encola en `email_queue`,
 * renderiza el template (`email_templates`) y delega el envío a SendGrid,
 * registrando el resultado en `email_logs`.
 *
 * El envío inmediato (síncrono) se intenta al momento de encolar para que el
 * usuario reciba el correo sin demora; si falla, el registro permanece
 * "pending" y el cron (cron/process_email_queue.php) lo reintenta después.
 */
final class EmailService
{
    private EmailTemplateRepository $templates;
    private EmailQueueRepository $queue;
    private EmailLogRepository $logs;
    private SendGridService $sendGrid;

    public function __construct()
    {
        $this->templates = new EmailTemplateRepository();
        $this->queue = new EmailQueueRepository();
        $this->logs = new EmailLogRepository();
        $this->sendGrid = new SendGridService();
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function queueEmail(string $templateSlug, string $toEmail, ?string $toName, array $payload): int
    {
        return $this->queue->create([
            'template_slug' => $templateSlug,
            'to_email' => $toEmail,
            'to_name' => $toName,
            'payload' => $payload,
            'scheduled_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function sendQueuedImmediately(int $queueId, ?int $userId = null): bool
    {
        $row = $this->queue->find($queueId);

        if ($row === null || $row['status'] !== 'pending') {
            return false;
        }

        return $this->processQueueRow($row, $userId);
    }

    public function processPendingQueue(int $limit = 20): int
    {
        $rows = $this->queue->pending($limit);
        $processed = 0;

        foreach ($rows as $row) {
            $this->processQueueRow($row);
            $processed++;
        }

        return $processed;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function processQueueRow(array $row, ?int $userId = null): bool
    {
        $template = $this->templates->findBySlug((string) $row['template_slug']);

        if ($template === null || !(bool) $template['is_active']) {
            $this->queue->markFailedOrRetry((int) $row['id'], 'Template no encontrado o inactivo: ' . $row['template_slug']);

            return false;
        }

        $payload = $row['payload'];
        $payload = is_string($payload) ? (json_decode($payload, true) ?: []) : (array) $payload;

        $subject = $this->renderTemplate((string) $template['subject'], $payload);
        $htmlContent = $this->renderTemplate((string) ($template['body_html'] ?? ''), $payload);

        $dynamicTemplateId = !empty($template['sendgrid_template_id']) ? (string) $template['sendgrid_template_id'] : null;

        if ($dynamicTemplateId === null && trim($htmlContent) === '') {
            $this->queue->markFailedOrRetry((int) $row['id'], 'El template no tiene body_html ni sendgrid_template_id configurado.');

            return false;
        }

        $result = $this->sendGrid->send(
            (string) $row['to_email'],
            $row['to_name'] !== null ? (string) $row['to_name'] : null,
            $subject !== '' ? $subject : (string) $template['name'],
            $htmlContent,
            null,
            $dynamicTemplateId,
            $payload
        );

        if ($result['success']) {
            $this->queue->markSent((int) $row['id']);
        } else {
            $this->queue->markFailedOrRetry((int) $row['id'], (string) $result['error']);
            ErrorHandler::log('SendGrid send failed', [
                'template' => $row['template_slug'],
                'to' => $row['to_email'],
                'error' => $result['error'],
            ]);
        }

        $this->logs->create([
            'user_id' => $userId,
            'template_slug' => $row['template_slug'],
            'to_email' => $row['to_email'],
            'sendgrid_message_id' => $result['message_id'],
            'status' => $result['success'] ? 'sent' : 'failed',
        ]);

        return $result['success'];
    }

    /**
     * @param array<string, mixed> $vars
     */
    private function renderTemplate(string $template, array $vars): string
    {
        if ($template === '') {
            return '';
        }

        $replacements = [];

        foreach ($vars as $key => $value) {
            if (is_scalar($value) || $value === null) {
                $replacements['{{' . $key . '}}'] = e($value === null ? '' : (string) $value);
            }
        }

        return strtr($template, $replacements);
    }
}
