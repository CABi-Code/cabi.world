<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\Request;
use App\Http\Response;

class CsrfMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next)
    {
        // Пропускаем безопасные методы
        if (in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'])) {
            return $next($request);
        }

        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $request->get('_csrf_token', '');
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $storedToken = $_SESSION['csrf_token'] ?? '';
        
        if (empty($token) || !hash_equals($storedToken, $token)) {
            Response::json(['error' => 'Invalid CSRF token'], 403);
            return null;
        }
        
        return $next($request);
    }
}
