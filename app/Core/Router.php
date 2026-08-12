<?php

declare(strict_types=1);

namespace App\Core;

final class Router
{
    /** @var array<int, array{method: string, pattern: string, handler: callable|array, middleware: array<int, string>}> */
    private array $routes = [];

    public function get(string $pattern, callable|array $handler, array $middleware = []): self
    {
        return $this->add('GET', $pattern, $handler, $middleware);
    }

    public function post(string $pattern, callable|array $handler, array $middleware = []): self
    {
        return $this->add('POST', $pattern, $handler, $middleware);
    }

    public function add(string $method, string $pattern, callable|array $handler, array $middleware = []): self
    {
        $this->routes[] = [
            'method' => strtoupper($method),
            'pattern' => $pattern,
            'handler' => $handler,
            'middleware' => $middleware,
        ];

        return $this;
    }

    public function dispatch(string $method, string $uri): void
    {
        $method = strtoupper($method);
        $uri = parse_url($uri, PHP_URL_PATH) ?: '/';
        $uri = rtrim($uri, '/') ?: '/';

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $params = $this->match($route['pattern'], $uri);

            if ($params === null) {
                continue;
            }

            foreach ($route['middleware'] as $middlewareClass) {
                /** @var object $middleware */
                $middleware = new $middlewareClass();
                $result = $middleware->handle();

                if ($result === false) {
                    return;
                }
            }

            $handler = $route['handler'];

            if (is_array($handler)) {
                [$class, $action] = $handler;
                $controller = new $class();
                $controller->{$action}(...array_values($params));
                return;
            }

            $handler(...array_values($params));
            return;
        }

        http_response_code(404);
        View::render('errors/404', ['title' => 'Página no encontrada']);
    }

    /**
     * @return array<string, string>|null
     */
    private function match(string $pattern, string $uri): ?array
    {
        $pattern = rtrim($pattern, '/') ?: '/';

        $regex = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $pattern);
        $regex = '#^' . $regex . '$#';

        if (!preg_match($regex, $uri, $matches)) {
            return null;
        }

        $params = [];

        foreach ($matches as $key => $value) {
            if (is_string($key)) {
                $params[$key] = $value;
            }
        }

        return $params;
    }
}
