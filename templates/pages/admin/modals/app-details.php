<?php
/**
 * Модалка деталей заявки для админ-панели
 * Загружается динамически через AJAX
 * 
 * @var array $app - данные заявки
 */

$colors = explode(',', $app['avatar_bg_value'] ?? '#3b82f6,#8b5cf6');
$avatarStyle = 'background:linear-gradient(135deg,' . ($colors[0] ?? '#3b82f6') . ',' . ($colors[1] ?? $colors[0]) . ')';
$statusLabels = ['pending' => 'Ожидает', 'accepted' => 'Одобрена', 'rejected' => 'Отклонена'];
?>
<div id="appDetailsModal" class="modal" style="display:none;" data-app-id="<?= $app['id'] ?>">
    <div class="modal-backdrop" data-modal-close></div>
    <div class="modal-content modal-lg">
        <div class="modal-header">
            <h3 class="modal-title">Заявка #<?= $app['id'] ?></h3>
            <button type="button" class="modal-close" data-modal-close>
                <svg width="20" height="20"><use href="#icon-x"/></svg>
            </button>
        </div>
        
        <div class="modal-body">
            <div class="app-detail">
                <div class="app-detail-header">
                    <a href="/@<?= e($app['login']) ?>" class="admin-user-link">
                        <div class="admin-avatar" style="<?= empty($app['avatar']) ? $avatarStyle : '' ?>">
                            <?php if ($app['avatar']): ?>
                                <img src="<?= e($app['avatar']) ?>" alt="">
                            <?php else: ?>
                                <?= mb_strtoupper(mb_substr($app['username'], 0, 1)) ?>
                            <?php endif; ?>
                        </div>
                        <div>
                            <div class="admin-username"><?= e($app['username']) ?></div>
                            <div class="admin-login">@<?= e($app['login']) ?></div>
                        </div>
                    </a>
                    <span class="app-status status-<?= $app['status'] ?>">
                        <?= $statusLabels[$app['status']] ?? $app['status'] ?>
                    </span>
                </div>
                
                <div class="app-detail-body">
                    <p class="app-detail-message"><?= nl2br(e($app['message'])) ?></p>
                </div>
                
                <?php if (!empty($app['images'])): ?>
                <div class="app-detail-images">
                    <?php foreach ($app['images'] as $img): ?>
                        <img src="<?= e($img['image_path']) ?>" alt="" class="app-detail-img">
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <?php if ($app['effective_discord'] || $app['effective_telegram'] || $app['effective_vk']): ?>
                <div class="app-detail-contacts">
                    <?php if ($app['effective_discord']): ?>
                        <span class="contact-btn discord">
                            <svg width="14" height="14"><use href="#icon-discord"/></svg>
                            <?= e($app['effective_discord']) ?>
                        </span>
                    <?php endif; ?>
                    <?php if ($app['effective_telegram']): ?>
                        <a href="https://t.me/<?= e(ltrim($app['effective_telegram'], '@')) ?>" 
                           class="contact-btn telegram" target="_blank">
                            <svg width="14" height="14"><use href="#icon-telegram"/></svg>
                            <?= e($app['effective_telegram']) ?>
                        </a>
                    <?php endif; ?>
                    <?php if ($app['effective_vk']): ?>
                        <a href="https://vk.com/<?= e($app['effective_vk']) ?>" 
                           class="contact-btn vk" target="_blank">
                            <svg width="14" height="14"><use href="#icon-vk"/></svg>
                            <?= e($app['effective_vk']) ?>
                        </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <div class="app-detail-meta">
                    <span>Создана: <?= date('d.m.Y H:i', strtotime($app['created_at'])) ?></span>
                    <?php if (!empty($app['modpack_name'])): ?>
                        <a href="/modpack/<?= e($app['platform']) ?>/<?= e($app['slug']) ?>" class="admin-modpack-link">
                            <?= e($app['modpack_name']) ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="modal-footer">
            <button type="button" class="btn btn-ghost btn-sm" data-modal-close>Закрыть</button>
            <?php if ($app['status'] !== 'accepted'): ?>
                <button type="button" class="btn admin-btn-accept btn-sm" 
                        onclick="setAppStatus(<?= $app['id'] ?>, 'accepted')">
                    <svg width="14" height="14"><use href="#icon-check"/></svg> Одобрить
                </button>
            <?php endif; ?>
            <?php if ($app['status'] !== 'rejected'): ?>
                <button type="button" class="btn admin-btn-reject btn-sm" 
                        onclick="setAppStatus(<?= $app['id'] ?>, 'rejected')">
                    <svg width="14" height="14"><use href="#icon-x"/></svg> Отклонить
                </button>
            <?php endif; ?>
        </div>
    </div>
</div>
