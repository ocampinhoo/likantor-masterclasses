<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Config;

/**
 * Construye eventos de webhook "falsos" (Stripe / Mercado Pago) firmados con
 * los secretos configurados localmente, para poder probar el flujo de pagos
 * de punta a punta sin credenciales reales de ningún proveedor.
 *
 * Usado tanto por el simulador web (App\Controllers\Dev\PaymentSimulatorController)
 * como por el script de línea de comandos (cron/simulate_webhook.php).
 */
final class PaymentWebhookSimulator
{
    public const ALLOWED_OUTCOMES = ['approved', 'failed', 'cancelled', 'refunded', 'pending'];

    /**
     * @param array<string, mixed> $payment
     * @return array{body:string, headers:array<int,string>, path:string}
     */
    public static function build(string $provider, string $outcome, array $payment): array
    {
        if (!in_array($outcome, self::ALLOWED_OUTCOMES, true)) {
            $outcome = 'approved';
        }

        if ($provider === 'stripe') {
            [$body, $headers] = self::buildStripeEvent($outcome, $payment);

            return ['body' => $body, 'headers' => $headers, 'path' => '/webhooks/stripe'];
        }

        [$body, $headers, $queryString] = self::buildMercadoPagoEvent($outcome, $payment);

        return ['body' => $body, 'headers' => $headers, 'path' => '/webhooks/mercadopago?' . $queryString];
    }

    /**
     * @param array<string, mixed> $payment
     * @return array{0:string, 1:array<int,string>}
     */
    private static function buildStripeEvent(string $outcome, array $payment): array
    {
        $uuidCompact = substr(str_replace('-', '', (string) $payment['uuid']), 0, 16);
        $fakePaymentIntentId = 'pi_dev_' . $uuidCompact;
        $amountMinor = (int) round(((float) $payment['amount']) * 100);
        $currency = strtolower((string) $payment['currency']);

        $eventType = match ($outcome) {
            'approved' => 'checkout.session.completed',
            'failed' => 'checkout.session.async_payment_failed',
            'cancelled' => 'checkout.session.expired',
            'refunded' => 'charge.refunded',
            default => 'checkout.session.completed',
        };

        if ($outcome === 'refunded') {
            $object = [
                'id' => 'ch_dev_' . $uuidCompact,
                'object' => 'charge',
                'amount' => $amountMinor,
                'currency' => $currency,
                'payment_intent' => $fakePaymentIntentId,
                'metadata' => ['payment_uuid' => $payment['uuid']],
            ];
        } else {
            $object = [
                'id' => 'cs_dev_' . $uuidCompact,
                'object' => 'checkout.session',
                'payment_status' => $outcome === 'approved' ? 'paid' : 'unpaid',
                'payment_intent' => $fakePaymentIntentId,
                'amount_total' => $amountMinor,
                'currency' => $currency,
                'client_reference_id' => $payment['uuid'],
                'metadata' => ['payment_uuid' => $payment['uuid']],
            ];
        }

        $event = [
            'id' => 'evt_dev_' . bin2hex(random_bytes(8)),
            'type' => $eventType,
            'data' => ['object' => $object],
        ];

        $body = (string) json_encode($event, JSON_UNESCAPED_UNICODE);
        $secret = (string) (Config::get('payments')['stripe']['webhook_secret'] ?? '');
        $timestamp = time();
        $signature = hash_hmac('sha256', $timestamp . '.' . $body, $secret);

        $headers = [
            'Content-Type: application/json',
            'Stripe-Signature: t=' . $timestamp . ',v1=' . $signature,
        ];

        return [$body, $headers];
    }

    /**
     * @param array<string, mixed> $payment
     * @return array{0:string, 1:array<int,string>, 2:string}
     */
    private static function buildMercadoPagoEvent(string $outcome, array $payment): array
    {
        $mpStatus = match ($outcome) {
            'approved' => 'approved',
            'failed' => 'rejected',
            'cancelled' => 'cancelled',
            'refunded' => 'refunded',
            default => 'pending',
        };

        // El "pago falso" (estado, monto, moneda, referencia) se embebe directamente en el id,
        // ya que en modo simulación no existe una cuenta real de Mercado Pago a la cual consultar.
        // MercadoPagoPaymentService::fetchPayment() lo decodifica en vez de llamar a la API real
        // (solo si APP_ENV=local y no hay access token configurado).
        $encoded = base64_encode((string) json_encode([
            'status' => $mpStatus,
            'status_detail' => 'dev_simulated',
            'transaction_amount' => (float) $payment['amount'],
            'currency_id' => (string) $payment['currency'],
            'external_reference' => (string) $payment['uuid'],
        ]));
        $token = 'DEV_' . rtrim(strtr($encoded, '+/', '-_'), '=');

        $notificationId = random_int(100000000, 999999999);

        $body = (string) json_encode([
            'id' => $notificationId,
            'type' => 'payment',
            'action' => 'payment.updated',
            'data' => ['id' => $token],
        ], JSON_UNESCAPED_UNICODE);

        $secret = (string) (Config::get('payments')['mercadopago']['webhook_secret'] ?? '');
        $requestId = bin2hex(random_bytes(8));
        $ts = (string) time();
        $manifest = 'id:' . strtolower($token) . ';request-id:' . $requestId . ';ts:' . $ts . ';';
        $signature = hash_hmac('sha256', $manifest, $secret);

        $headers = [
            'Content-Type: application/json',
            'x-request-id: ' . $requestId,
            'x-signature: ts=' . $ts . ',v1=' . $signature,
        ];

        $queryString = http_build_query(['type' => 'payment', 'data.id' => $token]);

        return [$body, $headers, $queryString];
    }
}
