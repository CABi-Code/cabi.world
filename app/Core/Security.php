<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Security - проверка маршрутов и CSRF
 * 
 * В файле Security/routes.json:
 * - Прямое совпадение: "/notifications" сработает через ===
 * - Маска с регуляркой: "^/@[\w\d_]+$" разрешит /@john, /@cabi
 * - Диапазоны: "^/api/chat/.*" разрешит всё после /api/chat/
 */
class Security
{
    private array $allowedRoutes = [];
    private string $configPath;

    public function __construct()
    {
        $this->configPath = __DIR__ . '/Security/routes.json';
        $this->loadRoutes();

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    private function loadRoutes(): void
    {
        if (!file_exists($this->configPath)) {
            $this->sendError("Конфигурационный файл маршрутов не найден", 500);
            return;
        }

        $jsonData = file_get_contents($this->configPath);
        $data = json_decode($jsonData, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->sendError("Ошибка в формате JSON маршрутов: " . json_last_error_msg(), 500);
            return;
        }

        $this->allowedRoutes = $data;
    }

    /**
     * Основной метод проверки
     */
    public function check(string $currentRoute): void
    {
        $method = $_SERVER['REQUEST_METHOD'];

        // 1. Проверяем маршрут
        if (!$this->isRouteAllowed($method, $currentRoute)) {
            $this->sendError("Доступ к маршруту $currentRoute запрещен", 403);
            return;
        }

        // 2. CSRF для всех методов кроме безопасных
        if (!in_array($method, ['GET', 'HEAD', 'OPTIONS'])) {
            $this->validateCsrf();
        }
    }

    /**
     * Проверяет, разрешён ли маршрут
     */
    private function isRouteAllowed(string $method, string $route): bool
    {
        // Проверяем точный метод
        if (isset($this->allowedRoutes[$method])) {
            if ($this->matchRoute($route, $this->allowedRoutes[$method])) {
                return true;
            }
        }

        // Проверяем wildcard '*' для любого метода
        if (isset($this->allowedRoutes['*'])) {
            if ($this->matchRoute($route, $this->allowedRoutes['*'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Сопоставляет маршрут с паттернами
     */
    private function matchRoute(string $route, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            // Игнорируем пустые записи
            if (trim($pattern) === '') {
                continue;
            }

            // 1. Точное совпадение
            if ($route === $pattern) {
                return true;
            }

            // 2. Регулярное выражение (содержит ^ или $ или .*)
            if ($this->isRegexPattern($pattern)) {
                if (@preg_match("#$pattern#u", $route)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Проверяет, является ли паттерн регуляркой
     */
    private function isRegexPattern(string $pattern): bool
    {
        return str_contains($pattern, '^') 
            || str_contains($pattern, '$') 
            || str_contains($pattern, '.*')
            || str_contains($pattern, '.+')
            || str_contains($pattern, '[');
    }

    private function validateCsrf(): void
    {
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['_csrf_token'] ?? '';
        $storedToken = $_SESSION['csrf_token'] ?? '';

        if (empty($token) || !hash_equals($storedToken, $token)) {
            $this->sendError("Security Alert: Invalid CSRF Token", 403);
        }
    }

    /**
     * Отправляет JSON ошибку и завершает выполнение
     */
    private function sendError(string $message, int $code): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => $message], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Получить CSRF токен
     */
    public static function getCsrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}
