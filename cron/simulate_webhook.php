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
 *   --payment  UUID del pago (columna payments.uuid) a simular. Requerido.
 *   --outcome  approved | failed | cancelled | refunded | pending. Por defecto: approved.
 *
 * El script construye el evento con la forma exacta que enviaría el proveedor
 * real (según el provider de ese pago), lo firma con STRIPE_WEBHOOK_SECRET /
 * MERCADOPAGO_WEBHOOK_SECRET (definidos en tu .env local, pueden ser cadenas
 * inventadas) y lo envía por HTTP al endpoint /webhooks/{provider} de APP_URL,
 * ejercitando el mismo código de validación de firma e idempotencia que se
 * usaría en producción.
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

$options = getopt('', ['payment:', 'outcome::']);
$paymentUuid = $options['payment'] ?? null;
$outcome = $options['outcome'] ?? 'approved';

if (!is_string($paymentUuid) || $paymentUuid === '') {
    fwrite(STDERR, 'Uso: php cron/simulate_webhook.php --payment=<uuid> --outcome=approved' . PHP_EOL);
    exit(1);
}

$payment = (new PaymentRepository())->findByUuid($paymentUuid);

if ($payment === null) {
    fwrite(STDERR, "No se encontró ningún pago con uuid={$paymentUuid}." . PHP_EOL);
    exit(1);
}

$event = PaymentWebhookSimulator::build((string) $payment['provider'], (string) $outcome, $payment);
$response = Http::request('POST', url($event['path']), $event['headers'], $event['body'], 15);

echo date('c') . " — Webhook simulado ({$payment['provider']}, outcome={$outcome}) → HTTP {$response['status']}: {$response['body']}" . PHP_EOL;

exit($response['status'] >= 200 && $response['status'] < 300 ? 0 : 1);
