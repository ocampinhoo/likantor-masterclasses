<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;

final class EmailsController extends Controller
{
    public function index(): void
    {
        $this->view('admin/emails/index', [
            'title' => 'Emails',
            'message' => 'La integración con SendGrid se implementará en la fase 3.',
        ], 'admin');
    }
}
