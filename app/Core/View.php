<?php

declare(strict_types=1);

namespace App\Core;

final class View
{
    /**
     * @param array<string, mixed> $data
     */
    public static function render(string $template, array $data = [], ?string $layout = 'main'): void
    {
        $viewsPath = dirname(__DIR__) . '/Views';
        $templateFile = $viewsPath . '/' . str_replace('.', '/', $template) . '.php';

        if (!is_file($templateFile)) {
            throw new \RuntimeException("View not found: {$template}");
        }

        extract($data, EXTR_SKIP);

        ob_start();
        require $templateFile;
        $content = ob_get_clean();

        if ($layout === null) {
            echo $content;
            return;
        }

        $layoutFile = $viewsPath . '/layouts/' . $layout . '.php';

        if (!is_file($layoutFile)) {
            echo $content;
            return;
        }

        require $layoutFile;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function renderAdmin(string $template, array $data = []): void
    {
        self::render($template, $data, 'admin');
    }
}
