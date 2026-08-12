<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Config;

/**
 * Cliente ligero para la API v3 de SendGrid vía cURL.
 *
 * Se evita depender del SDK oficial (composer sendgrid/sendgrid) para minimizar
 * dependencias en hosting compartido; solo requiere la extensión curl de PHP,
 * disponible por defecto en Hostinger.
 *
 * IMPORTANTE: este servicio se ejecuta exclusivamente en el servidor (PHP).
 * La API key nunca se expone al cliente ni se usa desde JavaScript.
 */
final class SendGridService
{
    private const ENDPOINT = 'https://api.sendgrid.com/v3/mail/send';

    private string $apiKey;
    private string $fromEmail;
    private string $fromName;
    private ?string $replyTo;

    public function __construct()
    {
        $mail = Config::get('mail');

        $this->apiKey = (string) ($mail['api_key'] ?? '');
        $this->fromEmail = (string) ($mail['from_email'] ?? '');
        $this->fromName = (string) ($mail['from_name'] ?? '');
        $replyTo = (string) ($mail['reply_to'] ?? '');
        $this->replyTo = $replyTo !== '' ? $replyTo : null;
    }

    /**
     * @param array<string, mixed> $dynamicData
     * @return array{success: bool, message_id: ?string, error: ?string}
     */
    public function send(
        string $toEmail,
        ?string $toName,
        string $subject,
        string $htmlContent,
        ?string $plainContent = null,
        ?string $dynamicTemplateId = null,
        array $dynamicData = []
    ): array {
        if (!function_exists('curl_init')) {
            return ['success' => false, 'message_id' => null, 'error' => 'La extensión cURL de PHP no está disponible.'];
        }

        if ($this->apiKey === '' || $this->fromEmail === '') {
            return ['success' => false, 'message_id' => null, 'error' => 'SendGrid no está configurado (falta API key o remitente).'];
        }

        $to = array_filter(['email' => $toEmail, 'name' => $toName], static fn ($v) => $v !== null && $v !== '');

        $payload = [
            'personalizations' => [
                ['to' => [$to]],
            ],
            'from' => array_filter(['email' => $this->fromEmail, 'name' => $this->fromName], static fn ($v) => $v !== null && $v !== ''),
        ];

        if ($this->replyTo !== null) {
            $payload['reply_to'] = ['email' => $this->replyTo];
        }

        if ($dynamicTemplateId !== null && $dynamicTemplateId !== '') {
            $payload['template_id'] = $dynamicTemplateId;
            $payload['personalizations'][0]['dynamic_template_data'] = $dynamicData;
        } else {
            $payload['subject'] = $subject;
            $content = [];

            if ($plainContent !== null && $plainContent !== '') {
                $content[] = ['type' => 'text/plain', 'value' => $plainContent];
            }

            $content[] = ['type' => 'text/html', 'value' => $htmlContent];
            $payload['content'] = $content;
        }

        $body = json_encode($payload, JSON_UNESCAPED_UNICODE);

        $ch = curl_init(self::ENDPOINT);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->apiKey,
                'Content-Type: application/json',
            ],
            CURLOPT_HEADER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);

        if ($errno !== 0 || $response === false) {
            return ['success' => false, 'message_id' => null, 'error' => "cURL error ({$errno}): {$error}"];
        }

        $headers = substr((string) $response, 0, $headerSize);
        $messageId = null;

        if (preg_match('/X-Message-Id:\s*(\S+)/i', $headers, $matches) === 1) {
            $messageId = trim($matches[1]);
        }

        if ($httpCode >= 200 && $httpCode < 300) {
            return ['success' => true, 'message_id' => $messageId, 'error' => null];
        }

        $responseBody = substr((string) $response, $headerSize);

        return ['success' => false, 'message_id' => null, 'error' => "SendGrid HTTP {$httpCode}: {$responseBody}"];
    }
}
