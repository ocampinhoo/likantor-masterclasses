<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Services\AuthService;

final class AuthMiddleware extends Middleware
{
    public function handle(): bool
    {
        $auth = new AuthService();

        if (!$auth->check()) {
            $_SESSION['_intended_url'] = $_SERVER['REQUEST_URI'] ?? '/mi-cuenta';
            header('Location: ' . url('/login'));
            return false;
        }

        return true;
    }
}
