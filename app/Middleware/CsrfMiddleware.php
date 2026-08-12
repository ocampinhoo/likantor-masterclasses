<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Config;

final class CsrfMiddleware extends Middleware
{
    public function handle(): bool
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            return true;
        }

        $tokenName = Config::security('csrf_token_name', '_csrf_token');
        $token = $_POST[$tokenName] ?? '';

        if (!verify_csrf(is_string($token) ? $token : null)) {
            http_response_code(419);
            echo 'Token CSRF inválido o expirado.';
            return false;
        }

        return true;
    }
}
