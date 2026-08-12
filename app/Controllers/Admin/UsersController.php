<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Repositories\UserRepository;

final class UsersController extends Controller
{
    public function index(): void
    {
        $users = (new UserRepository())->all();

        $this->view('admin/users/index', [
            'title' => 'Usuarios',
            'users' => $users,
        ], 'admin');
    }
}
