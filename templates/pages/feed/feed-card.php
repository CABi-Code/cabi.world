<?php
/**
 * Карточка заявки для ленты (главная страница)
 * 
 * @var array $app - данные заявки
 * @var ApplicationRepository $appRepo
 */

// Получаем изображения
$images = $appRepo->getImages($app['id']);

// Эффективные контакты уже приходят из findAllAccepted() с COALESCE
$effectiveDiscord = $app['effective_discord'] ?? '';
$effectiveTelegram = $app['effective_telegram'] ?? '';
$effectiveVk = $app['effective_vk'] ?? '';
?>

<div class="app-card" data-app-id="<?= $app['id'] ?>">
    <div class="app-header">
        <?php if (!empty($app['icon_url'])): ?>
            <img src="<?= e($app['icon_url']) ?>" alt="" class="app-icon">
        <?php endif; ?>
        <div style="flex:1;">
            <a href="/modpack/<?= e($app['platform'] ?? '') ?>/<?= e($app['slug'] ?? '') ?>" class="app-modpack">
                <?= e($app['modpack_name'] ?? 'Неизвестный модпак') ?>
            </a>
        </div>
    </div>
    
    <?php if (!empty($app['username'])): ?>
        <div class="app-user" style="display:flex;align-items:center;gap:0.5rem;margin-bottom:0.5rem;">
            <?php if (!empty($app['user_avatar'])): ?>
                <img src="<?= e($app['user_avatar']) ?>" alt="" style="width:24px;height:24px;border-radius:50%;">
            <?php else: ?>
                <div style="width:24px;height:24px;border-radius:50%;background:var(--primary-light);display:flex;align-items:center;justify-content:center;">
                    <svg width="12" height="12" style="color:var(--primary);"><use href="#icon-user"/></svg>
                </div>
            <?php endif; ?>
            <a href="/user/<?= e($app['username']) ?>" style="font-weight:500;color:var(--text);">
                <?= e($app['username']) ?>
            </a>
        </div>
    <?php endif; ?>
    
    <p class="app-message"><?= nl2br(e($app['message'])) ?></p>
    
    <?php if (!empty($app['relevant_until'])): ?>
        <?php $isExpired = strtotime($app['relevant_until']) < time(); ?>
        <p style="font-size:0.8125rem;color:<?= $isExpired ? 'var(--danger)' : 'var(--text-muted)' ?>;margin:0.5rem 0;">
            <svg width="12" height="12" style="vertical-align:-2px;"><use href="#icon-clock"/></svg>
            <?= $isExpired ? 'Истёк:' : 'Актуально до:' ?> <?= date('d.m.Y', strtotime($app['relevant_until'])) ?>
        </p>
    <?php endif; ?>
    
    <?php if (!empty($images)): ?>
        <div style="display:flex;gap:0.5rem;margin:0.75rem 0;flex-wrap:wrap;">
            <?php foreach ($images as $img): ?>
                <a href="<?= e($img['image_path']) ?>" data-lightbox class="app-image-thumb">
                    <img src="<?= e($img['image_path']) ?>" alt="" style="width:60px;height:60px;border-radius:4px;object-fit:cover;">
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <?php if ($effectiveDiscord || $effectiveTelegram || $effectiveVk): ?>
        <div class="app-contacts" style="display:flex;flex-wrap:wrap;gap:0.375rem;margin:0.75rem 0;">
            <?php if ($effectiveDiscord): ?>
                <span class="contact-btn discord" style="font-size:0.75rem;">
                    <svg width="12" height="12"><use href="#icon-discord"/></svg>
                    <?= e($effectiveDiscord) ?>
                </span>
            <?php endif; ?>
            <?php if ($effectiveTelegram): ?>
                <a href="https://t.me/<?= e(ltrim($effectiveTelegram, '@')) ?>" target="_blank" class="contact-btn telegram" style="font-size:0.75rem;">
                    <svg width="12" height="12"><use href="#icon-telegram"/></svg>
                    <?= e($effectiveTelegram) ?>
                </a>
            <?php endif; ?>
            <?php if ($effectiveVk): ?>
                <a href="https://vk.com/<?= e($effectiveVk) ?>" target="_blank" class="contact-btn vk" style="font-size:0.75rem;">
                    <svg width="12" height="12"><use href="#icon-vk"/></svg>
                    <?= e($effectiveVk) ?>
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    
    <div class="app-footer">
        <span class="app-date"><?= date('d.m.Y H:i', strtotime($app['created_at'])) ?></span>
    </div>
</div>
