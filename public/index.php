<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\Middleware\AuthMiddleware;
use App\Auth\AuthManager;
use App\Repository\UserRepository;
use App\Repository\ModpackRepository;
use App\Repository\ApplicationRepository;
use App\Repository\NotificationRepository;
use App\Service\ImageService;
use App\Core\Database;

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

// === API Routes ===
if (str_starts_with($uri, '/api/')) {
    header('Content-Type: application/json; charset=utf-8');
    
    // Обработка ошибок
    set_exception_handler(function($e) {
        json([
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            '_db_errors' => Database::getErrors()
        ], 500);
    });
    
    $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if ($method !== 'GET') {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $csrfToken)) {
            json(['error' => 'Invalid CSRF token'], 403);
        }
    }

    $apiRoute = substr($uri, 4);
    $input = json_decode(file_get_contents('php://input'), true) ?? [];

    switch ($apiRoute) {
        case '/auth/login':
            if ($method !== 'POST') json(['error' => 'Method not allowed'], 405);
            $authManager = new AuthManager();
            $result = $authManager->login(
                $input['login'] ?? '',
                $input['password'] ?? '',
                $_SERVER['REMOTE_ADDR'] ?? '',
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            );
            if ($result['success']) {
                $authManager->setTokenCookies($result['tokens']);
                json(['success' => true, 'redirect' => '/profile/@' . $result['user']['login']]);
            }
            json($result, 401);
            break;

        case '/auth/register':
            if ($method !== 'POST') json(['error' => 'Method not allowed'], 405);
            $authManager = new AuthManager();
            $result = $authManager->register(
                $input['login'] ?? '',
                $input['email'] ?? '',
                $input['password'] ?? '',
                $input['username'] ?? ''
            );
            if ($result['success']) {
                $authManager->setTokenCookies($result['tokens']);
                $userRepo = new UserRepository();
                $newUser = $userRepo->findById($result['user_id']);
                json(['success' => true, 'redirect' => '/profile/@' . $newUser['login']]);
            }
            json($result, 400);
            break;

        case '/auth/refresh':
            $refreshToken = $_COOKIE['refresh_token'] ?? '';
            if (!$refreshToken) json(['error' => 'No token'], 401);
            $authManager = new AuthManager();
            $result = $authManager->refresh($refreshToken);
            if ($result['success']) {
                $authManager->setTokenCookies($result['tokens']);
                json(['success' => true]);
            }
            json($result, 401);
            break;

        case '/user/update':
            if (!$user) json(['error' => 'Unauthorized'], 401);
            $userRepo = new UserRepository();
            $allowed = ['username', 'bio', 'discord', 'telegram', 'vk', 
                        'banner_bg_value', 'avatar_bg_value', 'banner_bg_type', 'avatar_bg_type'];
            $data = array_intersect_key($input, array_flip($allowed));
            $userRepo->update($user['id'], $data);
            json(['success' => true]);
            break;

        case '/user/avatar':
            if (!$user) json(['error' => 'Unauthorized'], 401);
            if (empty($_FILES['avatar'])) json(['error' => 'No file'], 400);
            $imgService = new ImageService();
            $paths = $imgService->uploadAvatar($_FILES['avatar'], $user['id']);
            if (!$paths) json(['error' => 'Upload failed'], 400);
            $userRepo = new UserRepository();
            $userRepo->update($user['id'], ['avatar' => $paths['medium']]);
            json(['success' => true, 'paths' => $paths]);
            break;

        case '/user/avatar/delete':
            if (!$user) json(['error' => 'Unauthorized'], 401);
            $userRepo = new UserRepository();
            // Удаляем файлы аватара
            $avatarDir = UPLOADS_PATH . '/avatars/' . $user['id'];
            if (is_dir($avatarDir)) {
                array_map('unlink', glob("$avatarDir/*"));
            }
            $userRepo->update($user['id'], ['avatar' => null]);
            json(['success' => true]);
            break;

        case '/user/banner':
            if (!$user) json(['error' => 'Unauthorized'], 401);
            if (empty($_FILES['banner'])) json(['error' => 'No file'], 400);
            $imgService = new ImageService();
            $path = $imgService->uploadBanner($_FILES['banner'], $user['id']);
            if (!$path) json(['error' => 'Upload failed'], 400);
            $userRepo = new UserRepository();
            $userRepo->update($user['id'], ['banner' => $path]);
            json(['success' => true, 'path' => $path]);
            break;

        case '/user/banner/delete':
            if (!$user) json(['error' => 'Unauthorized'], 401);
            $userRepo = new UserRepository();
            // Удаляем файлы баннера
            $bannerDir = UPLOADS_PATH . '/banners/' . $user['id'];
            if (is_dir($bannerDir)) {
                array_map('unlink', glob("$bannerDir/*"));
            }
            $userRepo->update($user['id'], ['banner' => null]);
            json(['success' => true]);
            break;

        case '/feed':
            // AJAX endpoint для ленты заявок
            $appRepo = new ApplicationRepository();
            $page = max(1, (int)($_GET['page'] ?? 1));
            $sort = $_GET['sort'] ?? 'date';
            $limit = 20;
            $offset = ($page - 1) * $limit;

            $applications = $appRepo->findAllAccepted($limit, $offset, $sort);
            
            // Рендерим HTML
            ob_start();
            foreach ($applications as $app) {
                $images = $appRepo->getImages($app['id']);
                $avatarStyle = '';
                if (empty($app['avatar'])) {
                    $colors = explode(',', $app['avatar_bg_value'] ?? '#3b82f6,#8b5cf6');
                    $avatarStyle = 'background:linear-gradient(135deg,' . $colors[0] . ',' . $colors[1] ?? $colors[0] . ')';
                }
                ?>
                <div class="feed-card">
                    <div class="feed-header">
                        <a href="/profile/@<?= e($app['login']) ?>" class="feed-user">
                            <div class="feed-avatar" style="<?= $avatarStyle ?>">
                                <?php if (!empty($app['avatar'])): ?>
                                    <img src="<?= e($app['avatar']) ?>" alt="">
                                <?php else: ?>
                                    <?= mb_strtoupper(mb_substr($app['username'], 0, 1)) ?>
                                <?php endif; ?>
                            </div>
                            <div>
                                <div class="feed-name"><?= e($app['username']) ?></div>
                                <div class="feed-login">@<?= e($app['login']) ?></div>
                            </div>
                        </a>
                        <a href="/modpack/<?= e($app['platform']) ?>/<?= e($app['slug']) ?>" class="feed-modpack">
                            <?php if ($app['icon_url']): ?>
                                <img src="<?= e($app['icon_url']) ?>" alt="" class="feed-mp-icon">
                            <?php endif; ?>
                            <?= e($app['modpack_name']) ?>
                        </a>
                    </div>
                    
                    <div class="feed-body">
                        <p class="feed-message"><?= nl2br(e($app['message'])) ?></p>
                        
                        <?php if (!empty($app['relevant_until'])): ?>
                            <?php $expired = strtotime($app['relevant_until']) < time(); ?>
                            <div style="font-size:0.8125rem;color:<?= $expired ? 'var(--danger)' : 'var(--text-muted)' ?>;margin-top:0.5rem;">
                                <svg width="12" height="12" style="vertical-align:-2px;"><use href="#icon-clock"/></svg>
                                <?= $expired ? 'Истёк:' : 'До:' ?> <?= date('d.m.Y', strtotime($app['relevant_until'])) ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($images)): ?>
                            <div class="feed-images">
                                <?php foreach ($images as $img): ?>
                                    <a href="<?= e($img['image_path']) ?>" data-lightbox>
                                        <img src="<?= e($img['image_path']) ?>" alt="" class="feed-img">
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="feed-footer">
                        <div class="feed-contacts">
                            <?php if ($app['contact_discord']): ?>
                                <span class="contact-btn discord">
                                    <svg width="14" height="14"><use href="#icon-discord"/></svg>
                                    <?= e($app['contact_discord']) ?>
                                </span>
                            <?php endif; ?>
                            <?php if ($app['contact_telegram']): ?>
                                <a href="https://t.me/<?= e(ltrim($app['contact_telegram'], '@')) ?>" class="contact-btn telegram" target="_blank">
                                    <svg width="14" height="14"><use href="#icon-telegram"/></svg>
                                    <?= e($app['contact_telegram']) ?>
                                </a>
                            <?php endif; ?>
                            <?php if ($app['contact_vk']): ?>
                                <a href="https://vk.com/<?= e($app['contact_vk']) ?>" class="contact-btn vk" target="_blank">
                                    <svg width="14" height="14"><use href="#icon-vk"/></svg>
                                    <?= e($app['contact_vk']) ?>
                                </a>
                            <?php endif; ?>
                        </div>
                        <span class="feed-date"><?= date('d.m.Y', strtotime($app['created_at'])) ?></span>
                    </div>
                </div>
                <?php
            }
            $html = ob_get_clean();
            json(['success' => true, 'html' => $html]);
            break;

        case '/modpack/apply':
            if (!$user) json(['error' => 'Unauthorized'], 401);
            $modpackId = (int) ($input['modpack_id'] ?? 0);
            if ($modpackId <= 0) json(['error' => 'Invalid modpack ID'], 400);
            
            $appRepo = new ApplicationRepository();
            
            if ($appRepo->userHasApplied($modpackId, $user['id'])) {
                json(['error' => 'Вы уже подали заявку'], 400);
            }
            
            // Валидация даты на стороне сервера
            $dateValidation = $appRepo->validateRelevantUntil($input['relevant_until'] ?? null);
            if (!$dateValidation['valid']) {
                json(['errors' => ['relevant_until' => $dateValidation['error']]], 400);
            }
            
            // Валидация сообщения
            if (empty(trim($input['message'] ?? ''))) {
                json(['errors' => ['message' => 'Введите сообщение']], 400);
            }
            
            try {
                $appId = $appRepo->create(
                    $modpackId,
                    $user['id'],
                    $input['message'] ?? '',
                    $input['discord'] ?? $user['discord'],
                    $input['telegram'] ?? $user['telegram'],
                    $input['vk'] ?? $user['vk'],
                    $input['relevant_until'] ?? null
                );
                
                // Загрузка изображений если есть
                if (!empty($_FILES['images'])) {
                    $imgService = new ImageService();
                    $files = $_FILES['images'];
                    $count = min(4, count($files['name']));
                    for ($i = 0; $i < $count; $i++) {
                        if ($files['error'][$i] === UPLOAD_ERR_OK) {
                            $file = [
                                'tmp_name' => $files['tmp_name'][$i],
                                'size' => $files['size'][$i],
                                'error' => $files['error'][$i]
                            ];
                            $path = $imgService->uploadApplicationImage($file, $appId);
                            if ($path) {
                                $appRepo->addImage($appId, $path, $i);
                            }
                        }
                    }
                }
                
                json(['success' => true, 'id' => $appId]);
            } catch (\InvalidArgumentException $e) {
                json(['errors' => ['relevant_until' => $e->getMessage()]], 400);
            }
            break;

        case '/application/update':
            if (!$user) json(['error' => 'Unauthorized'], 401);
            $appId = (int) ($input['id'] ?? 0);
            if ($appId <= 0) json(['error' => 'Invalid ID'], 400);
            
            $appRepo = new ApplicationRepository();
            
            // Валидация даты на стороне сервера
            $dateValidation = $appRepo->validateRelevantUntil($input['relevant_until'] ?? null);
            if (!$dateValidation['valid']) {
                json(['errors' => ['relevant_until' => $dateValidation['error']]], 400);
            }
            
            // Валидация сообщения
            if (empty(trim($input['message'] ?? ''))) {
                json(['errors' => ['message' => 'Введите сообщение']], 400);
            }
            
            try {
                $updated = $appRepo->update(
                    $appId,
                    $user['id'],
                    $input['message'] ?? '',
                    $input['discord'] ?? null,
                    $input['telegram'] ?? null,
                    $input['vk'] ?? null,
                    $input['relevant_until'] ?? null
                );
                
                if (!$updated) json(['error' => 'Not found or not yours'], 404);
                json(['success' => true]);
            } catch (\InvalidArgumentException $e) {
                json(['errors' => ['relevant_until' => $e->getMessage()]], 400);
            }
            break;

        case '/application/delete':
            if (!$user) json(['error' => 'Unauthorized'], 401);
            $appId = (int) ($input['id'] ?? 0);
            if ($appId <= 0) json(['error' => 'Invalid ID'], 400);
            $appRepo = new ApplicationRepository();
            $appRepo->deleteAllImages($appId);
            $deleted = $appRepo->delete($appId, $user['id']);
            if (!$deleted) json(['error' => 'Not found'], 404);
            json(['success' => true]);
            break;

        case '/application/toggle-hidden':
            if (!$user) json(['error' => 'Unauthorized'], 401);
            $appId = (int) ($input['id'] ?? 0);
            $appRepo = new ApplicationRepository();
            $appRepo->toggleHidden($appId, $user['id']);
            json(['success' => true]);
            break;

        case '/notifications':
            if (!$user) json(['error' => 'Unauthorized'], 401);
            $notifRepo = new NotificationRepository();
            $notifications = $notifRepo->findByUser($user['id']);
            json(['success' => true, 'notifications' => $notifications, 'unread' => $notifRepo->countUnread($user['id'])]);
            break;

        case '/notifications/read':
            if (!$user) json(['error' => 'Unauthorized'], 401);
            $notifId = (int) ($input['id'] ?? 0);
            $notifRepo = new NotificationRepository();
            if ($notifId > 0) {
                $notifRepo->markAsRead($notifId, $user['id']);
            } else {
                $notifRepo->markAllAsRead($user['id']);
            }
            json(['success' => true]);
            break;

        default:
            json(['error' => 'Not found'], 404);
    }
    exit;
}

