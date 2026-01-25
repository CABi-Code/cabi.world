<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e($title ?? 'cabi.world') ?></title>
    <link rel="stylesheet" href="/css/app.css">
	<link rel="stylesheet" href="/css/components/application-cards.css">
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
</body>
</html>
