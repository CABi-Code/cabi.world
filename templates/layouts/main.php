<?php
/**
 * Основной layout приложения
 */

use App\Repository\NotificationRepository;
use App\Core\Security;

$security = new Security();
$currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$security->check($currentPath);

if (defined('MAIN_LAYOUT_RENDERED')) return;
define('MAIN_LAYOUT_RENDERED', true);

if (!isset($user)) $user = null;

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
    
    <!-- Modal.js должен быть загружен ДО любых inline скриптов -->
    <script src="/js/modules/modal.js"></script>
    <script type="module" src="/js/app.js"></script>
</body>
</html>
