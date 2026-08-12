<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Services\AuthService;

final class AdminMiddleware extends Middleware
{
    public function handle(): bool
    {
        $auth = new AuthService();

        if (!$auth->check()) {
            header('Location: ' . url('/login'));
            return false;
        }

        if (!$auth->isAdmin()) {
            http_response_code(403);
            echo 'Acceso denegado.';
            return false;
        }

        return true;
    }
}
