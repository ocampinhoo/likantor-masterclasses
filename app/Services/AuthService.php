<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use App\Core\ErrorHandler;
use App\Core\RateLimiter;
use App\Core\Session;
use App\Repositories\EmailVerificationTokenRepository;
use App\Repositories\PasswordResetTokenRepository;
use App\Repositories\RememberTokenRepository;
use App\Repositories\UserRepository;

/**
 * Autenticación, registro, verificación de email, recuperación de contraseña
 * y "recuérdame" persistente.
 *
 * Principios de seguridad aplicados en esta clase:
 * - Nunca se revela si un email existe o no (registro y recuperación de contraseña
 *   siempre responden de forma genérica hacia el exterior).
 * - Las contraseñas nunca se almacenan ni se registran en texto plano.
 * - Los tokens (verificación de email, reset de contraseña) se guardan como hash
 *   (SHA-256), son de un solo uso y expiran.
 * - El "recuérdame" usa el patrón selector/validador, con rotación en cada uso.
 * - session_regenerate_id() se invoca en todo cambio de estado de autenticación.
 */
final class AuthService
{
    public const REMEMBER_COOKIE = 'likantor_remember';

    private UserRepository $users;

    public function __construct()
    {
        $this->users = new UserRepository();
    }

    public function check(): bool
    {
        return !empty($_SESSION['user_id']);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function user(): ?array
    {
        if (!$this->check()) {
            return null;
        }

        return $this->users->findById((int) $_SESSION['user_id']);
    }

    public function id(): ?int
    {
        return $this->check() ? (int) $_SESSION['user_id'] : null;
    }

    public function isAdmin(): bool
    {
        $user = $this->user();

        return $user !== null && in_array($user['role'], ['admin', 'super_admin'], true);
    }

    public function isSuperAdmin(): bool
    {
        $user = $this->user();

        return $user !== null && $user['role'] === 'super_admin';
    }

    // ------------------------------------------------------------------
    // Registro
    // ------------------------------------------------------------------

    /**
     * Registra un usuario nuevo. Por diseño, el resultado NUNCA distingue entre
     * "el correo ya existía" y "se creó la cuenta": ambos casos responden success=true
     * para evitar enumeración de usuarios. Solo se reportan errores de validación
     * de formato (que no revelan nada sobre cuentas existentes).
     *
     * @return array{success: bool, message?: string}
     */
    public function register(string $name, string $email, string $password, string $passwordConfirm, bool $privacyAccepted): array
    {
        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');

        if (RateLimiter::tooManyAttempts('register_' . $ip, (int) Config::security('register_max_attempts', 5), (int) Config::security('register_lockout_minutes', 60) * 60)) {
            return ['success' => false, 'message' => 'Demasiados intentos de registro. Intenta de nuevo más tarde.'];
        }

        $name = sanitize_string($name, 150);
        $email = sanitize_email($email);
        $minLength = (int) Config::security('password_min_length', 10);

        if ($name === '' || mb_strlen($name) < 2) {
            return ['success' => false, 'message' => 'Ingresa tu nombre completo.'];
        }

        if (!validate_email($email)) {
            return ['success' => false, 'message' => 'Ingresa un correo electrónico válido.'];
        }

        if (strlen($password) < $minLength) {
            return ['success' => false, 'message' => "La contraseña debe tener al menos {$minLength} caracteres."];
        }

        if ($password !== $passwordConfirm) {
            return ['success' => false, 'message' => 'Las contraseñas no coinciden.'];
        }

        if (!$privacyAccepted) {
            return ['success' => false, 'message' => 'Debes aceptar el Aviso de Privacidad y los Términos y Condiciones.'];
        }

        try {
            $existing = $this->users->findByEmail($email);

            if ($existing === null) {
                $userId = $this->users->create([
                    'uuid' => $this->generateUuid(),
                    'name' => $name,
                    'email' => $email,
                    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                    'role' => 'user',
                    'privacy_accepted_at' => date('Y-m-d H:i:s'),
                ]);

                $this->sendVerificationEmail($userId, $email, $name);
            }
            // Si el correo ya existía, no se crea una segunda cuenta ni se revela nada:
            // la respuesta hacia el usuario es idéntica en ambos casos.
        } catch (\Throwable $e) {
            ErrorHandler::log('Register failed', ['error' => $e->getMessage()]);

            return ['success' => false, 'message' => 'No pudimos completar tu registro. Intenta de nuevo en unos minutos.'];
        }

        return ['success' => true];
    }

    // ------------------------------------------------------------------
    // Login / logout
    // ------------------------------------------------------------------

    /**
     * @return array{success: bool, message?: string}
     */
    public function login(string $email, string $password, bool $remember = false): array
    {
        $email = sanitize_email($email);
        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
        $rateLimitKey = 'login_' . md5($email . '|' . $ip);

        if (RateLimiter::tooManyAttempts($rateLimitKey, (int) Config::security('login_max_attempts', 5), (int) Config::security('login_lockout_minutes', 15) * 60)) {
            return ['success' => false, 'message' => 'Demasiados intentos. Intenta de nuevo más tarde.'];
        }

        $user = $this->users->findByEmail($email);

        // Siempre ejecutamos password_verify (incluso con un hash "dummy" pero
        // válido) para que el tiempo de respuesta no delate si el correo existe o no.
        $hashToVerify = $user['password_hash'] ?? '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
        $passwordValid = password_verify($password, $hashToVerify);

        if ($user === null || !$passwordValid || !(bool) $user['is_active']) {
            return ['success' => false, 'message' => 'Credenciales incorrectas.'];
        }

        RateLimiter::reset($rateLimitKey);
        Session::regenerate();

        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['user_role'] = $user['role'];

        $this->users->updateLastLogin((int) $user['id']);

        if ($remember) {
            $this->createRememberCookie((int) $user['id']);
        }

        return ['success' => true];
    }

    public function logout(): void
    {
        $this->forgetRememberCookie();

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
    }

    // ------------------------------------------------------------------
    // "Recuérdame" (selector/validador, revocable y rotativo)
    // ------------------------------------------------------------------

    /**
     * Intenta autenticar al visitante a partir de la cookie "recuérdame" si no
     * tiene ya una sesión activa. Rota el validador en cada uso exitoso y, si
     * detecta un validador ya usado (indicio de robo de cookie), revoca todos
     * los tokens del usuario por precaución.
     */
    public function attemptLoginFromRememberCookie(): bool
    {
        if ($this->check()) {
            return true;
        }

        $cookie = $_COOKIE[self::REMEMBER_COOKIE] ?? null;

        if (!is_string($cookie) || !str_contains($cookie, ':')) {
            return false;
        }

        [$selector, $validator] = explode(':', $cookie, 2);

        if ($selector === '' || $validator === '') {
            $this->forgetRememberCookie();
            return false;
        }

        try {
            $repo = new RememberTokenRepository();
            $token = $repo->findValidBySelector($selector);

            if ($token === null) {
                $this->forgetRememberCookie();
                return false;
            }

            $validatorHash = hash('sha256', $validator);

            if (!hash_equals($token['validator_hash'], $validatorHash)) {
                // El selector es válido pero el validador no coincide: posible robo de
                // cookie (reuso de un token ya rotado). Revocamos todo por precaución.
                $repo->deleteAllForUser((int) $token['user_id']);
                $this->forgetRememberCookie();
                ErrorHandler::log('Remember-me validator mismatch (posible robo de cookie)', ['user_id' => $token['user_id']]);

                return false;
            }

            $user = $this->users->findById((int) $token['user_id']);

            if ($user === null || !(bool) $user['is_active']) {
                $repo->deleteBySelector($selector);
                $this->forgetRememberCookie();

                return false;
            }

            Session::regenerate();
            $_SESSION['user_id'] = (int) $user['id'];
            $_SESSION['user_role'] = $user['role'];
            $this->users->updateLastLogin((int) $user['id']);

            // Rotación: se emite un nuevo validador para este mismo selector.
            $newValidator = bin2hex(random_bytes(32));
            $ttlDays = (int) Config::security('remember_me_ttl_days', 30);
            $expiresAt = date('Y-m-d H:i:s', time() + ($ttlDays * 86400));
            $repo->updateValidator((int) $token['id'], hash('sha256', $newValidator), $expiresAt);
            $this->setRememberCookie($selector, $newValidator, $ttlDays);

            return true;
        } catch (\Throwable $e) {
            ErrorHandler::log('attemptLoginFromRememberCookie failed', ['error' => $e->getMessage()]);

            return false;
        }
    }

    private function createRememberCookie(int $userId): void
    {
        try {
            $selector = bin2hex(random_bytes(12));
            $validator = bin2hex(random_bytes(32));
            $ttlDays = (int) Config::security('remember_me_ttl_days', 30);
            $expiresAt = date('Y-m-d H:i:s', time() + ($ttlDays * 86400));

            (new RememberTokenRepository())->create($userId, $selector, hash('sha256', $validator), $expiresAt);
            $this->setRememberCookie($selector, $validator, $ttlDays);
        } catch (\Throwable $e) {
            // Si falla, simplemente no se recuerda la sesión; no debe romper el login.
            ErrorHandler::log('createRememberCookie failed', ['error' => $e->getMessage()]);
        }
    }

    private function setRememberCookie(string $selector, string $validator, int $ttlDays): void
    {
        setcookie(self::REMEMBER_COOKIE, $selector . ':' . $validator, [
            'expires' => time() + ($ttlDays * 86400),
            'path' => '/',
            'secure' => Session::isHttps(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    private function forgetRememberCookie(): void
    {
        $cookie = $_COOKIE[self::REMEMBER_COOKIE] ?? null;

        if (is_string($cookie) && str_contains($cookie, ':')) {
            [$selector] = explode(':', $cookie, 2);

            try {
                (new RememberTokenRepository())->deleteBySelector($selector);
            } catch (\Throwable) {
                // Ignorar: el token expirará por sí solo.
            }
        }

        if (isset($_COOKIE[self::REMEMBER_COOKIE])) {
            setcookie(self::REMEMBER_COOKIE, '', [
                'expires' => time() - 42000,
                'path' => '/',
                'secure' => Session::isHttps(),
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            unset($_COOKIE[self::REMEMBER_COOKIE]);
        }
    }

    // ------------------------------------------------------------------
    // Verificación de email
    // ------------------------------------------------------------------

    public function sendVerificationEmail(int $userId, string $email, string $name): void
    {
        try {
            (new EmailVerificationTokenRepository())->invalidateAllForUser($userId);

            $token = bin2hex(random_bytes(32));
            $ttlHours = (int) Config::security('email_verification_token_ttl_hours', 24);
            $expiresAt = date('Y-m-d H:i:s', time() + ($ttlHours * 3600));

            (new EmailVerificationTokenRepository())->create($userId, hash('sha256', $token), $expiresAt);

            $verifyUrl = url('/verificar-email/' . $token);

            $emailService = new EmailService();
            $queueId = $emailService->queueEmail('email_verification', $email, $name, [
                'name' => $name,
                'verify_url' => $verifyUrl,
                'expires_in' => $ttlHours . ' horas',
            ]);
            $emailService->sendQueuedImmediately($queueId, $userId);
        } catch (\Throwable $e) {
            ErrorHandler::log('sendVerificationEmail failed', ['user_id' => $userId, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Reenvía el correo de verificación. Respuesta silenciosa: no revela si el
     * correo existe, si ya está verificado, ni ningún otro detalle.
     */
    public function resendVerification(string $email): void
    {
        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
        $email = sanitize_email($email);

        if (RateLimiter::tooManyAttempts('verify_resend_' . md5($email . '|' . $ip), 3, 900)) {
            return;
        }

        try {
            $user = $this->users->findByEmail($email);

            if ($user !== null && $user['email_verified_at'] === null) {
                $this->sendVerificationEmail((int) $user['id'], $user['email'], $user['name']);
            }
        } catch (\Throwable $e) {
            ErrorHandler::log('resendVerification failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * @return array{success: bool, message?: string}
     */
    public function verifyEmail(string $token): array
    {
        if ($token === '') {
            return ['success' => false, 'message' => 'Enlace de verificación inválido.'];
        }

        try {
            $repo = new EmailVerificationTokenRepository();
            $record = $repo->findValidByHash(hash('sha256', $token));

            if ($record === null) {
                return ['success' => false, 'message' => 'Este enlace de verificación no es válido o ya expiró.'];
            }

            $repo->markUsed((int) $record['id']);
            $this->users->markEmailVerified((int) $record['user_id']);

            $user = $this->users->findById((int) $record['user_id']);

            if ($user !== null && (bool) $user['is_active']) {
                Session::regenerate();
                $_SESSION['user_id'] = (int) $user['id'];
                $_SESSION['user_role'] = $user['role'];
            }

            return ['success' => true];
        } catch (\Throwable $e) {
            ErrorHandler::log('verifyEmail failed', ['error' => $e->getMessage()]);

            return ['success' => false, 'message' => 'No pudimos verificar tu correo. Intenta de nuevo.'];
        }
    }

    // ------------------------------------------------------------------
    // Recuperación de contraseña
    // ------------------------------------------------------------------

    /**
     * Solicita el restablecimiento de contraseña. No revela si el correo existe:
     * el controlador debe mostrar siempre el mismo mensaje genérico sin importar
     * el resultado interno de este método.
     */
    public function requestPasswordReset(string $email): void
    {
        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
        $email = sanitize_email($email);

        if (RateLimiter::tooManyAttempts('pwreset_' . md5($email . '|' . $ip), (int) Config::security('password_reset_max_attempts', 3), (int) Config::security('password_reset_lockout_minutes', 15) * 60)) {
            return;
        }

        try {
            $user = $this->users->findByEmail($email);

            if ($user === null || !(bool) $user['is_active']) {
                return;
            }

            $repo = new PasswordResetTokenRepository();
            $repo->invalidateAllForUser((int) $user['id']);

            $token = bin2hex(random_bytes(32));
            $ttlMinutes = (int) Config::security('password_reset_token_ttl_minutes', 60);
            $expiresAt = date('Y-m-d H:i:s', time() + ($ttlMinutes * 60));

            $repo->create((int) $user['id'], hash('sha256', $token), $expiresAt);

            $resetUrl = url('/restablecer-contrasena/' . $token);
            $expiresLabel = $ttlMinutes >= 60 ? round($ttlMinutes / 60) . ' horas' : $ttlMinutes . ' minutos';

            $emailService = new EmailService();
            $queueId = $emailService->queueEmail('password_reset', $user['email'], $user['name'], [
                'name' => $user['name'],
                'reset_url' => $resetUrl,
                'expires_in' => $expiresLabel,
            ]);
            $emailService->sendQueuedImmediately($queueId, (int) $user['id']);
        } catch (\Throwable $e) {
            ErrorHandler::log('requestPasswordReset failed', ['error' => $e->getMessage()]);
        }
    }

    public function isPasswordResetTokenValid(string $token): bool
    {
        if ($token === '') {
            return false;
        }

        try {
            return (new PasswordResetTokenRepository())->findValidByHash(hash('sha256', $token)) !== null;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @return array{success: bool, message?: string}
     */
    public function resetPassword(string $token, string $password, string $passwordConfirm): array
    {
        $minLength = (int) Config::security('password_min_length', 10);

        if (strlen($password) < $minLength) {
            return ['success' => false, 'message' => "La contraseña debe tener al menos {$minLength} caracteres."];
        }

        if ($password !== $passwordConfirm) {
            return ['success' => false, 'message' => 'Las contraseñas no coinciden.'];
        }

        try {
            $repo = new PasswordResetTokenRepository();
            $record = $repo->findValidByHash(hash('sha256', $token));

            if ($record === null) {
                return ['success' => false, 'message' => 'Este enlace no es válido o ya expiró. Solicita uno nuevo.'];
            }

            $userId = (int) $record['user_id'];

            $this->users->updatePasswordHash($userId, password_hash($password, PASSWORD_DEFAULT));
            // Poseer el enlace de recuperación enviado al correo ya demuestra su
            // titularidad, así que aprovechamos para marcarlo como verificado.
            $this->users->markEmailVerified($userId);
            $repo->markUsed((int) $record['id']);
            $repo->invalidateAllForUser($userId);

            // Cambiar la contraseña cierra la sesión "recuérdame" en todos los dispositivos.
            (new RememberTokenRepository())->deleteAllForUser($userId);

            $user = $this->users->findById($userId);

            if ($user !== null && (bool) $user['is_active']) {
                Session::regenerate();
                $_SESSION['user_id'] = $userId;
                $_SESSION['user_role'] = $user['role'];
            }

            return ['success' => true];
        } catch (\Throwable $e) {
            ErrorHandler::log('resetPassword failed', ['error' => $e->getMessage()]);

            return ['success' => false, 'message' => 'No pudimos restablecer tu contraseña. Intenta de nuevo.'];
        }
    }

    // ------------------------------------------------------------------
    // Utilidades
    // ------------------------------------------------------------------

    private function generateUuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
