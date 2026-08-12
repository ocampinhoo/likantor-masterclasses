<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;

final class SettingsController extends Controller
{
    public function index(): void
    {
        $this->view('admin/settings/index', [
            'title' => 'Configuración',
        ], 'admin');
    }
}
