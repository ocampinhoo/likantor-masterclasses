<?php

declare(strict_types=1);

namespace App\Controllers\Webhooks;

use App\Core\ErrorHandler;
use App\Services\PaymentService;
use App\Services\StripePaymentService;

/**
 * Endpoint público (sin login, sin CSRF) para las notificaciones de Stripe.
 * La autenticidad se valida mediante el header Stripe-Signature; nunca se
 * confirma un pago por otra vía.
 */
final class StripeWebhookController extends WebhookController
{
    public function handle(): void
    {
        $rawBody = (string) file_get_contents('php://input');
        $signatureHeader = (string) ($_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '');

        $stripe = new StripePaymentService();

        if (!$stripe->hasWebhookSecret()) {
            ErrorHandler::log('Stripe webhook received without STRIPE_WEBHOOK_SECRET configurado');
            http_response_code(200);
            echo 'not configured';
            return;
        }

        if (!$stripe->verifySignature($rawBody, $signatureHeader)) {
            ErrorHandler::log('Stripe webhook signature inválida');
            http_response_code(401);
            echo 'invalid signature';
            return;
        }

        $event = json_decode($rawBody, true);

        if (!is_array($event) || !isset($event['type'], $event['id'])) {
            http_response_code(400);
            echo 'invalid payload';
            return;
        }

        $eventType = (string) $event['type'];
        // Stripe garantiza que 'id' (evt_...) es único por evento: es la clave de idempotencia recomendada.
        $providerEventId = (string) $event['id'];
        $payloadHash = hash('sha256', 'stripe:' . $providerEventId);

        $this->processIdempotently('stripe', $eventType, $providerEventId, $payloadHash, $rawBody, function () use ($stripe, $eventType, $event): ?int {
            $object = $event['data']['object'] ?? [];
            $object = is_array($object) ? $object : [];

            $status = $stripe->mapStatus($eventType, $object);

            if ($status === 'unknown') {
                // Evento de Stripe reconocido pero irrelevante para nuestro flujo de pagos.
                return null;
            }

            $paymentUuid = $object['metadata']['payment_uuid'] ?? $object['client_reference_id'] ?? null;
            $providerPaymentId = $object['payment_intent'] ?? $object['id'] ?? null;

            $amount = null;
            if (isset($object['amount_total'])) {
                $amount = ((float) $object['amount_total']) / 100;
            } elseif (isset($object['amount'])) {
                $amount = ((float) $object['amount']) / 100;
            }

            $currency = isset($object['currency']) ? strtoupper((string) $object['currency']) : null;

            $result = (new PaymentService())->applyStatusUpdate(
                'stripe',
                $providerPaymentId !== null ? (string) $providerPaymentId : null,
                $paymentUuid !== null ? (string) $paymentUuid : null,
                $status,
                $amount,
                $currency,
                ['stripe_event_type' => $eventType]
            );

            return $result['payment_id'] ?? null;
        });
    }
}
