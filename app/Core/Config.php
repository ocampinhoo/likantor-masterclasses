<?php

declare(strict_types=1);

namespace App\Core;

final class Config
{
    /** @var array<string, mixed> */
    private static array $cache = [];

    /**
     * @return array<string, mixed>
     */
    public static function get(string $file): array
    {
        if (!isset(self::$cache[$file])) {
            $path = dirname(__DIR__, 2) . '/config/' . $file . '.php';

            if (!is_file($path)) {
                throw new \RuntimeException("Config file not found: {$file}");
            }

            /** @var array<string, mixed> $config */
            $config = require $path;
            self::$cache[$file] = $config;
        }

        return self::$cache[$file];
    }

    public static function app(string $key, mixed $default = null): mixed
    {
        return self::get('app')[$key] ?? $default;
    }

    public static function security(string $key, mixed $default = null): mixed
    {
        return self::get('security')[$key] ?? $default;
    }
}
