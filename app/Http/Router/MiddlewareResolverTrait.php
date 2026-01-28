<?php

declare(strict_types=1);

namespace App\Http\Router;

use App\Http\Middleware\MiddlewareInterface;
use App\Http\Middleware\AuthMiddleware;
use App\Http\Middleware\GuestMiddleware;
use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\CsrfMiddleware;
use App\Http\Middleware\RateLimitMiddleware;

trait MiddlewareResolverTrait
{
    private static array $middlewareMap = [
        'auth'  => AuthMiddleware::class,
        'guest' => GuestMiddleware::class,
        'admin' => AdminMiddleware::class,
        'csrf'  => CsrfMiddleware::class,
    ];

    private static function resolveMiddleware(string $name): ?MiddlewareInterface
    {
        if (isset(self::$middlewareMap[$name])) {
            return new self::$middlewareMap[$name]();
        }

        if (str_starts_with($name, 'rate_limit:')) {
            return self::resolveRateLimitMiddleware($name);
        }

        return null;
    }

    private static function resolveRateLimitMiddleware(string $name): ?RateLimitMiddleware
    {
        $parts = explode(':', $name);
        
        if (count($parts) !== 3) {
            return null;
        }
        
        $maxAttempts = (int) $parts[1];
        $windowSeconds = (int) $parts[2];
        
        return new RateLimitMiddleware($maxAttempts, $windowSeconds);
    }
}
