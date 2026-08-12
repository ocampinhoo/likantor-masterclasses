<?php

declare(strict_types=1);

require __DIR__ . '/init.php';

use App\Core\ErrorHandler;
use App\Core\Router;
use App\Core\Session;
use App\Services\AuthService;

ErrorHandler::register();
Session::start();

// Auto-login mediante cookie "recuérdame" (si no hay sesión activa). Se evita
// instanciar el servicio (y abrir conexión a BD) cuando no hace falta.
if (empty($_SESSION['user_id']) && !empty($_COOKIE[AuthService::REMEMBER_COOKIE])) {
    (new AuthService())->attemptLoginFromRememberCookie();
}

/** @var Router $router */
$router = require BASE_PATH . '/app/routes.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uri = $_SERVER['REQUEST_URI'] ?? '/';

$router->dispatch($method, $uri);
