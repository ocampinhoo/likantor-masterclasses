<?php

declare(strict_types=1);

namespace App\Controllers\Webhooks;

use App\Core\ErrorHandler;
use App\Services\MercadoPagoPaymentService;
use App\Services\PaymentService;

/**
 * Endpoint público (sin login, sin CSRF) para las notificaciones de Mercado
 * Pago. La autenticidad se valida mediante el header `x-signature`; el
 * detalle real del pago siempre se re-consulta a la API de Mercado Pago
 * (nunca se confía en el cuerpo del webhook por sí solo).
 */
final class MercadoPagoWebhookController extends WebhookController
{
    public function handle(): void
    {
        $rawBody = (string) file_get_contents('php://input');
        $requestId = (string) ($_SERVER['HTTP_X_REQUEST_ID'] ?? '');
        $signatureHeader = (string) ($_SERVER['HTTP_X_SIGNATURE'] ?? '');

        $decoded = json_decode($rawBody, true);
        $decoded = is_array($decoded) ? $decoded : [];

        // OJO: no se usa $_GET para "data.id" porque PHP convierte automáticamente
        // los puntos (y espacios) de los nombres de parámetros de querystring en
        // guiones bajos al poblar $_GET (comportamiento heredado de register_globals),
        // así que $_GET['data.id'] nunca existiría aunque la URL real lo contenga.
        $query = self::parseRawQueryString((string) ($_SERVER['QUERY_STRING'] ?? ''));

        $dataId = $query['data.id'] ?? $query['id'] ?? ($decoded['data']['id'] ?? null);
        $type = $query['type'] ?? $query['topic'] ?? ($decoded['type'] ?? null);

        if ($dataId === null || $type !== 'payment') {
            // Otros tópicos (merchant_order, etc.) se reconocen sin procesar.
            http_response_code(200);
            echo 'ignored';
            return;
        }

        $dataId = (string) $dataId;
        $mercadoPago = new MercadoPagoPaymentService();

        if (!$mercadoPago->hasWebhookSecret()) {
            ErrorHandler::log('Mercado Pago webhook recibido sin MERCADOPAGO_WEBHOOK_SECRET configurado');
            http_response_code(200);
            echo 'not configured';
            return;
        }

        if (!$mercadoPago->verifySignature($dataId, $requestId, $signatureHeader)) {
            ErrorHandler::log('Mercado Pago webhook signature inválida', ['data_id' => $dataId]);
            http_response_code(401);
            echo 'invalid signature';
            return;
        }

        // El id de notificación (top-level "id" del payload) es único por evento
        // según la documentación de Mercado Pago: es nuestra clave de idempotencia.
        $providerEventId = isset($decoded['id']) ? (string) $decoded['id'] : ('legacy-' . $dataId . '-' . $type);
        $payloadHash = hash('sha256', 'mercadopago:' . $providerEventId);

        $this->processIdempotently('mercadopago', (string) $type, $providerEventId, $payloadHash, $rawBody, function () use ($mercadoPago, $dataId): ?int {
            $payment = $mercadoPago->fetchPayment($dataId);

            if ($payment === null) {
                throw new \RuntimeException('No se pudo obtener el detalle del pago desde la API de Mercado Pago.');
            }

            $status = $mercadoPago->mapStatus((string) ($payment['status'] ?? ''));
            $externalReference = $payment['external_reference'] ?? ($payment['metadata']['payment_uuid'] ?? null);
            $providerPaymentId = isset($payment['id']) ? (string) $payment['id'] : null;

            $result = (new PaymentService())->applyStatusUpdate(
                'mercadopago',
                $providerPaymentId,
                $externalReference !== null ? (string) $externalReference : null,
                $status,
                isset($payment['transaction_amount']) ? (float) $payment['transaction_amount'] : null,
                isset($payment['currency_id']) ? strtoupper((string) $payment['currency_id']) : null,
                ['mp_status' => $payment['status'] ?? null, 'mp_status_detail' => $payment['status_detail'] ?? null]
            );

            return $result['payment_id'] ?? null;
        });
    }

    /**
     * Parser manual de querystring que preserva puntos en los nombres de
     * parámetros (a diferencia de $_GET / parse_str, que los convierte a "_").
     *
     * @return array<string, string>
     */
    private static function parseRawQueryString(string $queryString): array
    {
        $result = [];

        if ($queryString === '') {
            return $result;
        }

        foreach (explode('&', $queryString) as $pair) {
            if ($pair === '') {
                continue;
            }

            $parts = explode('=', $pair, 2);
            $key = urldecode($parts[0]);
            $value = isset($parts[1]) ? urldecode($parts[1]) : '';
            $result[$key] = $value;
        }

        return $result;
    }
}
