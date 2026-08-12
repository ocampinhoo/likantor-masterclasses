<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Repositories\EnrollmentRepository;
use App\Repositories\LeadRepository;
use App\Repositories\MasterclassRepository;
use App\Repositories\UserRepository;

final class DashboardController extends Controller
{
    public function index(): void
    {
        $stats = [
            'users' => 0,
            'masterclasses' => 0,
            'enrollments' => 0,
            'enrollments_paid' => 0,
            'leads' => 0,
        ];

        try {
            $stats['users'] = (new UserRepository())->count();
            $stats['masterclasses'] = (new MasterclassRepository())->count();
            $stats['enrollments'] = (new EnrollmentRepository())->count();
            $stats['enrollments_paid'] = (new EnrollmentRepository())->countPaid();
            $stats['leads'] = (new LeadRepository())->count();
        } catch (\Throwable) {
            // BD no disponible
        }

        $this->view('admin/dashboard', [
            'title' => 'Panel administrativo',
            'stats' => $stats,
        ], 'admin');
    }
}
