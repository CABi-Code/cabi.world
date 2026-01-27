<?php

//
// В файле Security/routes.json
//
// Прямое совпадение: Если путь в JSON написан как /notifications, он сработает мгновенно через ===.
// Маска профиля: Регулярка #^/@[\w\d_]+$#u разрешит / @john, / @cabi, но не разрешит / @john/settings (так как указан конец строки $).
// Диапазоны: Если нужно разрешить всё после определенного символа, используйте ^/path/.*.




declare(strict_types=1);

namespace App\Core;

class Security {
    private array $allowedRoutes = [];
    private string $configPath;

    public function __construct() {
        // Путь относительно текущего файла
        $this->configPath = __DIR__ . '/Security/routes.json';
        $this->loadRoutes();

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    private function loadRoutes(): void {
        if (!file_exists($this->configPath)) {
            json(['error' => "Конфигурационный файл маршрутов не найден"], 500);
        }

        $jsonData = file_get_contents($this->configPath);
        $data = json_decode($jsonData, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            json(['error' => "Ошибка в формате JSON маршрутов: " . json_last_error_msg()], 500);
        }

        $this->allowedRoutes = $data;
    }

    /**
     * Основной метод запуска защиты
     */
    public function check(string $currentRoute): void {
        $method = $_SERVER['REQUEST_METHOD'];

        // 1. Проверяем маршрут (включая регулярки)
        $this->validateRoute($method, $currentRoute);

        // 2. CSRF для всех методов кроме безопасных
        if (!in_array($method, ['GET', 'HEAD', 'OPTIONS'])) {
            $this->validateCsrf();
        }
    }

	private function validateRoute(string $method, string $route): void {
		if (!isset($this->allowedRoutes[$method])) {
			json(['error' => "Метод $method не разрешен"], 405);
		}

		$isAllowed = false;

		foreach ($this->allowedRoutes[$method] as $pattern) {
			// Игнорируем пустые записи в конфиге, чтобы не было дыр
			if (trim($pattern) === '') continue;

			// 1. Пробуем точное совпадение
			if ($route === $pattern) {
				$isAllowed = true;
				break;
			}
			
			// 2. Проверяем как регулярное выражение
			// Используем ограничители ^ и $ для строгого соответствия всей строки
			if (str_contains($pattern, '^') || str_contains($pattern, '$')) {
				// Флаг 'u' для поддержки юникода (@имена)
				if (@preg_match("#$pattern#u", $route)) {
					$isAllowed = true;
					break;
				}
			}
		}

		if (!$isAllowed) {
			json(['error' => "Доступ к маршруту $route запрещен"], 403);
		}
	}
    private function validateCsrf(): void {
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['_csrf_token'] ?? '';
        $storedToken = $_SESSION['csrf_token'] ?? '';

        if (empty($token) || !hash_equals($storedToken, $token)) {
            json(['error' => "Security Alert: Invalid CSRF Token"], 403);
        }
    }

    /**
     * Метод для получения токена (использовать в формах)
     */
    public static function getCsrfToken(): string {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

