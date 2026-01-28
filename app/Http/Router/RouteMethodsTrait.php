<?php

declare(strict_types=1);

namespace App\Http\Router;

use App\Http\Route;

trait RouteMethodsTrait
{
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
        $fullPath = self::buildFullPath($path);
        $route = new Route($method, $fullPath, $handler);
        
        $allMiddleware = array_merge(self::$groupMiddleware, $middleware);
        foreach ($allMiddleware as $mw) {
            $route->middleware($mw);
        }
        
        self::$routes[] = $route;
        return $route;
    }

    private static function buildFullPath(string $path): string
    {
        $fullPath = self::$prefix 
            ? rtrim(self::$prefix, '/') . '/' . ltrim($path, '/') 
            : $path;
            
        return '/' . ltrim($fullPath, '/');
    }
}
