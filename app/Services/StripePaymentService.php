<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use App\Core\Http;

/**
 * Integración con la API oficial de Stripe (Checkout Sessions) vía cURL.
 *
 * No se procesan tarjetas ni se almacenan datos sensibles de pago: el pago
 * ocurre por completo en la página hospedada de Stripe Checkout. Este
 * servicio únicamente crea la sesión y, del lado del webhook, valida la
 * firma `Stripe-Signature` de forma manual (sin el SDK oficial) siguiendo
 * el esquema documentado por Stripe.
 */
final class StripePaymentService
{
    private const API_BASE = 'https://api.stripe.com/v1';

    private string $secretKey;
    private string $webhookSecret;

    public function __construct()
    {
        $config = Config::get('payments')['stripe'] ?? [];
        $this->secretKey = (string) ($config['secret_key'] ?? '');
        $this->webhookSecret = (string) ($config['webhook_secret'] ?? '');
    }

    /**
     * Indica si hay credenciales reales para crear sesiones de Checkout.
     */
    public function isConfigured(): bool
    {
        return $this->secretKey !== '';
    }

    public function hasWebhookSecret(): bool
    {
        return $this->webhookSecret !== '';
    }

    /**
     * @return array{success:bool, url?:string, session_id?:string, error?:string}
     */
    public function createCheckoutSession(
        string $paymentUuid,
        float $amount,
        string $currency,
        string $productName,
        string $customerEmail,
        string $successUrl,
        string $cancelUrl
    ): array {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'Stripe no está configurado.'];
        }

        // Stripe expresa los montos en la unidad mínima de la moneda (p.ej. centavos para USD/MXN).
        $unitAmount = (int) round($amount * 100);

        $payload = [
            'mode' => 'payment',
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'client_reference_id' => $paymentUuid,
            'customer_email' => $customerEmail,
            'line_items' => [[
                'quantity' => 1,
                'price_data' => [
                    'currency' => strtolower($currency),
                    'unit_amount' => $unitAmount,
                    'product_data' => [
                        'name' => $productName,
                    ],
                ],
            ]],
            'metadata' => [
                'payment_uuid' => $paymentUuid,
            ],
            'payment_intent_data' => [
                'metadata' => [
                    'payment_uuid' => $paymentUuid,
                ],
            ],
        ];

        $response = Http::request('POST', self::API_BASE . '/checkout/sessions', [
            'Authorization: Bearer ' . $this->secretKey,
            'Content-Type: application/x-www-form-urlencoded',
            'Idempotency-Key: checkout-' . $paymentUuid,
        ], http_build_query($payload));

        if ($response['error'] !== null) {
            return ['success' => false, 'error' => $response['error']];
        }

        if ($response['status'] >= 200 && $response['status'] < 300 && $response['json'] !== null) {
            return [
                'success' => true,
                'url' => (string) $response['json']['url'],
                'session_id' => (string) $response['json']['id'],
            ];
        }

        $message = $response['json']['error']['message'] ?? ('Stripe respondió HTTP ' . $response['status']);

        return ['success' => false, 'error' => (string) $message];
    }

    /**
     * Verifica manualmente el header Stripe-Signature siguiendo el esquema
     * documentado por Stripe (https://stripe.com/docs/webhooks#verify-manually),
     * sin depender del SDK oficial: formato "t=<timestamp>,v1=<firma>[,v1=<firma>...]".
     */
    public function verifySignature(string $payload, string $signatureHeader): bool
    {
        if ($this->webhookSecret === '' || $signatureHeader === '') {
            return false;
        }

        $timestamp = null;
        $signatures = [];

        foreach (explode(',', $signatureHeader) as $item) {
            $parts = explode('=', trim($item), 2);

            if (count($parts) !== 2) {
                continue;
            }

            [$key, $value] = $parts;

            if ($key === 't') {
                $timestamp = $value;
            } elseif ($key === 'v1') {
                $signatures[] = $value;
            }
        }

        if ($timestamp === null || $signatures === [] || !ctype_digit($timestamp)) {
            return false;
        }

        $tolerance = (int) (Config::get('payments')['webhook_tolerance_seconds'] ?? 300);

        if (abs(time() - (int) $timestamp) > $tolerance) {
            return false;
        }

        $expected = hash_hmac('sha256', $timestamp . '.' . $payload, $this->webhookSecret);

        foreach ($signatures as $signature) {
            if (hash_equals($expected, $signature)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Traduce un evento de Stripe a uno de los estados canónicos de la
     * plataforma: pending, approved, failed, cancelled, refunded, chargeback, unknown.
     *
     * @param array<string, mixed> $object El objeto data.object del evento.
     */
    public function mapStatus(string $eventType, array $object): string
    {
        return match ($eventType) {
            'checkout.session.completed' => match ($object['payment_status'] ?? '') {
                'paid' => 'approved',
                default => 'pending',
            },
            'checkout.session.async_payment_succeeded' => 'approved',
            'checkout.session.async_payment_failed' => 'failed',
            'checkout.session.expired' => 'cancelled',
            'payment_intent.payment_failed' => 'failed',
            'payment_intent.canceled' => 'cancelled',
            'charge.refunded' => 'refunded',
            'charge.dispute.created', 'charge.dispute.funds_withdrawn' => 'chargeback',
            default => 'unknown',
        };
    }
}
