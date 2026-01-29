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

<!-- Модальное окно просмотра заявки -->
<div id="appDetailsModal" class="modal" style="display:none;">
    <div class="modal-backdrop" data-modal-close></div>
    <div class="modal-content modal-lg">
        <div class="modal-header">
            <h3 class="modal-title">Детали заявки</h3>
            <button type="button" class="modal-close" data-modal-close>
                <svg width="20" height="20"><use href="#icon-x"/></svg>
            </button>
        </div>
        <div class="modal-body" id="appDetailsContent"></div>
        <div class="modal-footer">
            <button type="button" class="btn btn-ghost btn-sm" data-modal-close>Закрыть</button>
            <button type="button" class="btn admin-btn-accept btn-sm" id="modalAcceptBtn">
                <svg width="14" height="14"><use href="#icon-check"/></svg> Одобрить
            </button>
            <button type="button" class="btn admin-btn-reject btn-sm" id="modalRejectBtn">
                <svg width="14" height="14"><use href="#icon-x"/></svg> Отклонить
            </button>
        </div>
    </div>
</div>

<?php require $adminPath . '/js-script.php'; ?>