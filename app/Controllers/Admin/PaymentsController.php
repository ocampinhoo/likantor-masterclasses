<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Repositories\PaymentRepository;

final class PaymentsController extends Controller
{
    public function index(): void
    {
        $repo = new PaymentRepository();
        $summary = ['counts' => [], 'revenue_by_currency' => [], 'total_sales' => 0];
        $payments = [];

        try {
            $summary = $repo->summary();
            $payments = $repo->recent(300);
        } catch (\Throwable) {
            // BD no disponible
        }

        $this->view('admin/payments/index', [
            'title' => 'Pagos',
            'summary' => $summary,
            'payments' => $payments,
        ], 'admin');
    }
}
