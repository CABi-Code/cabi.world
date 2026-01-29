<?php
/**
 * Контент админ-панели - таблица заявок
 */
use App\Core\Role;
?>

<main class="admin-content">
    <div class="admin-card">
        <div class="admin-card-header">
            <h2>Модерация заявок</h2>
            <div class="admin-filters">
                <a href="/admin?status=pending" class="admin-filter-btn <?= $status === 'pending' ? 'active' : '' ?>">
                    На рассмотрении
                    <?php if ($pendingCount > 0): ?>
                        <span class="filter-count"><?= $pendingCount ?></span>
                    <?php endif; ?>
                </a>
                <a href="/admin?status=accepted" class="admin-filter-btn <?= $status === 'accepted' ? 'active' : '' ?>">Одобренные</a>
                <a href="/admin?status=rejected" class="admin-filter-btn <?= $status === 'rejected' ? 'active' : '' ?>">Отклонённые</a>
                <a href="/admin" class="admin-filter-btn <?= !$status ? 'active' : '' ?>">Все</a>
            </div>
        </div>
        
        <?php if (empty($applications)): ?>
            <div class="admin-empty">
                <svg width="48" height="48"><use href="#icon-check"/></svg>
                <p>Нет заявок для отображения</p>
            </div>
        <?php else: ?>
            <?php include __DIR__ . '/table.php'; ?>
            <?php include __DIR__ . '/pagination.php'; ?>
        <?php endif; ?>
    </div>
</main>
