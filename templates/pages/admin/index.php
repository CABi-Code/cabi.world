<?php
/**
 * Панель управления - Заявки
 * 
 * @var array $user
 * @var array $applications
 * @var int $pendingCount
 * @var int $totalPages
 * @var int $page
 * @var string $status
 */

use App\Core\Role;

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
                <!-- Тут будут другие пункты меню -->
            </nav>
        </aside>
        
		
		<?php include 'admin-content.php' ?>
		
    </div>
</div>

<!-- Модальное окно просмотра заявки -->
<div id="appDetailsModal" class="modal" style="display:none;">
    <div class="modal-overlay" data-close></div>
    <div class="modal-content modal-lg">
        <h3>Детали заявки</h3>
        <div id="appDetailsContent">
            <!-- Контент загружается через JS -->
        </div>
        <div class="modal-actions">
            <button type="button" class="btn btn-secondary btn-sm" data-close>Закрыть</button>
            <button type="button" class="btn admin-btn-accept btn-sm" id="modalAcceptBtn">
                <svg width="14" height="14"><use href="#icon-check"/></svg> Одобрить
            </button>
            <button type="button" class="btn admin-btn-reject btn-sm" id="modalRejectBtn">
                <svg width="14" height="14"><use href="#icon-x"/></svg> Отклонить
            </button>
        </div>
    </div>
</div>

<?php include 'js-script.php' ?>
