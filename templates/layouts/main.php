<?php
/**
 * Основной layout приложения
 * 
 * @var string $title - заголовок страницы
 * @var string $content - контент страницы
 * @var array|null $user - текущий пользователь
 */

use App\Core\Security;
use App\Repository\NotificationRepository;

// Проверка безопасности
$security = new Security();
$currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$security->check($currentPath);

// Если $user не передан, он null
if (!isset($user)) {
    $user = null;
}

// Счётчик уведомлений
if (!isset($unreadNotifications)) {
    $unreadNotifications = 0;
    if ($user) {
        $notifRepo = new NotificationRepository();
        $unreadNotifications = $notifRepo->countUnread($user['id']);
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e($title ?? 'cabi.world') ?></title>
    <link rel="stylesheet" href="/css/app.css">
    <link rel="stylesheet" href="/css/components/application-cards.css">
    <link rel="stylesheet" href="/css/components/modal.css">
    <link rel="stylesheet" href="/css/sections/community.css">
    <link rel="stylesheet" href="/css/sections/chat.css">
</head>
<body>
    <?php require TEMPLATES_PATH . '/components/header.php'; ?>
    
    <main class="page-content">
        <div class="container">
            <?= $content ?? '' ?>
        </div>
    </main>
    
    <?php require TEMPLATES_PATH . '/components/footer.php'; ?>
    <?php require TEMPLATES_PATH . '/components/icons.php'; ?>
    
    <script type="module" src="/js/app.js"></script>
    <script type="module" src="/js/modules/modal.js"></script>
</body>
</html>
