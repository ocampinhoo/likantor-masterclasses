<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Services\AuthService;

final class GuestMiddleware extends Middleware
{
    public function handle(): bool
    {
        $auth = new AuthService();

        if ($auth->check()) {
            $redirect = $auth->isAdmin() ? '/admin' : '/mi-cuenta';
            header('Location: ' . url($redirect));
            return false;
        }

        return true;
    }
}
