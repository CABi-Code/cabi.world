<?php
/**
 * Пагинация для админ-панели
 */
if ($totalPages <= 1) return;

$baseUrl = '/admin' . ($status ? '?status=' . e($status) : '');
$sep = $status ? '&' : '?';
?>

<div class="pagination" style="margin-top:1rem;">
    <?php if ($page > 1): ?>
        <a href="<?= $baseUrl . $sep ?>page=<?= $page - 1 ?>" class="page-item">&laquo;</a>
    <?php endif; ?>
    
    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
        <a href="<?= $baseUrl . $sep ?>page=<?= $i ?>" 
           class="page-item <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
    <?php endfor; ?>
    
    <?php if ($page < $totalPages): ?>
        <a href="<?= $baseUrl . $sep ?>page=<?= $page + 1 ?>" class="page-item">&raquo;</a>
    <?php endif; ?>
</div>
