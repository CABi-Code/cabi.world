<?php

declare(strict_types=1);

namespace App\Http\Route;

trait MiddlewareTrait
{
    /**
     * Добавляет middleware к маршруту
     */
    public function middleware(string|array $middleware): self
    {
        if (is_array($middleware)) {
            $this->middleware = array_merge($this->middleware, $middleware);
        } else {
            $this->middleware[] = $middleware;
        }
        
        return $this;
    }

    /**
     * Возвращает список middleware
     */
    public function getMiddleware(): array
    {
        return $this->middleware;
    }
}
