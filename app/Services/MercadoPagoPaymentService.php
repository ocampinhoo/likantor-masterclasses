<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use App\Core\Http;

/**
 * Integración con la API oficial de Mercado Pago (Checkout Pro) vía cURL.
 *
 * Por diseño de Mercado Pago, la notificación webhook solo trae una
 * referencia (`data.id`); el detalle real y confiable del pago SIEMPRE se
 * obtiene mediante una llamada autenticada a la API (`GET /v1/payments/{id}`),
 * nunca se confía en el cuerpo del webhook por sí solo.
 */
final class MercadoPagoPaymentService
{
    private const API_BASE = 'https://api.mercadopago.com';

    private string $accessToken;
    private string $webhookSecret;

    public function __construct()
    {
        $config = Config::get('payments')['mercadopago'] ?? [];
        $this->accessToken = (string) ($config['access_token'] ?? '');
        $this->webhookSecret = (string) ($config['webhook_secret'] ?? '');
    }

    /**
     * Indica si hay credenciales reales para crear preferencias de checkout.
     */
    public function isConfigured(): bool
    {
        return $this->accessToken !== '';
    }

    public function hasWebhookSecret(): bool
    {
        return $this->webhookSecret !== '';
    }

    private function isSandbox(): bool
    {
        return str_starts_with($this->accessToken, 'TEST-');
    }

    /**
     * @return array{success:bool, init_point?:string, preference_id?:string, error?:string}
     */
    public function createPreference(
        string $paymentUuid,
        float $amount,
        string $currency,
        string $itemTitle,
        string $payerEmail,
        string $successUrl,
        string $pendingUrl,
        string $failureUrl,
        string $notificationUrl
    ): array {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'Mercado Pago no está configurado.'];
        }

        $payload = [
            'items' => [[
                'title' => $itemTitle,
                'quantity' => 1,
                'currency_id' => strtoupper($currency),
                'unit_price' => $amount,
            ]],
            'payer' => ['email' => $payerEmail],
            'back_urls' => [
                'success' => $successUrl,
                'pending' => $pendingUrl,
                'failure' => $failureUrl,
            ],
            'auto_return' => 'approved',
            'external_reference' => $paymentUuid,
            'notification_url' => $notificationUrl,
            'metadata' => ['payment_uuid' => $paymentUuid],
            'statement_descriptor' => 'LIKANTOR',
        ];

        $response = Http::request('POST', self::API_BASE . '/checkout/preferences', [
            'Authorization: Bearer ' . $this->accessToken,
            'Content-Type: application/json',
            'X-Idempotency-Key: checkout-' . $paymentUuid,
        ], json_encode($payload, JSON_UNESCAPED_UNICODE));

        if ($response['error'] !== null) {
            return ['success' => false, 'error' => $response['error']];
        }

        if ($response['status'] >= 200 && $response['status'] < 300 && $response['json'] !== null) {
            $json = $response['json'];
            $url = $this->isSandbox()
                ? (string) ($json['sandbox_init_point'] ?? $json['init_point'] ?? '')
                : (string) ($json['init_point'] ?? '');

            return ['success' => true, 'init_point' => $url, 'preference_id' => (string) ($json['id'] ?? '')];
        }

        $message = $response['json']['message'] ?? ('Mercado Pago respondió HTTP ' . $response['status']);

        return ['success' => false, 'error' => (string) $message];
    }

    /**
     * Obtiene el detalle autoritativo de un pago desde la API de Mercado Pago.
     *
     * En APP_ENV=local, si no hay access_token configurado y el id recibido
     * viene del simulador local de webhooks (prefijo "DEV_"), se decodifica
     * el "pago falso" embebido en el propio id en lugar de llamar a la API
     * real — esto permite probar el flujo completo sin credenciales reales.
     *
     * @return array<string, mixed>|null
     */
    public function fetchPayment(string $providerPaymentId): ?array
    {
        if (
            $this->accessToken === ''
            && Config::app('env') === 'local'
            && str_starts_with($providerPaymentId, 'DEV_')
        ) {
            return $this->decodeDevSimulatedPayment($providerPaymentId);
        }

        $response = Http::request('GET', self::API_BASE . '/v1/payments/' . rawurlencode($providerPaymentId), [
            'Authorization: Bearer ' . $this->accessToken,
        ]);

        if ($response['error'] !== null || $response['json'] === null || $response['status'] >= 300) {
            return null;
        }

        return $response['json'];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decodeDevSimulatedPayment(string $token): ?array
    {
        $encoded = substr($token, 4);
        $encoded = strtr($encoded, '-_', '+/');
        $padded = str_pad($encoded, strlen($encoded) + ((4 - (strlen($encoded) % 4)) % 4), '=');
        $decoded = json_decode((string) base64_decode($padded, true), true);

        if (!is_array($decoded)) {
            return null;
        }

        return [
            'id' => $token,
            'status' => $decoded['status'] ?? 'pending',
            'status_detail' => $decoded['status_detail'] ?? 'dev_simulated',
            'transaction_amount' => $decoded['transaction_amount'] ?? null,
            'currency_id' => $decoded['currency_id'] ?? null,
            'external_reference' => $decoded['external_reference'] ?? null,
            'metadata' => [],
        ];
    }

    /**
     * Valida el header `x-signature` siguiendo el esquema documentado por
     * Mercado Pago para webhooks: manifest "id:{data.id};request-id:{x-request-id};ts:{ts};"
     * firmado con HMAC-SHA256 usando el secreto configurado en el panel de MP.
     */
    public function verifySignature(string $dataId, string $requestId, string $signatureHeader): bool
    {
        if ($this->webhookSecret === '' || $signatureHeader === '' || $dataId === '') {
            return false;
        }

        $ts = null;
        $v1 = null;

        foreach (explode(',', $signatureHeader) as $item) {
            $parts = explode('=', trim($item), 2);

            if (count($parts) !== 2) {
                continue;
            }

            [$key, $value] = $parts;

            if ($key === 'ts') {
                $ts = $value;
            } elseif ($key === 'v1') {
                $v1 = $value;
            }
        }

        if ($ts === null || $v1 === null) {
            return false;
        }

        $manifest = 'id:' . strtolower($dataId) . ';request-id:' . $requestId . ';ts:' . $ts . ';';
        $expected = hash_hmac('sha256', $manifest, $this->webhookSecret);

        return hash_equals($expected, $v1);
    }

    /**
     * Traduce un estado de Mercado Pago a uno de los estados canónicos de la
     * plataforma: pending, approved, failed, cancelled, refunded, chargeback, unknown.
     */
    public function mapStatus(string $mpStatus): string
    {
        return match ($mpStatus) {
            'approved' => 'approved',
            'pending', 'in_process', 'authorized', 'in_mediation' => 'pending',
            'rejected' => 'failed',
            'cancelled' => 'cancelled',
            'refunded' => 'refunded',
            'charged_back' => 'chargeback',
            default => 'unknown',
        };
    }
}
