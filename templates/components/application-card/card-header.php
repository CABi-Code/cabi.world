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
    // Аватар может быть в поле avatar или user_avatar в зависимости от запроса
    $userAvatar = $app['avatar'] ?? $app['user_avatar'] ?? null;
    // Градиент для аватара
    $avatarBgValue = $app['avatar_bg_value'] ?? '#3b82f6,#8b5cf6';
    $avatarColors = explode(',', $avatarBgValue);
    $avatarGradient = 'background:linear-gradient(135deg,' . $avatarColors[0] . ',' . ($avatarColors[1] ?? $avatarColors[0]) . ')';
    ?>
    <div class="app-user" style="display:flex;align-items:center;gap:0.5rem;margin-bottom:0.5rem;">
        <?php if (!empty($userAvatar)): ?>
            <img src="<?= e($userAvatar) ?>" alt="" style="width:24px;height:24px;border-radius:50%;object-fit:cover;">
        <?php else: ?>
            <div style="width:24px;height:24px;border-radius:50%;<?= $avatarGradient ?>;display:flex;align-items:center;justify-content:center;color:#fff;font-size:0.625rem;font-weight:600;">
                <?= mb_strtoupper(mb_substr($app['username'], 0, 1)) ?>
            </div>
        <?php endif; ?>
        <a href="/@<?= e($app['login'] ?? '') ?>" style="font-weight:500;color:var(--text);">
            <?= e($app['username']) ?>
        </a>
    </div>
<?php endif; ?>