<?php if ($page > 1): ?>
	<a href="?page=<?= $page - 1 ?>&sort=<?= e($sort) ?>" class="page-item">&laquo;</a>
<?php endif; ?>
<?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
	<a href="?page=<?= $i ?>&sort=<?= e($sort) ?>" class="page-item <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
<?php endfor; ?>
<?php if ($page < $totalPages): ?>
	<a href="?page=<?= $page + 1 ?>&sort=<?= e($sort) ?>" class="page-item">&raquo;</a>
<?php endif; ?>