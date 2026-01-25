<?php
/**
 * Универсальная карточка заявки
 * 
 * @var array $app - данные заявки (должны включать effective_discord/telegram/vk)
 * @var array|null $user - текущий пользователь
 * @var bool $showModpack - показывать ли ссылку на модпак (default: true)
 * @var bool $showUser - показывать ли информацию о пользователе (default: true)
 * @var ApplicationRepository $appRepo - репозиторий для получения изображений
 */

$showModpack = $showModpack ?? true;
$showUser = $showUser ?? true;

$isOwner = $user && isset($app['user_id']) && $app['user_id'] == $user['id'];
$isPending = ($app['status'] ?? '') === 'pending';
$isHidden = !empty($app['is_hidden']);

// Получаем изображения заявки
$images = $appRepo->getImages($app['id']);

// Эффективные контакты (должны приходить из репозитория через COALESCE)
$effectiveDiscord = $app['effective_discord'] ?? '';
$effectiveTelegram = $app['effective_telegram'] ?? '';
$effectiveVk = $app['effective_vk'] ?? '';

$cardClass = 'app-card';
if ($isPending) $cardClass .= ' pending';
if ($isHidden) $cardClass .= ' hidden-app';

// Данные для JS (редактирование)
$appForJs = $app;
$appForJs['images'] = $images;
?>

<div class="<?= $cardClass ?>" data-app-id="<?= $app['id'] ?>">
    <?php include __DIR__ . '/card-header.php'; ?>
    
    <?php include __DIR__ . '/card-status.php'; ?>
    
    <?php include __DIR__ . '/card-message.php'; ?>
    
    <?php include __DIR__ . '/card-images.php'; ?>
    
    <?php include __DIR__ . '/card-relevant.php'; ?>
    
    <?php include __DIR__ . '/card-contacts.php'; ?>
    
    <?php include __DIR__ . '/card-footer.php'; ?>
</div>
