<?php

declare(strict_types=1);

namespace App\Http;

class Route
{
    private string $method;
    private string $path;
    private $handler;
    private array $middleware = [];
    private array $params = [];

    public function __construct(string $method, string $path, $handler)
    {
        $this->method = $method;
        $this->path = $path;
        $this->handler = $handler;
        $this->extractParams();
    }

    private function extractParams(): void
    {
        // Извлекаем параметры из пути (:id, :username и т.д.)
        if (preg_match_all('/:(\w+)/', $this->path, $matches)) {
            $this->params = $matches[1];
        }
    }

    public function matches(string $method, string $uri): bool
    {
        // Проверяем метод
        if ($this->method !== '*' && strtoupper($this->method) !== strtoupper($method)) {
            return false;
        }

        // Преобразуем путь в регулярное выражение
        $pattern = preg_replace('/:(\w+)/', '([^/]+)', $this->path);
        $pattern = '#^' . $pattern . '$#';

        return (bool)preg_match($pattern, $uri);
    }

    public function extractParams(string $uri): array
    {
        $pattern = preg_replace('/:(\w+)/', '([^/]+)', $this->path);
        $pattern = '#^' . $pattern . '$#';
        
        if (preg_match($pattern, $uri, $matches)) {
            array_shift($matches); // Убираем полное совпадение
            return array_combine($this->params, $matches) ?: [];
        }
        
        return [];
    }

    public function call(Request $request, array $params = []): void
    {
        // Если handler - массив [Controller::class, 'method']
        if (is_array($this->handler)) {
            [$class, $method] = $this->handler;
            $controller = new $class();
            $result = $controller->$method($request, ...array_values($params));
            
            if ($result instanceof Response) {
                return;
            }
            
            if (is_array($result)) {
                Response::json($result);
                return;
            }
            
            return;
        }

        // Если handler - callable
        if (is_callable($this->handler)) {
            $result = call_user_func($this->handler, $request, ...array_values($params));
            
            if ($result instanceof Response) {
                return;
            }
            
            if (is_array($result)) {
                Response::json($result);
                return;
            }
            
            return;
        }
    }

    public function middleware(string|array $middleware): self
    {
        if (is_array($middleware)) {
            $this->middleware = array_merge($this->middleware, $middleware);
        } else {
            $this->middleware[] = $middleware;
        }
        return $this;
    }

    public function getMiddleware(): array
    {
        return $this->middleware;
    }
}
