<?php

declare(strict_types=1);

namespace App\Http\Router;

use App\Http\Request;
use App\Http\Response;
use App\Http\Middleware\AuthMiddleware;
use App\Http\Middleware\RateLimitBlockMiddleware;

trait DispatchTrait
{
    public static function dispatch(): void
    {
        $request = new Request();
        
        // Инициализируем пользователя
        self::initializeUser($request);
        
        // Проверяем глобальную блокировку по rate limit
        if (!self::checkGlobalRateLimit($request)) {
            return;
        }
        
        $method = $request->method();
        $uri = $request->uri();

        foreach (self::$routes as $route) {
            if ($route->matches($method, $uri)) {
                self::handleRoute($route, $request, $uri);
                return;
            }
        }

        self::handleNotFound($request, $uri);
    }

    /**
     * Проверяет глобальную блокировку
     */
    private static function checkGlobalRateLimit(Request $request): bool
    {
		error_log("checkGlobalRateLimit called for: " . $request->uri());
		
        // Пропускаем статику и эндпоинт капчи
        $uri = $request->uri();
        $skipPaths = ['/api/captcha/solve', '/css/', '/js/', '/images/', '/uploads/'];
        
        foreach ($skipPaths as $path) {
            if (str_starts_with($uri, $path)) {
                return true;
            }
        }
        
        $middleware = new RateLimitBlockMiddleware();
        $result = $middleware->handle($request, fn($r) => true);
        
        return $result !== null;
    }

    /**
     * Инициализирует пользователя через AuthMiddleware
     */
    private static function initializeUser(Request $request): void
    {
        try {
            $authMiddleware = new AuthMiddleware();
            $user = $authMiddleware->getUser($request);
            if ($user) {
                $request->setUser($user);
            }
        } catch (\Exception $e) {
            // Игнорируем ошибки аутентификации
        }
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

    private static function handleNotFound(Request $request, string $uri): void
    {
        if (str_starts_with($uri, '/api/')) {
            Response::json(['error' => 'Not found'], 404);
            return;
        }
        
        http_response_code(404);
        $title = 'Страница не найдена';
        $user = $request->user();
        $content = '<div class="alert alert-error">Страница не найдена</div>';
        require TEMPLATES_PATH . '/layouts/main.php';
    }
}
