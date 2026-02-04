<?php
/**
 * Сайдбар с подписками
 * @var array $subscriptions
 * @var array|null $user
 * @var array $owner
 */
?>
<aside class="item-sidebar">
    <div class="sidebar-section">
        <div class="sidebar-title">
            <svg width="16" height="16"><use href="#icon-users"/></svg>
            Подписки
        </div>
        
        <?php if (empty($subscriptions)): ?>
            <div class="sidebar-empty">
                <?php if ($user): ?>
                    Вы ни на кого не подписаны
                <?php else: ?>
                    <a href="/login">Войдите</a>, чтобы видеть подписки
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="sidebar-subscriptions" id="sidebarSubscriptions">
                <?php foreach ($subscriptions as $sub): ?>
                    <a href="/@<?= e($sub['login']) ?>?tab=folder" 
                       class="subscription-item <?= $sub['id'] === $owner['id'] ? 'active' : '' ?>">
                        <div class="sub-avatar" style="<?= $sub['avatar'] ? '' : 'background:linear-gradient(135deg,' . ($sub['avatar_bg_value'] ?? '#3b82f6,#8b5cf6') . ')' ?>">
                            <?php if ($sub['avatar']): ?>
                                <img src="<?= e($sub['avatar']) ?>" alt="">
                            <?php else: ?>
                                <?= mb_strtoupper(mb_substr($sub['username'], 0, 1)) ?>
                            <?php endif; ?>
                        </div>
                        <span class="sub-name"><?= e($sub['username']) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Владелец элемента -->
    <div class="sidebar-section">
        <div class="sidebar-title">Владелец</div>
        <a href="/@<?= e($owner['login']) ?>" class="owner-card">
            <div class="owner-avatar" style="<?= $owner['avatar'] ? '' : 'background:linear-gradient(135deg,' . ($owner['avatar_bg_value'] ?? '#3b82f6,#8b5cf6') . ')' ?>">
                <?php if ($owner['avatar']): ?>
                    <img src="<?= e($owner['avatar']) ?>" alt="">
                <?php else: ?>
                    <?= mb_strtoupper(mb_substr($owner['username'], 0, 1)) ?>
                <?php endif; ?>
            </div>
            <div class="owner-info">
                <span class="owner-name"><?= e($owner['username']) ?></span>
                <span class="owner-login">@<?= e($owner['login']) ?></span>
            </div>
        </a>
    </div>
</aside>
