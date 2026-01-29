<?php
/**
 * Вкладка с заявками в профиле
 * 
 * @var array $applications
 * @var bool $isOwner
 * @var object $appRepo
 */
?>

<div class="tab-pane <?= $activeTab === 'applications' ? 'active' : '' ?>" id="tab-applications">

	<?php if (!empty($applications)): ?>
		<div class="app-list">
			<?php foreach ($applications as $app): ?>
				<?php 
				$showModpack = true;
				$showUser = false;
				include TEMPLATES_PATH . '/components/application-card/card.php'; 
				?>
			<?php endforeach; ?>
		</div>
	<?php elseif ($isOwner): ?>
		<div class="empty-state">
			<svg width="48" height="48" class="empty-icon"><use href="#icon-send"/></svg>
			<p>У вас пока нет заявок</p>
			<a href="/modrinth" class="btn btn-primary btn-sm">Найти модпак</a>
		</div>
	<?php else: ?>
		<div class="empty-state">
			<svg width="48" height="48" class="empty-icon"><use href="#icon-send"/></svg>
			<p>Нет заявок</p>
		</div>
	<?php endif; ?>
	
</div>
