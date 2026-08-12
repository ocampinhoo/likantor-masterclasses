<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Repositories\LeadRepository;

final class LeadsController extends Controller
{
    public function index(): void
    {
        $leads = (new LeadRepository())->all();

        $this->view('admin/leads/index', [
            'title' => 'Leads',
            'leads' => $leads,
        ], 'admin');
    }
}
