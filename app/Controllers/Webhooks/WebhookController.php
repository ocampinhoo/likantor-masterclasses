<?php

declare(strict_types=1);

namespace App\Controllers\Webhooks;

use App\Core\Controller;
use App\Core\ErrorHandler;
use App\Repositories\PaymentEventRepository;

/**
 * Base compartida por los controladores de webhooks de pago. Los endpoints
 * de webhook NO requieren sesión/login (los llama el proveedor de pago), pero
 * sí deben validar la autenticidad de cada petición (ver StripeWebhookController
 * y MercadoPagoWebhookController) y son siempre idempotentes.
 */
abstract class WebhookController extends Controller
{
    /**
     * Garantiza que cada evento se procese como máximo una vez, incluso si el
     * proveedor reenvía la misma notificación varias veces (los proveedores
     * reintentan webhooks agresivamente hasta recibir un 200 OK).
     *
     * @param callable(): ?int $processor Debe devolver el id del pago afectado (o null) y lanzar una excepción si falla.
     */
    protected function processIdempotently(
        string $provider,
        string $eventType,
        ?string $providerEventId,
        string $payloadHash,
        string $rawPayload,
        callable $processor
    ): void {
        $events = new PaymentEventRepository();
        $existing = $events->findByHash($provider, $payloadHash);

        if ($existing !== null && (bool) $existing['processed']) {
            http_response_code(200);
            echo 'already processed';
            return;
        }

        if ($existing === null) {
            try {
                $eventId = $events->create([
                    'provider' => $provider,
                    'event_type' => $eventType,
                    'provider_event_id' => $providerEventId,
                    'payload_hash' => $payloadHash,
                    'payload' => $rawPayload,
                ]);
            } catch (\Throwable $e) {
                // Condición de carrera: otra petición concurrente insertó el mismo hash primero.
                $existing = $events->findByHash($provider, $payloadHash);

                if ($existing === null) {
                    ErrorHandler::log('Webhook event insert failed', ['provider' => $provider, 'error' => $e->getMessage()]);
                    http_response_code(500);
                    echo 'error';
                    return;
                }

                if ((bool) $existing['processed']) {
                    http_response_code(200);
                    echo 'already processed';
                    return;
                }

                $eventId = (int) $existing['id'];
            }
        } else {
            $eventId = (int) $existing['id'];
        }

        try {
            $paymentId = $processor();
            $events->markProcessed($eventId, $paymentId);
            http_response_code(200);
            echo 'ok';
        } catch (\Throwable $e) {
            $events->markError($eventId, $e->getMessage());
            ErrorHandler::log('Webhook processing failed', [
                'provider' => $provider,
                'event_type' => $eventType,
                'error' => $e->getMessage(),
            ]);
            // 500 para que el proveedor reintente más tarde (el evento ya quedó
            // registrado, así que el reintento no duplicará nada al reprocesarse).
            http_response_code(500);
            echo 'error';
        }
    }
}
