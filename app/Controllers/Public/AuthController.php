<?php

declare(strict_types=1);

namespace App\Controllers\Public;

use App\Core\Controller;
use App\Services\AuthService;

final class AuthController extends Controller
{
    private AuthService $auth;

    public function __construct()
    {
        $this->auth = new AuthService();
    }

    // ------------------------------------------------------------------
    // Login
    // ------------------------------------------------------------------

    public function showLogin(): void
    {
        $this->view('auth/login', ['title' => 'Iniciar sesión']);
    }

    public function login(): void
    {
        $email = sanitize_email($this->input('email'));
        $password = (string) $this->input('password', '');
        $remember = $this->input('remember') === '1';

        $result = $this->auth->login($email, $password, $remember);

        if (!$result['success']) {
            $this->rememberInput(['email' => $email]);
            $this->flash('error', $result['message'] ?? 'Error al iniciar sesión.');
            $this->redirect('/login');
        }

        $this->clearOldInput();

        $intended = $_SESSION['_intended_url'] ?? null;
        unset($_SESSION['_intended_url']);

        if (is_string($intended) && str_starts_with($intended, '/')) {
            $this->redirect($intended);
        }

        $redirect = $this->auth->isAdmin() ? '/admin' : '/mi-cuenta';
        $this->redirect($redirect);
    }

    public function logout(): void
    {
        $this->auth->logout();
        $this->redirect('/');
    }

    // ------------------------------------------------------------------
    // Registro
    // ------------------------------------------------------------------

    public function showRegister(): void
    {
        $this->view('auth/register', ['title' => 'Crear cuenta']);
    }

    public function register(): void
    {
        $name = (string) $this->input('name', '');
        $email = (string) $this->input('email', '');
        $password = (string) $this->input('password', '');
        $passwordConfirm = (string) $this->input('password_confirm', '');
        $privacy = $this->input('privacy') === '1';

        $result = $this->auth->register($name, $email, $password, $passwordConfirm, $privacy);

        if (!$result['success']) {
            $this->rememberInput(['name' => sanitize_string($name), 'email' => sanitize_email($email)]);
            $this->flash('error', $result['message'] ?? 'No pudimos completar tu registro.');
            $this->redirect('/registro');
        }

        $this->clearOldInput();

        // Mensaje idéntico exista o no la cuenta previamente: evita enumeración de usuarios.
        $this->flash('success', 'Si tus datos son correctos, hemos enviado un correo de verificación a tu bandeja de entrada.');
        $this->redirect('/verificar-email');
    }

    // ------------------------------------------------------------------
    // Verificación de email
    // ------------------------------------------------------------------

    public function showVerifyNotice(): void
    {
        $this->view('auth/verify-notice', ['title' => 'Verifica tu correo']);
    }

    public function verifyEmail(string $token): void
    {
        $result = $this->auth->verifyEmail($token);

        $this->view('auth/verify-result', [
            'title' => $result['success'] ? 'Correo verificado' : 'Enlace inválido',
            'success' => $result['success'],
            'message' => $result['message'] ?? null,
        ]);
    }

    public function resendVerification(): void
    {
        $email = sanitize_email($this->input('email', ''));
        $this->auth->resendVerification($email);

        $this->flash('success', 'Si el correo existe y aún no ha sido verificado, te enviamos un nuevo enlace.');
        $this->redirect('/verificar-email');
    }

    // ------------------------------------------------------------------
    // Recuperación de contraseña
    // ------------------------------------------------------------------

    public function showForgotPassword(): void
    {
        $this->view('auth/forgot-password', ['title' => 'Recuperar contraseña']);
    }

    public function forgotPassword(): void
    {
        $email = sanitize_email($this->input('email', ''));
        $this->auth->requestPasswordReset($email);

        // Mensaje genérico: nunca confirma si el correo existe en el sistema.
        $this->flash('success', 'Si el correo existe en nuestro sistema, recibirás instrucciones para restablecer tu contraseña.');
        $this->redirect('/recuperar-contrasena');
    }

    public function showResetPassword(string $token): void
    {
        if (!$this->auth->isPasswordResetTokenValid($token)) {
            $this->view('auth/reset-password-invalid', ['title' => 'Enlace inválido']);
            return;
        }

        $this->view('auth/reset-password', ['title' => 'Restablecer contraseña', 'token' => $token]);
    }

    public function resetPassword(string $token): void
    {
        $password = (string) $this->input('password', '');
        $passwordConfirm = (string) $this->input('password_confirm', '');

        $result = $this->auth->resetPassword($token, $password, $passwordConfirm);

        if (!$result['success']) {
            $this->flash('error', $result['message'] ?? 'No pudimos restablecer tu contraseña.');
            $this->redirect('/restablecer-contrasena/' . $token);
        }

        $this->flash('success', 'Tu contraseña se actualizó correctamente.');
        $this->redirect('/mi-cuenta');
    }
}
