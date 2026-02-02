<div class="profile-tabs">
	<button 
		class="profile-tab <?= $activeTab === 'folder' ? 'active' : '' ?> <?= !$canViewFolder ? 'disabled' : '' ?>" 
		data-tab="folder"
		<?= !$canViewFolder ? 'disabled title="Папка пуста"' : '' ?>
	>
		<svg width="16" height="16"><use href="#icon-folder"/></svg>
		Моя папка
	</button>
	
	<?php if ($showSubscriptions): ?>
	<button 
		class="profile-tab <?= $activeTab === 'subscriptions' ? 'active' : '' ?>" 
		data-tab="subscriptions"
	>
		<svg width="16" height="16"><use href="#icon-star"/></svg>
		Подписки
		<?php if ($subscriptionsCount > 0): ?>
			<span class="tab-count"><?= $subscriptionsCount ?></span>
		<?php endif; ?>
	</button>
	<?php endif; ?>
</div>
