<?php

declare(strict_types=1);

namespace App\Core;
use App\Controllers\BaseController;

/**
 * Security - проверка маршрутов и CSRF
 * 
 * В файле Security/routes.json:
 * - Прямое совпадение: "/notifications" сработает через ===
 * - Маска с регуляркой: "^/@[\w\d_]+$" разрешит /@john, /@cabi
 * - Диапазоны: "^/api/chat/.*" разрешит всё после /api/chat/
 */
class Security extends BaseController
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
		$currentRoute = $currentRoute ?? '/';
		$currentRoute = '/' . ltrim($currentRoute, '/'); // точно один слэш вналае
		$currentRoute = preg_replace('#/{2,}#', '/', $currentRoute); // точно нет лишних слешей где-либо еще 
		
		// сегментирование и удаление лишнего
		$segments = array_filter(explode('/', $currentRoute), function($s) {return $s !== '' && $s !== '.' && $s !== '..';});
		$cleanSegments = [];
		// Пропуск только букв, цифр, дефисов, подчёркивание, точкек, собак
		foreach ($segments as $seg) {if (!preg_match('/^[a-zA-Z0-9@._-]+$/', $seg)) $this->sendError('Bad Request', 400);$cleanSegments[] = $seg;}
		
		$normalizedPath = '/' . implode('/', $cleanSegments);
		if (strlen($normalizedPath) > 4096) $this->sendError('URI Too Long', 414);
		
        $method = $_SERVER['REQUEST_METHOD'];

        // 1. Проверяем маршрут
        if (!$this->isRouteAllowed($method, $currentRoute)) {
			//$this->sendError("Страница не найдена", 404);
			$message = 'Страница не найдена';
			http_response_code(404);
			$this->notFound('эу',[
				'title' => 'Не найдено',
				'message' => $message
			]);
            
			exit;
			//$this->sendError("Доступ к маршруту $currentRoute запрещен", 423);
            return;
        }

		//$allowedByMethod  = isset($this->allowedRoutes[$method]) 
		//	&& $this->matchRoute($currentRoute, $this->allowedRoutes[$method] ?? []);
		//
		//$allowedByWildcard = isset($this->allowedRoutes['*']) 
		//	&& $this->matchRoute($currentRoute, $this->allowedRoutes['*'] ?? []);
		//
		//if ($allowedByMethod || $allowedByWildcard) {
		//	// маршрут найден и разрешён → продолжаем
		//	if (!in_array($method, ['GET'])) {
		//		$this->validateCsrf();
		//	}
		//} else {
		//	$message = 'Страница не найдена';
		//	http_response_code(404);
		//	$this->notFound('эу',[
		//		'title' => 'Не найдено',
		//		'message' => $message
		//	]);
		//}

        // 2. CSRF для всех методов 
        if (!in_array($method, ['GET'])) {
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
	 * $route - вызваный метод пользователем строчка из браузера, например '/login', '/registr', '/@cabi', ' /modpack/curseforge/nightfallcraft-the-casket-of-reveries'
	 * $patterns - список методов из файла
	 */
	private function matchRoute(string $route, array $patterns): bool
	{
		
		$route = trim($route);
		$route = filter_var($route, FILTER_SANITIZE_URL);
		
		foreach ($patterns as $pattern) {
			$patternBan = false;
			
			// Игнорирует пустые записи
			if (trim($pattern) === '') continue;

			// Точное совпадение
			if ($route === $pattern) {
				if (str_starts_with($pattern, '!')) return false; // Отклоняет записи с ! вначале
				return true;
			}

			// Регулярное выражение (содержит ^ или $ или .*)
			if ($this->isRegexPattern($pattern)) {
				$isNegative = str_starts_with($pattern, '!');
				if ($isNegative) $pattern = ltrim($pattern, '!'); // Отклоняет записи с ! вначале
				if (@preg_match("#$pattern#u", $route)) return !$isNegative;
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
		echo $message;
		exit;
		//json(['error' => $message], $code);
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
