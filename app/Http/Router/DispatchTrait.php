<?php

declare(strict_types=1);

namespace App\Http\Router;

use App\Http\Request;
use App\Http\Response;

trait DispatchTrait
{
    public static function dispatch(): void
    {
        $request = new Request();
        $method = $request->method();
        $uri = $request->uri();

        foreach (self::$routes as $route) {
            if ($route->matches($method, $uri)) {
                self::handleRoute($route, $request, $uri);
                return;
            }
        }

        self::handleNotFound($uri);
    }

    private static function handleRoute($route, Request $request, string $uri): void
    {
        $params = $route->extractParams($uri);
        
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
        
        $route->call($request, $params);
    }

    private static function handleNotFound(string $uri): void
    {
        if (str_starts_with($uri, '/api/')) {
            Response::json(['error' => 'Not found'], 404);
            return;
        }
        
        http_response_code(404);
        $title = 'Страница не найдена';
        $content = '<div class="alert alert-error">Страница не найдена</div>';
        require TEMPLATES_PATH . '/layouts/main.php';
    }
}