// === Page Routes ===
$routes = [
    '/' => ['page' => 'home'],
    '/modrinth' => ['page' => 'modrinth'],
    '/curseforge' => ['page' => 'curseforge'],
    '/login' => ['page' => 'login', 'guest' => true],
    '/register' => ['page' => 'register', 'guest' => true],
    '/forgot-password' => ['page' => 'forgot-password', 'guest' => true],
    '/settings' => ['page' => 'settings', 'auth' => true],
    '/logout' => ['action' => 'logout'],
];

// Profile routes
if (preg_match('#^/profile/@([a-zA-Z0-9_-]+)$#', $uri, $matches)) {
    $profileLogin = $matches[1];
    $userRepo = new UserRepository();
    $profileUser = $userRepo->findByLogin($profileLogin);
    
    if (!$profileUser) {
        http_response_code(404);
        $title = 'Пользователь не найден';
        $content = '<div class="alert alert-error">Пользователь не найден</div>';
        require TEMPLATES_PATH . '/layouts/main.php';
        exit;
    }
    
    // Проверка существования файлов профиля
    $uploadPath = ROOT_PATH . '/public';
    $fileUpdates = [];
    if ($profileUser['avatar'] && !file_exists($uploadPath . $profileUser['avatar'])) {
        $fileUpdates['avatar'] = null;
        $profileUser['avatar'] = null;
    }
    if ($profileUser['banner'] && !file_exists($uploadPath . $profileUser['banner'])) {
        $fileUpdates['banner'] = null;
        $profileUser['banner'] = null;
    }
    if (!empty($fileUpdates)) {
        $userRepo->update($profileUser['id'], $fileUpdates);
    }
    
    $isOwner = $user && $user['id'] === $profileUser['id'];
    $title = $profileUser['username'] . ' — cabi.world';
    ob_start();
    require TEMPLATES_PATH . '/pages/profile.php';
    $content = ob_get_clean();
    require TEMPLATES_PATH . '/layouts/main.php';
    exit;
}

