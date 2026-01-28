<?php

declare(strict_types=1);

namespace App\Http;

/**
 * Вспомогательный класс для цепочки вызовов Router::prefix()->group()
 */
class RouterGroup
{
    private array $options;

    public function __construct(array $options = [])
    {
        $this->options = $options;
    }

    public function group(callable $callback): void
    {
        Router::group($this->options, $callback);
    }

    public function middleware(string|array $middleware): self
    {
        $mw = is_array($middleware) ? $middleware : [$middleware];
        $this->options['middleware'] = array_merge(
            $this->options['middleware'] ?? [],
            $mw
        );
        return $this;
    }
}
