<?php

declare(strict_types=1);

namespace App\Http\Route;

trait ParamsTrait
{
    /**
     * Извлекает имена параметров из пути (:id, :username и т.д.)
     */
    private function parsePathParams(): void
    {
        if (preg_match_all('/:(\w+)/', $this->path, $matches)) {
            $this->params = $matches[1];
        }
    }

    /**
     * Извлекает значения параметров из URI
     */
    public function extractParams(string $uri): array
    {
        $pattern = $this->buildPattern();
        
        if (preg_match($pattern, $uri, $matches)) {
            array_shift($matches);
            return array_combine($this->params, $matches) ?: [];
        }
        
        return [];
    }

    /**
     * Строит регулярное выражение для пути
     */
    private function buildPattern(): string
    {
        $pattern = preg_replace('/:(\w+)/', '([^/]+)', $this->path);
        return '#^' . $pattern . '$#';
    }
}
