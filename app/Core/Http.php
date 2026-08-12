<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Cliente cURL genérico y ligero, reutilizado por los servicios de Stripe y
 * Mercado Pago (y por el simulador de webhooks). Se evita depender de SDKs
 * oficiales pesados para mantener compatibilidad con hosting compartido.
 */
final class Http
{
    /**
     * @param array<int, string> $headers
     * @return array{status:int, body:string, json:?array<string,mixed>, error:?string}
     */
    public static function request(string $method, string $url, array $headers = [], ?string $body = null, int $timeout = 15): array
    {
        if (!function_exists('curl_init')) {
            return ['status' => 0, 'body' => '', 'json' => null, 'error' => 'La extensión cURL de PHP no está disponible.'];
        }

        $ch = curl_init($url);

        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
        ];

        if ($body !== null) {
            $options[CURLOPT_POSTFIELDS] = $body;
        }

        curl_setopt_array($ch, $options);

        $response = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errno !== 0 || $response === false) {
            return ['status' => 0, 'body' => '', 'json' => null, 'error' => "cURL error ({$errno}): {$error}"];
        }

        $responseBody = (string) $response;
        $decoded = json_decode($responseBody, true);

        return [
            'status' => $status,
            'body' => $responseBody,
            'json' => is_array($decoded) ? $decoded : null,
            'error' => null,
        ];
    }
}
