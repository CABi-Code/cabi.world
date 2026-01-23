<?php
/**
 * @var int $page
 * @var int $totalPages
 * @var string $baseUrl
 */
$sep = strpos($baseUrl, '?') !== false ? '&' : '?';
?>
<div class="pagination">
    <?php if ($page > 1): ?>
        <a href="<?= e($baseUrl . $sep . 'page=1') ?>" class="pagination-item">««</a>
        <a href="<?= e($baseUrl . $sep . 'page=' . ($page - 1)) ?>" class="pagination-item">‹</a>
    <?php else: ?>
        <span class="pagination-item disabled">««</span>
        <span class="pagination-item disabled">‹</span>
    <?php endif; ?>
    
    <?php
    $start = max(1, $page - 2);
    $end = min($totalPages, $page + 2);
    if ($start > 1) echo '<span class="pagination-item disabled">...</span>';
    for ($i = $start; $i <= $end; $i++):
    ?>
        <?php if ($i === $page): ?>
            <span class="pagination-item active"><?= $i ?></span>
        <?php else: ?>
            <a href="<?= e($baseUrl . $sep . 'page=' . $i) ?>" class="pagination-item"><?= $i ?></a>
        <?php endif; ?>
    <?php endfor; ?>
    <?php if ($end < $totalPages) echo '<span class="pagination-item disabled">...</span>'; ?>
    
    <?php if ($page < $totalPages): ?>
        <a href="<?= e($baseUrl . $sep . 'page=' . ($page + 1)) ?>" class="pagination-item">›</a>
        <a href="<?= e($baseUrl . $sep . 'page=' . $totalPages) ?>" class="pagination-item">»»</a>
    <?php else: ?>
        <span class="pagination-item disabled">›</span>
        <span class="pagination-item disabled">»»</span>
    <?php endif; ?>
</div>
