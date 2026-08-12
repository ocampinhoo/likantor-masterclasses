<?php

declare(strict_types=1);

namespace App\Controllers\User;

use App\Core\Controller;
use App\Repositories\EnrollmentRepository;
use App\Services\AuthService;

final class DashboardController extends Controller
{
    public function index(): void
    {
        $auth = new AuthService();
        $user = $auth->user();
        $enrollments = [];

        try {
            $enrollments = (new EnrollmentRepository())->findByUserId((int) $user['id']);
        } catch (\Throwable) {
            // BD no disponible
        }

        $this->view('user/dashboard', [
            'title' => 'Mi cuenta',
            'user' => $user,
            'enrollments' => $enrollments,
        ]);
    }
}
