<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Captura y persiste en sesión los parámetros UTM (Meta Ads, Google Ads, etc.)
 * para que estén disponibles al momento de enviar un formulario de lead,
 * incluso si el envío ocurre después de navegar por varias páginas del sitio.
 */
final class Utm
{
    public const KEYS = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term'];

    public static function captureFromRequest(): void
    {
        $data = $_SESSION['_utm'] ?? [];
        $found = false;

        foreach (self::KEYS as $key) {
            if (isset($_GET[$key]) && is_string($_GET[$key]) && $_GET[$key] !== '') {
                $data[$key] = sanitize_string($_GET[$key], 150);
                $found = true;
            }
        }

        if (isset($_GET['campaign']) && is_string($_GET['campaign']) && $_GET['campaign'] !== '') {
            $data['campaign'] = sanitize_string($_GET['campaign'], 100);
            $found = true;
        }

        if ($found) {
            $_SESSION['_utm'] = $data;
        }
    }

    /**
     * @return array<string, string|null>
     */
    public static function fromSession(): array
    {
        $data = $_SESSION['_utm'] ?? [];
        $result = [];

        foreach (self::KEYS as $key) {
            $result[$key] = isset($data[$key]) ? (string) $data[$key] : null;
        }

        $result['campaign'] = isset($data['campaign']) ? (string) $data['campaign'] : null;

        return $result;
    }
}
