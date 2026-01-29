<?php

declare(strict_types=1);

namespace App\Core;

class Template
{
    private static array $included = [];

    /**
     * Включает файл только один раз (по абсолютному пути)
     */
    public static function includeOnce(string $path, array $data = []): void
    {
        $absolutePath = self::resolvePath($path);
        
        if (isset(self::$included[$absolutePath])) {
            return;
        }
        
        self::$included[$absolutePath] = true;
        
        if (!file_exists($absolutePath)) {
            throw new \RuntimeException("Template not found: {$absolutePath}");
        }
        
        extract($data);
        require $absolutePath;
    }

    /**
     * Включает файл (можно несколько раз)
     */
    public static function include(string $path, array $data = []): void
    {
        $absolutePath = self::resolvePath($path);
        
        if (!file_exists($absolutePath)) {
            throw new \RuntimeException("Template not found: {$absolutePath}");
        }
        
        extract($data);
        require $absolutePath;
    }

    /**
     * Рендерит файл и возвращает строку
     */
    public static function render(string $path, array $data = []): string
    {
        $absolutePath = self::resolvePath($path);
        
        if (!file_exists($absolutePath)) {
            throw new \RuntimeException("Template not found: {$absolutePath}");
        }
        
        extract($data);
        ob_start();
        require $absolutePath;
        return ob_get_clean();
    }

    /**
     * Сбрасывает кеш включённых файлов
     */
    public static function reset(): void
    {
        self::$included = [];
    }

    /**
     * Преобразует путь в абсолютный
     */
    private static function resolvePath(string $path): string
    {
        // Если путь уже абсолютный
        if (str_starts_with($path, '/') || str_starts_with($path, TEMPLATES_PATH)) {
            return str_starts_with($path, TEMPLATES_PATH) 
                ? $path 
                : TEMPLATES_PATH . $path;
        }
        
        // Относительный путь — от TEMPLATES_PATH
        return TEMPLATES_PATH . '/' . ltrim($path, '/');
    }
}