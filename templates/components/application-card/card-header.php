<?php if ($showModpack && !empty($app['modpack_name'])): ?>
    <div class="app-header">
        <?php if (!empty($app['icon_url'])): ?>
            <img src="<?= e($app['icon_url']) ?>" alt="" class="app-icon">
        <?php endif; ?>
        <div style="flex:1;">
            <a href="/modpack/<?= e($app['platform'] ?? '') ?>/<?= e($app['slug'] ?? '') ?>" class="app-modpack">
                <?= e($app['modpack_name']) ?>
            </a>
        </div>
    </div>
<?php endif; ?>

<?php if ($showUser && !empty($app['username'])): ?>
    <?php
    // Аватар может быть в поле avatar (из APP_WITH_USER/APP_FULL)
    $userAvatar = $app['avatar'] ?? $app['user_avatar'] ?? null;
    // Градиент для аватара
    $avatarBgValue = $app['avatar_bg_value'] ?? '#3b82f6,#8b5cf6';
    $avatarColors = explode(',', $avatarBgValue);
    $avatarGradient = 'background:linear-gradient(135deg,' . $avatarColors[0] . ',' . ($avatarColors[1] ?? $avatarColors[0]) . ')';
    // Логин пользователя
    $userLogin = $app['login'] ?? $app['user_login'] ?? '';
    ?>
    <div class="app-user">
        <a href="/@<?= e($userLogin) ?>" class="app-user-link">
            <?php if (!empty($userAvatar)): ?>
                <img src="<?= e($userAvatar) ?>" alt="" class="app-user-avatar">
            <?php else: ?>
                <div class="app-user-avatar app-user-avatar--placeholder" style="<?= $avatarGradient ?>">
                    <?= mb_strtoupper(mb_substr($app['username'], 0, 1)) ?>
                </div>
            <?php endif; ?>
            <span class="app-user-name"><?= e($app['username']) ?></span>
        </a>
    </div>
<?php endif; ?>
