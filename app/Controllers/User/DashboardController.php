<?php

declare(strict_types=1);

namespace App\Controllers\User;

use App\Core\Controller;
use App\Services\AuthService;
use App\Services\MasterclassAccessService;

final class DashboardController extends Controller
{
    public function index(): void
    {
        $auth = new AuthService();
        $user = $auth->user();
        $cards = [];

        try {
            $cards = (new MasterclassAccessService())->dashboardCardsForUser((int) $user['id']);
        } catch (\Throwable) {
            // BD no disponible
        }

        $this->view('user/dashboard', [
            'title' => 'Mi cuenta',
            'user' => $user,
            'cards' => $cards,
        ]);
    }
}
