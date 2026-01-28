<?php

declare(strict_types=1);

namespace App\Http\Router;

use App\Http\RouterGroup;

trait GroupTrait
{
    /**
     * Устанавливает префикс для группы маршрутов
     * Поддерживает два варианта использования:
     * 1. Router::prefix('/api', function() { ... })
     * 2. Router::prefix('/api')->group(function() { ... })
     */
    public static function prefix(string $prefix, ?callable $callback = null): RouterGroup
    {
        if ($callback !== null) {
            $oldPrefix = self::$prefix;
            self::$prefix = self::combinePrefix($oldPrefix, $prefix);
            $callback();
            self::$prefix = $oldPrefix;
            return new RouterGroup();
        }
        
        return new RouterGroup(['prefix' => $prefix]);
    }

    public static function group(array $options, callable $callback): void
    {
        $oldPrefix = self::$prefix;
        $oldMiddleware = self::$groupMiddleware;
        
        if (isset($options['prefix'])) {
            self::$prefix = self::combinePrefix($oldPrefix, $options['prefix']);
        }
        
        if (isset($options['middleware'])) {
            $middleware = is_array($options['middleware']) 
                ? $options['middleware'] 
                : [$options['middleware']];
            self::$groupMiddleware = array_merge($oldMiddleware, $middleware);
        }
        
        $callback();
        
        self::$prefix = $oldPrefix;
        self::$groupMiddleware = $oldMiddleware;
    }

    private static function combinePrefix(?string $oldPrefix, string $newPrefix): string
    {
        return ($oldPrefix ? rtrim($oldPrefix, '/') . '/' : '') . ltrim($newPrefix, '/');
    }
}
