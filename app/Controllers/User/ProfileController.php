<?php

declare(strict_types=1);

namespace App\Controllers\User;

use App\Core\Controller;
use App\Repositories\UserRepository;
use App\Services\AuthService;

final class ProfileController extends Controller
{
    public function show(): void
    {
        $auth = new AuthService();

        $this->view('user/profile', [
            'title' => 'Mi perfil',
            'user' => $auth->user(),
        ]);
    }

    public function update(): void
    {
        $auth = new AuthService();
        $user = $auth->user();

        if ($user === null) {
            $this->redirect('/login');
        }

        $name = sanitize_string($this->input('name'), 150);
        $email = sanitize_email($this->input('email'));
        $pronouns = sanitize_string($this->input('pronouns'), 50) ?: null;
        $professionalTitle = sanitize_string($this->input('professional_title'), 150) ?: null;
        $ageInput = $this->input('age');
        $age = ($ageInput !== null && $ageInput !== '') ? (int) $ageInput : null;

        $errors = [];

        if ($name === '') {
            $errors[] = 'El nombre es obligatorio.';
        }

        if (!validate_email($email)) {
            $errors[] = 'El correo electrónico no es válido.';
        }

        if ($age !== null && ($age < 1 || $age > 120)) {
            $errors[] = 'La edad no es válida.';
        }

        $userRepo = new UserRepository();
        $existing = $userRepo->findByEmail($email);

        if ($existing !== null && (int) $existing['id'] !== (int) $user['id']) {
            $errors[] = 'Este correo ya está en uso.';
        }

        if ($errors !== []) {
            $this->flash('error', implode(' ', $errors));
            $this->redirect('/mi-cuenta/perfil');
        }

        $userRepo->update((int) $user['id'], [
            'name' => $name,
            'email' => $email,
            'pronouns' => $pronouns,
            'professional_title' => $professionalTitle,
            'age' => $age,
        ]);

        $this->flash('success', 'Perfil actualizado correctamente.');
        $this->redirect('/mi-cuenta/perfil');
    }
}
