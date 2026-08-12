<?php

declare(strict_types=1);

namespace App\Core;

abstract class Controller
{
    protected function view(string $template, array $data = [], ?string $layout = 'main'): void
    {
        View::render($template, $data, $layout);
    }

    protected function redirect(string $path, int $code = 302): never
    {
        $base = Config::app('url', '');
        $location = str_starts_with($path, 'http') ? $path : $base . $path;
        header('Location: ' . $location, true, $code);
        exit;
    }

    protected function json(array $data, int $code = 200): never
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * @return array<string, mixed>
     */
    protected function input(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }

    protected function flash(string $key, ?string $message = null): ?string
    {
        if ($message !== null) {
            $_SESSION['_flash'][$key] = $message;
            return null;
        }

        $value = $_SESSION['_flash'][$key] ?? null;
        unset($_SESSION['_flash'][$key]);

        return is_string($value) ? $value : null;
    }

    protected function old(string $key, string $default = ''): string
    {
        $value = $_SESSION['_old'][$key] ?? $default;
        return is_string($value) ? $value : $default;
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function rememberInput(array $data): void
    {
        $_SESSION['_old'] = $data;
    }

    protected function clearOldInput(): void
    {
        unset($_SESSION['_old']);
    }
}
