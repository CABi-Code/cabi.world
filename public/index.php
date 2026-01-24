<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\Middleware\AuthMiddleware;
use App\Middleware\AdminMiddleware;
use App\Auth\AuthManager;
use App\Repository\UserRepository;
use App\Repository\ModpackRepository;
use App\Repository\ApplicationRepository;
use App\Repository\NotificationRepository;
use App\Service\ImageService;
use App\Core\Database;
use App\Core\Role;

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = rtrim($uri, '/') ?: '/';
$method = $_SERVER['REQUEST_METHOD'];

$auth = new AuthMiddleware();
$user = $auth->getUser();

// Счётчик уведомлений для хедера
$unreadNotifications = 0;
if ($user) {
    $notifRepo = new NotificationRepository();
    $unreadNotifications = $notifRepo->countUnread($user['id']);
}

include_once '../index/RoutesAPI/RoutesAPI.php';

include_once '../index/Routes/.PageRoutes.php';

include_once '../index/Routes/admin-panel-route.php';

include_once '../index/Routes/profile-routes.php';

include_once '../index/Routes/modpack-page.php';

include_once '../index/Routes/static-routes.php';

// 404
http_response_code(404);
$title = 'Страница не найдена';
$content = '<div class="alert alert-error">Страница не найдена</div>';
require TEMPLATES_PATH . '/layouts/main.php';