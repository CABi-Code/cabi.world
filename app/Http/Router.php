<?php

declare(strict_types=1);

namespace App\Http;

class Router
{
    use \App\Http\Router\RouteMethodsTrait;
    use \App\Http\Router\GroupTrait;
    use \App\Http\Router\DispatchTrait;
    use \App\Http\Router\MiddlewareResolverTrait;

    private static array $routes = [];
    private static array $middleware = [];
    private static ?string $prefix = null;
    private static array $groupMiddleware = [];
}
