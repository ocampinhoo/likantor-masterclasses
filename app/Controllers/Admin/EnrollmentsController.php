<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Repositories\EnrollmentRepository;

final class EnrollmentsController extends Controller
{
    public function index(): void
    {
        $enrollments = [];

        try {
            $enrollments = (new EnrollmentRepository())->recentWithDetails(300);
        } catch (\Throwable) {
            // BD no disponible
        }

        $this->view('admin/enrollments/index', [
            'title' => 'Registros',
            'enrollments' => $enrollments,
        ], 'admin');
    }
}
