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
 *
 * Solo debe usarse con APP_ENV=local. No genera ni requiere API keys reales.
 */
final class PaymentWebhookSimulator
{
    public const ALLOWED_OUTCOMES = ['approved', 'failed', 'cancelled', 'refunded', 'pending'];

    /**
     * @param array<string, mixed> $payment Fila de payments (uuid, amount, currency, provider, …)
     * @param array{amount?:float|int|string, currency?:string, event_id?:string} $overrides
     *        amount/currency: simulan un webhook del proveedor con valores distintos
     *        al checkout (para probar rechazo por mismatch).
     *        event_id: fija el id del evento para poder reenviar el mismo webhook
     *        y probar idempotencia.
     * @return array{body:string, headers:array<int,string>, path:string, event_id:string}
     */
    public static function build(string $provider, string $outcome, array $payment, array $overrides = []): array
    {
        if (!in_array($outcome, self::ALLOWED_OUTCOMES, true)) {
            $outcome = 'approved';
        }

        if ($provider === 'stripe') {
            [$body, $headers, $eventId] = self::buildStripeEvent($outcome, $payment, $overrides);

            return [
                'body' => $body,
                'headers' => $headers,
                'path' => '/webhooks/stripe',
                'event_id' => $eventId,
            ];
        }

        [$body, $headers, $queryString, $eventId] = self::buildMercadoPagoEvent($outcome, $payment, $overrides);

        return [
            'body' => $body,
            'headers' => $headers,
            'path' => '/webhooks/mercadopago?' . $queryString,
            'event_id' => $eventId,
        ];
    }

    /**
     * @param array<string, mixed> $payment
     * @param array{amount?:float|int|string, currency?:string, event_id?:string} $overrides
     * @return array{0:string, 1:array<int,string>, 2:string}
     */
    private static function buildStripeEvent(string $outcome, array $payment, array $overrides): array
    {
        $uuidCompact = substr(str_replace('-', '', (string) $payment['uuid']), 0, 16);
        $fakePaymentIntentId = 'pi_dev_' . $uuidCompact;
        [$amountMajor, $currency] = self::resolveSimulatedMoney($payment, $overrides);
        $amountMinor = (int) round($amountMajor * 100);

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

        $eventId = self::resolveEventId($overrides, 'evt_dev_');

        $event = [
            'id' => $eventId,
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

        return [$body, $headers, $eventId];
    }

    /**
     * @param array<string, mixed> $payment
     * @param array{amount?:float|int|string, currency?:string, event_id?:string} $overrides
     * @return array{0:string, 1:array<int,string>, 2:string, 3:string}
     */
    private static function buildMercadoPagoEvent(string $outcome, array $payment, array $overrides): array
    {
        $mpStatus = match ($outcome) {
            'approved' => 'approved',
            'failed' => 'rejected',
            'cancelled' => 'cancelled',
            'refunded' => 'refunded',
            default => 'pending',
        };

        [$amountMajor, $currency] = self::resolveSimulatedMoney($payment, $overrides);

        // El "pago falso" se embebe en el id (prefijo DEV_). fetchPayment() lo
        // decodifica solo si APP_ENV=local y no hay access token real.
        $encoded = base64_encode((string) json_encode([
            'status' => $mpStatus,
            'status_detail' => 'dev_simulated',
            'transaction_amount' => $amountMajor,
            'currency_id' => strtoupper($currency),
            'external_reference' => (string) $payment['uuid'],
        ]));
        $token = 'DEV_' . rtrim(strtr($encoded, '+/', '-_'), '=');

        $eventId = self::resolveEventId($overrides, '');
        if ($eventId === '' || !ctype_digit($eventId)) {
            $eventId = (string) random_int(100000000, 999999999);
        }

        $body = (string) json_encode([
            'id' => (int) $eventId,
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

        return [$body, $headers, $queryString, $eventId];
    }

    /**
     * @param array<string, mixed> $payment
     * @param array{amount?:float|int|string, currency?:string, event_id?:string} $overrides
     * @return array{0:float, 1:string}
     */
    private static function resolveSimulatedMoney(array $payment, array $overrides): array
    {
        $amount = array_key_exists('amount', $overrides) && $overrides['amount'] !== '' && $overrides['amount'] !== null
            ? (float) $overrides['amount']
            : (float) $payment['amount'];

        $currency = array_key_exists('currency', $overrides) && trim((string) $overrides['currency']) !== ''
            ? strtolower(trim((string) $overrides['currency']))
            : strtolower((string) $payment['currency']);

        return [$amount, $currency];
    }

    /**
     * @param array{amount?:float|int|string, currency?:string, event_id?:string} $overrides
     */
    private static function resolveEventId(array $overrides, string $prefix): string
    {
        $custom = trim((string) ($overrides['event_id'] ?? ''));

        if ($custom !== '') {
            return $custom;
        }

        if ($prefix === '') {
            return (string) random_int(100000000, 999999999);
        }

        return $prefix . bin2hex(random_bytes(8));
    }
}
