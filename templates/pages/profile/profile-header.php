<?php use App\Core\Role; ?>

<div class="profile-header">
    <div class="profile-avatar-wrap">
        <div class="profile-avatar" style="<?= $avatarStyle ?>">
            <?php if ($profileUser['avatar']): ?>
                <img src="<?= e($profileUser['avatar']) ?>" alt="">
            <?php else: ?>
                <?= mb_strtoupper(mb_substr($profileUser['username'], 0, 1)) ?>
            <?php endif; ?>
        </div>
        <?php if ($isOwner): ?>
            <button class="avatar-edit-btn" id="avatarEditBtn">
                <svg width="12" height="12"><use href="#icon-camera"/></svg>
            </button>
            <input type="file" id="avatarInput" accept="image/*" hidden>
        <?php endif; ?>
    </div>
    
    <div class="profile-info">
        <h1 class="profile-name">
            <?= e($profileUser['username']) ?>
            <?= Role::badge($profileUser['role'] ?? 'user') ?>
        </h1>
        <p class="profile-login">@<?= e($profileUser['login']) ?></p>
        
        <?php if ($profileUser['bio']): ?>
            <p class="profile-bio"><?= nl2br(e($profileUser['bio'])) ?></p>
        <?php endif; ?>
        
        <?php if ($profileUser['discord'] || $profileUser['telegram'] || $profileUser['vk']): ?>
            <div class="profile-contacts">
                <?php if ($profileUser['discord']): ?>
                    <span class="contact-btn discord">
                        <svg width="14" height="14"><use href="#icon-discord"/></svg>
                        <?= e($profileUser['discord']) ?>
                    </span>
                <?php endif; ?>
                <?php if ($profileUser['telegram']): ?>
                    <a href="https://t.me/<?= e(ltrim($profileUser['telegram'], '@')) ?>" class="contact-btn telegram" target="_blank">
                        <svg width="14" height="14"><use href="#icon-telegram"/></svg>
                        <?= e($profileUser['telegram']) ?>
                    </a>
                <?php endif; ?>
                <?php if ($profileUser['vk']): ?>
                    <a href="https://vk.com/<?= e($profileUser['vk']) ?>" class="contact-btn vk" target="_blank">
                        <svg width="14" height="14"><use href="#icon-vk"/></svg>
                        <?= e($profileUser['vk']) ?>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <p class="profile-date">На сайте с <?= date('d.m.Y', strtotime($profileUser['created_at'])) ?></p>
    </div>
    
    <?php if ($isOwner): ?>
        <div class="profile-actions">
            <?php if ($canAccessAdmin): ?>
                <a href="/admin" class="btn btn-secondary btn-sm btn-icon" title="Панель управления">
                    <svg width="16" height="16"><use href="#icon-shield"/></svg>
                </a>
            <?php endif; ?>
            <a href="/settings" class="btn btn-secondary btn-sm">
                <svg width="14" height="14"><use href="#icon-edit"/></svg>
                Редактировать
            </a>
        </div>
    <?php endif; ?>
</div>
