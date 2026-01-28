<?php

use App\Core\Security; 

global $user;

$security = new Security();
$currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$security->check($currentPath);

?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e($title ?? 'cabi.world') ?></title>
    <link rel="stylesheet" href="/css/app.css">
</head>
<body>
    <?php require TEMPLATES_PATH . '/components/header.php'; ?>
    
    <main class="auth-page">
        <div class="auth-container">
            <div class="auth-card">
                <?= $content ?? '' ?>
            </div>
        </div>
    </main>
    
    <?php require TEMPLATES_PATH . '/components/icons.php'; ?>
    <script type="module" src="/js/app.js"></script>
</body>
</html>
