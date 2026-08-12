<?php

declare(strict_types=1);

/**
 * CLI: simula un webhook de pago (Stripe o Mercado Pago) firmado localmente,
 * sin credenciales reales de ningún proveedor. Solo funciona con APP_ENV=local
 * (falla explícitamente en cualquier otro entorno, por seguridad).
 *
 * Uso:
 *   php cron/simulate_webhook.php --payment=<uuid> --outcome=approved
 *
 * Opciones:
 *   --payment   UUID del pago (payments.uuid). Requerido.
 *   --outcome   approved | failed | cancelled | refunded | pending. Default: approved.
 *   --amount    (opcional) Monto reportado en el webhook. Si difiere del checkout, el acceso se rechaza.
 *   --currency  (opcional) Moneda reportada en el webhook (ej. MXN). Si difiere, el acceso se rechaza.
 *   --event-id  (opcional) Id fijo del evento. Reenvía el mismo id para probar idempotencia.
 *
 * El script construye el evento con la forma que enviaría el proveedor, lo firma
 * con STRIPE_WEBHOOK_SECRET / MERCADOPAGO_WEBHOOK_SECRET (cadenas inventadas locales)
 * y lo POST-ea a /webhooks/{provider} en APP_URL.
 */

require dirname(__DIR__) . '/app/init.php';

use App\Core\Config;
use App\Core\Http;
use App\Repositories\PaymentRepository;
use App\Services\PaymentWebhookSimulator;

if (Config::app('env') !== 'local') {
    fwrite(STDERR, 'Este script solo puede ejecutarse con APP_ENV=local.' . PHP_EOL);
    exit(1);
}

$options = getopt('', ['payment:', 'outcome::', 'amount::', 'currency::', 'event-id::']);
$paymentUuid = $options['payment'] ?? null;
$outcome = $options['outcome'] ?? 'approved';

if (!is_string($paymentUuid) || $paymentUuid === '') {
    fwrite(STDERR, 'Uso: php cron/simulate_webhook.php --payment=<uuid> --outcome=approved [--amount=1] [--currency=MXN] [--event-id=evt_dup_1]' . PHP_EOL);
    exit(1);
}

$payment = (new PaymentRepository())->findByUuid($paymentUuid);

if ($payment === null) {
    fwrite(STDERR, "No se encontró ningún pago con uuid={$paymentUuid}." . PHP_EOL);
    exit(1);
}

$overrides = [];

if (isset($options['amount']) && $options['amount'] !== false && $options['amount'] !== '') {
    $overrides['amount'] = $options['amount'];
}

if (isset($options['currency']) && is_string($options['currency']) && $options['currency'] !== '') {
    $overrides['currency'] = $options['currency'];
}

if (isset($options['event-id']) && is_string($options['event-id']) && $options['event-id'] !== '') {
    $overrides['event_id'] = $options['event-id'];
}

$event = PaymentWebhookSimulator::build((string) $payment['provider'], (string) $outcome, $payment, $overrides);
$response = Http::request('POST', url($event['path']), $event['headers'], $event['body'], 15);

echo date('c') . " — Webhook simulado ({$payment['provider']}, outcome={$outcome}, event_id={$event['event_id']}) → HTTP {$response['status']}: {$response['body']}" . PHP_EOL;

exit($response['status'] >= 200 && $response['status'] < 300 ? 0 : 1);
