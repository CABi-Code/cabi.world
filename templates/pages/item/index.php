<?php
/**
 * Страница элемента папки
 * 
 * @var array $item
 * @var array $owner
 * @var array|null $user
 * @var bool $isOwner
 * @var array $path
 * @var array $children
 * @var array $subscriptions
 * @var array $breadcrumbs
 */

use App\Repository\UserFolderRepository;

$itemsMap = UserFolderRepository::ITEMS_MAP;
$iconData = $itemsMap[$item['item_type']] ?? ['icon' => 'file', 'color' => '#94a3b8'];
$icon = $item['icon'] ?? $iconData['icon'];
$color = $item['color'] ?? $iconData['color'];
$settings = $item['settings'] ? json_decode($item['settings'], true) : [];
?>

<div class="item-page">
    <!-- Хлебные крошки -->
    <?php require __DIR__ . '/item-page/breadcrumbs.php'; ?>
    
    <div class="item-layout">
        <!-- Сайдбар с подписками -->
        <?php require __DIR__ . '/item-page/sidebar.php'; ?>
        
        <!-- Основной контент -->
        <div class="item-main">
            <?php require __DIR__ . '/item-page/header.php'; ?>
            
            <!-- Контент в зависимости от типа -->
            <?php
            $contentFile = __DIR__ . '/item-page/content-' . $item['item_type'] . '.php';
            if (file_exists($contentFile)) {
                require $contentFile;
            } else {
                require __DIR__ . '/item-page/content-default.php';
            }
            ?>
        </div>
    </div>
</div>

<?php require __DIR__ . '/item-page/js-script.php'; ?>
