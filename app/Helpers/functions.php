<?php

declare(strict_types=1);

/**
 * Global helper functions.
 */

use App\Core\Config;

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

        if ($value === false || $value === null || $value === '') {
            return $default;
        }

        return match (strtolower((string) $value)) {
            'true', '(true)' => true,
            'false', '(false)' => false,
            'null', '(null)' => null,
            default => $value,
        };
    }
}

if (!function_exists('e')) {
    function e(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        if (empty($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['_csrf_token'];
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        $name = Config::security('csrf_token_name', '_csrf_token');
        return '<input type="hidden" name="' . e($name) . '" value="' . e(csrf_token()) . '">';
    }
}

if (!function_exists('verify_csrf')) {
    function verify_csrf(?string $token): bool
    {
        $sessionToken = $_SESSION['_csrf_token'] ?? '';

        if (!is_string($token) || $token === '' || $sessionToken === '') {
            return false;
        }

        return hash_equals($sessionToken, $token);
    }
}

if (!function_exists('url')) {
    function url(string $path = ''): string
    {
        $base = rtrim(Config::app('url', ''), '/');
        $path = '/' . ltrim($path, '/');

        return $path === '/' ? ($base ?: '/') : $base . $path;
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        return url('/assets/' . ltrim($path, '/'));
    }
}

if (!function_exists('is_active_path')) {
    function is_active_path(string $path): bool
    {
        $current = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $current = rtrim($current, '/') ?: '/';
        $path = rtrim($path, '/') ?: '/';

        return $current === $path || str_starts_with($current, $path . '/');
    }
}

if (!function_exists('sanitize_string')) {
    function sanitize_string(?string $value, int $maxLength = 255): string
    {
        $value = trim((string) $value);
        $value = preg_replace('/\s+/', ' ', $value) ?? '';

        if (mb_strlen($value) > $maxLength) {
            $value = mb_substr($value, 0, $maxLength);
        }

        return $value;
    }
}

if (!function_exists('sanitize_email')) {
    function sanitize_email(?string $value): string
    {
        return strtolower(trim(filter_var((string) $value, FILTER_SANITIZE_EMAIL) ?: ''));
    }
}

if (!function_exists('validate_email')) {
    function validate_email(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}

if (!function_exists('uuid_v4')) {
    function uuid_v4(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}

if (!function_exists('format_ip')) {
    function format_ip(?string $binary): string
    {
        if ($binary === null || $binary === '') {
            return '—';
        }

        $ip = @inet_ntop($binary);

        return $ip !== false ? $ip : '—';
    }
}
