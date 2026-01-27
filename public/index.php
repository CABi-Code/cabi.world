<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\Http\Router;
use App\Repository\NotificationRepository;

// Инициализируем Request для получения пользователя
$request = new \App\Http\Request();

// Получаем пользователя через middleware (для совместимости со старым кодом)
$authMiddleware = new \App\Http\Middleware\AuthMiddleware();
$user = null;
try {
    $user = $authMiddleware->getUser($request);
    if ($user) {
        $request->setUser($user);
    }
} catch (\Exception $e) {
    // Игнорируем ошибки аутентификации
}

// Глобальная переменная $user для совместимости со старыми шаблонами
global $user;

// Счётчик уведомлений для хедера (глобальная переменная для совместимости)
$unreadNotifications = 0;
if ($user) {
    $notifRepo = new NotificationRepository();
    $unreadNotifications = $notifRepo->countUnread($user['id']);
}

// Загружаем маршруты
require_once ROOT_PATH . '/routes/web.php';
require_once ROOT_PATH . '/routes/api.php';

// Диспетчеризация маршрутов
Router::dispatch();
