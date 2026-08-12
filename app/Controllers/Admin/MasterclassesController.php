<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Repositories\MasterclassRepository;

final class MasterclassesController extends Controller
{
    public function index(): void
    {
        $masterclasses = (new MasterclassRepository())->all();

        $this->view('admin/masterclasses/index', [
            'title' => 'Masterclasses',
            'masterclasses' => $masterclasses,
        ], 'admin');
    }
}
