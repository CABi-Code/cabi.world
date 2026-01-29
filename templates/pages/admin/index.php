<?php
/**
 * Панель управления - Заявки
 * 
 * @var array $user
 * @var array $applications
 * @var int $pendingCount
 * @var int $totalPages
 * @var int $page
 * @var string|null $status
 */

use App\Core\Role;

$adminPath = __DIR__;
?>

<div class="admin-page">
    <div class="admin-header">
        <h1 class="admin-title">
            <svg width="24" height="24"><use href="#icon-settings"/></svg>
            Панель управления
        </h1>
        <div class="admin-user">
            <?= Role::badge($user['role']) ?>
            <span><?= e($user['username']) ?></span>
        </div>
    </div>
    
    <div class="admin-layout">
        <aside class="admin-sidebar">
            <nav class="admin-nav">
                <a href="/admin" class="admin-nav-item active">
                    <svg width="18" height="18"><use href="#icon-send"/></svg>
                    Заявки
                    <?php if ($pendingCount > 0): ?>
                        <span class="admin-nav-badge"><?= $pendingCount ?></span>
                    <?php endif; ?>
                </a>
            </nav>
        </aside>
        
        <?php require $adminPath . '/admin-content.php'; ?>
    </div>
</div>

<?php require $adminPath . '/js-scripts/main.php'; ?>
