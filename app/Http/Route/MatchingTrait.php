<?php

declare(strict_types=1);

namespace App\Http\Route;

trait MatchingTrait
{
    /**
     * Проверяет, совпадает ли маршрут с методом и URI
     */
    public function matches(string $method, string $uri): bool
    {
        if (!$this->matchesMethod($method)) {
            return false;
        }

        return $this->matchesUri($uri);
    }

    private function matchesMethod(string $method): bool
    {
        if ($this->method === '*') {
            return true;
        }
        
        return strtoupper($this->method) === strtoupper($method);
    }

    private function matchesUri(string $uri): bool
    {
        $pattern = $this->buildPattern();
        return (bool) preg_match($pattern, $uri);
    }
}
