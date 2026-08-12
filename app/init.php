<?php

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

$autoload = BASE_PATH . '/vendor/autoload.php';

if (is_file($autoload)) {
    require $autoload;
} else {
    spl_autoload_register(static function (string $class): void {
        $prefix = 'App\\';
        if (!str_starts_with($class, $prefix)) {
            return;
        }
        $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
        $file = BASE_PATH . '/app/' . $relative . '.php';
        if (is_file($file)) {
            require $file;
        }
    });
    require BASE_PATH . '/app/Helpers/functions.php';
}

use App\Core\Env;
use Dotenv\Dotenv;

if (class_exists(Dotenv::class) && is_file(BASE_PATH . '/.env')) {
    Dotenv::createImmutable(BASE_PATH)->safeLoad();
} elseif (is_file(BASE_PATH . '/.env')) {
    Env::load(BASE_PATH . '/.env');
}

date_default_timezone_set((string) env('APP_TIMEZONE', 'America/Mexico_City'));