// Modpack page
if (preg_match('#^/modpack/(modrinth|curseforge)/([a-zA-Z0-9_-]+)$#', $uri, $matches)) {
    $platform = $matches[1];
    $slug = $matches[2];
    
    $modpackRepo = new ModpackRepository();
    $modpack = $modpackRepo->findBySlug($platform, $slug);
    
    $title = ($modpack['name'] ?? $slug) . ' — cabi.world';
    ob_start();
    require TEMPLATES_PATH . '/pages/modpack.php';
    $content = ob_get_clean();
    require TEMPLATES_PATH . '/layouts/main.php';
    exit;
}

// Static routes
if (isset($routes[$uri])) {
    $route = $routes[$uri];
    
    if (isset($route['action']) && $route['action'] === 'logout') {
        $authManager = new AuthManager();
        $refreshToken = $_COOKIE['refresh_token'] ?? '';
        if ($refreshToken) $authManager->logout($refreshToken);
        $authManager->clearTokenCookies();
        redirect('/login');
    }
    
    if (isset($route['guest']) && $route['guest'] && $user) {
        redirect('/profile/@' . $user['login']);
    }
    
    if (isset($route['auth']) && $route['auth'] && !$user) {
        redirect('/login');
    }
    
    $pageName = $route['page'];
    $pageFile = TEMPLATES_PATH . '/pages/' . $pageName . '.php';
    
    if (file_exists($pageFile)) {
        $title = match($pageName) {
            'home' => 'Найди компанию — cabi.world',
            'modrinth' => 'Модпаки Modrinth — cabi.world',
            'curseforge' => 'Модпаки CurseForge — cabi.world',
            'login' => 'Вход — cabi.world',
            'register' => 'Регистрация — cabi.world',
            'settings' => 'Настройки — cabi.world',
            default => 'cabi.world'
        };
        
        ob_start();
        require $pageFile;
        $content = ob_get_clean();
        
        if (in_array($pageName, ['login', 'register', 'forgot-password'])) {
            require TEMPLATES_PATH . '/layouts/auth.php';
        } else {
            require TEMPLATES_PATH . '/layouts/main.php';
        }
        exit;
    }
}

// 404
http_response_code(404);
$title = 'Страница не найдена';
$content = '<div class="alert alert-error">Страница не найдена</div>';
require TEMPLATES_PATH . '/layouts/main.php';