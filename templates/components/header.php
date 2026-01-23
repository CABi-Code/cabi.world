<?php 
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
// Получаем цвета аватара пользователя
$headerAvatarStyle = '';
if (isset($user) && $user && empty($user['avatar'])) {
    $colors = explode(',', $user['avatar_bg_value'] ?? '#3b82f6,#8b5cf6');
    $headerAvatarStyle = 'background:linear-gradient(135deg,' . $colors[0] . ',' . $colors[1] ?? $colors[0] . ')';
}
?>
<header class="header">
    <div class="header-inner">
        <div class="header-left">
            <a href="/" class="logo">cabi.world</a>
            <nav class="nav">
                <a href="/" class="nav-link <?= $path === '/' ? 'active' : '' ?>">Главная</a>
                <a href="/modrinth" class="nav-link <?= $path === '/modrinth' ? 'active' : '' ?>">Modrinth</a>
                <a href="/curseforge" class="nav-link <?= $path === '/curseforge' ? 'active' : '' ?>">CurseForge</a>
            </nav>
        </div>
        <div class="header-right">
            <!-- Theme Toggle -->
            <div class="theme-toggle" id="themeToggle">
                <button class="theme-btn" type="button" title="Сменить тему">
                    <svg width="18" height="18"><use href="#icon-sun"/></svg>
                </button>
                <div class="theme-menu">
                    <button class="theme-option" data-theme="light">
                        <span class="theme-dot light"></span>Светлая
                    </button>
                    <button class="theme-option" data-theme="dark">
                        <span class="theme-dot dark"></span>Тёмная
                    </button>
                    <button class="theme-option" data-theme="darker">
                        <span class="theme-dot darker"></span>Очень тёмная
                    </button>
                </div>
            </div>
            
            <?php if (isset($user) && $user): ?>
                <!-- Notifications -->
                <div class="notif-menu" id="notifMenu">
                    <button class="notif-btn" type="button">
                        <svg width="18" height="18"><use href="#icon-bell"/></svg>
                        <?php if (isset($unreadNotifications) && $unreadNotifications > 0): ?>
                            <span class="notif-badge"><?= $unreadNotifications > 99 ? '99+' : $unreadNotifications ?></span>
                        <?php endif; ?>
                    </button>
                    <div class="notif-dropdown">
                        <div class="notif-header">
                            <span>Уведомления</span>
                            <button type="button" class="btn-link" id="markAllRead">Прочитать</button>
                        </div>
                        <div class="notif-list" id="notifList">
                            <div class="notif-empty">Загрузка...</div>
                        </div>
                    </div>
                </div>
                
                <!-- User Menu -->
                <div class="user-menu">
                    <button class="user-btn" type="button">
                        <div class="user-avatar" style="<?= $headerAvatarStyle ?>">
                            <?php if (!empty($user['avatar'])): ?>
                                <img src="<?= e($user['avatar']) ?>" alt="">
                            <?php else: ?>
                                <?= mb_strtoupper(mb_substr($user['username'], 0, 1)) ?>
                            <?php endif; ?>
                        </div>
                        <span><?= e($user['username']) ?></span>
                        <svg width="14" height="14"><use href="#icon-chevron-down"/></svg>
                    </button>
                    <div class="user-dropdown">
                        <a href="/profile/@<?= e($user['login']) ?>" class="dropdown-item">
                            <svg width="16" height="16"><use href="#icon-user"/></svg>Профиль
                        </a>
                        <a href="/settings" class="dropdown-item">
                            <svg width="16" height="16"><use href="#icon-settings"/></svg>Настройки
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="/logout" class="dropdown-item danger">
                            <svg width="16" height="16"><use href="#icon-logout"/></svg>Выйти
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <a href="/login" class="btn btn-primary btn-sm">Войти</a>
            <?php endif; ?>
        </div>
    </div>
</header>