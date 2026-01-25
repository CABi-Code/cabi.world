<?php
/**
 * Универсальная карточка заявки
 * 
 * @var array $app - данные заявки
 * @var array|null $user - текущий пользователь (для определения владельца)
 * @var bool $showModpack - показывать ли ссылку на модпак
 * @var bool $showUser - показывать ли информацию о пользователе
 * @var ApplicationRepository $appRepo - репозиторий (для получения изображений)
 */

$showModpack = $showModpack ?? true;
$showUser = $showUser ?? true;

$isOwner = $user && isset($app['user_id']) && $app['user_id'] == $user['id'];
$isPending = ($app['status'] ?? '') === 'pending';
$isHidden = !empty($app['is_hidden']);

// Получаем изображения заявки
$images = [];
if (isset($appRepo) && $appRepo instanceof \App\Repository\ApplicationRepository) {
    $images = $appRepo->getImages($app['id']);
}

// Эффективные контакты - используем effective_* если есть, иначе contact_*, иначе из профиля пользователя
$effectiveDiscord = $app['effective_discord'] 
    ?? $app['contact_discord'] 
    ?? ($showUser && isset($app['user_discord']) ? $app['user_discord'] : null)
    ?? '';
$effectiveTelegram = $app['effective_telegram'] 
    ?? $app['contact_telegram'] 
    ?? ($showUser && isset($app['user_telegram']) ? $app['user_telegram'] : null)
    ?? '';
$effectiveVk = $app['effective_vk'] 
    ?? $app['contact_vk'] 
    ?? ($showUser && isset($app['user_vk']) ? $app['user_vk'] : null)
    ?? '';

$cardClass = 'app-card';
if ($isPending) $cardClass .= ' pending';
if ($isHidden) $cardClass .= ' hidden-app';

// Данные для JS (редактирование)
$appForJs = $app;
$appForJs['effective_discord'] = $effectiveDiscord;
$appForJs['effective_telegram'] = $effectiveTelegram;
$appForJs['effective_vk'] = $effectiveVk;
$appForJs['images'] = $images;
?>