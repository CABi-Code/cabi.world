<?php

declare(strict_types=1);

namespace App\Http;

class Router
{
    private static array $routes = [];
    private static array $middleware = [];
    private static ?string $prefix = null;
    private static array $groupMiddleware = [];

    public static function get(string $path, $handler, array $middleware = []): Route
    {
        return self::addRoute('GET', $path, $handler, $middleware);
    }

    public static function post(string $path, $handler, array $middleware = []): Route
    {
        return self::addRoute('POST', $path, $handler, $middleware);
    }

    public static function put(string $path, $handler, array $middleware = []): Route
    {
        return self::addRoute('PUT', $path, $handler, $middleware);
    }

    public static function delete(string $path, $handler, array $middleware = []): Route
    {
        return self::addRoute('DELETE', $path, $handler, $middleware);
    }

    public static function patch(string $path, $handler, array $middleware = []): Route
    {
        return self::addRoute('PATCH', $path, $handler, $middleware);
    }

    public static function any(string $path, $handler, array $middleware = []): Route
    {
        return self::addRoute('*', $path, $handler, $middleware);
    }

    private static function addRoute(string $method, string $path, $handler, array $middleware = []): Route
    {
        $fullPath = self::$prefix ? rtrim(self::$prefix, '/') . '/' . ltrim($path, '/') : $path;
        $fullPath = '/' . ltrim($fullPath, '/');
        
        $route = new Route($method, $fullPath, $handler);
        
        // Объединяем middleware группы и маршрута
        $allMiddleware = array_merge(self::$groupMiddleware, $middleware);
        foreach ($allMiddleware as $mw) {
            $route->middleware($mw);
        }
        
        self::$routes[] = $route;
        return $route;
    }

    public static function prefix(string $prefix, callable $callback): void
    {
        $oldPrefix = self::$prefix;
        self::$prefix = ($oldPrefix ? rtrim($oldPrefix, '/') . '/' : '') . ltrim($prefix, '/');
        $callback();
        self::$prefix = $oldPrefix;
    }

    public static function group(array $options, callable $callback): void
    {
        $oldPrefix = self::$prefix;
        $oldMiddleware = self::$groupMiddleware;
        
        if (isset($options['prefix'])) {
            self::$prefix = ($oldPrefix ? rtrim($oldPrefix, '/') . '/' : '') . ltrim($options['prefix'], '/');
        }
        
        if (isset($options['middleware'])) {
            $middleware = is_array($options['middleware']) ? $options['middleware'] : [$options['middleware']];
            self::$groupMiddleware = array_merge($oldMiddleware, $middleware);
        }
        
        $callback();
        
        self::$prefix = $oldPrefix;
        self::$groupMiddleware = $oldMiddleware;
    }

    public static function dispatch(): void
    {
        $request = new Request();
        $method = $request->method();
        $uri = $request->uri();

        foreach (self::$routes as $route) {
            if ($route->matches($method, $uri)) {
                $params = $route->extractParams($uri);
                
                // Применяем middleware
                foreach ($route->getMiddleware() as $middlewareName) {
                    $middleware = self::resolveMiddleware($middlewareName);
                    if ($middleware) {
                        $result = $middleware->handle($request, function($req) use ($route, $params) {
                            return $route->call($req, $params);
                        });
                        
                        if ($result !== null) {
                            return;
                        }
                    }
                }
                
                // Вызываем обработчик
                $route->call($request, $params);
                return;
            }
        }

        // 404
        if (str_starts_with($uri, '/api/')) {
            Response::json(['error' => 'Not found'], 404);
        } else {
            http_response_code(404);
            $title = 'Страница не найдена';
            $content = '<div class="alert alert-error">Страница не найдена</div>';
            require TEMPLATES_PATH . '/layouts/main.php';
        }
    }

    private static function resolveMiddleware(string $name): ?\App\Http\Middleware\MiddlewareInterface
    {
        $middlewareMap = [
            'auth' => \App\Http\Middleware\AuthMiddleware::class,
            'guest' => \App\Http\Middleware\GuestMiddleware::class,
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'csrf' => \App\Http\Middleware\CsrfMiddleware::class,
        ];

        if (isset($middlewareMap[$name])) {
            return new $middlewareMap[$name]();
        }

        // Поддержка rate_limit:5,60
        if (strpos($name, 'rate_limit:') === 0) {
            $parts = explode(':', $name);
            if (count($parts) === 3) {
                $maxAttempts = (int)$parts[1];
                $windowSeconds = (int)$parts[2];
                return new \App\Http\Middleware\RateLimitMiddleware($maxAttempts, $windowSeconds);
            }
        }

        return null;
    }
}
