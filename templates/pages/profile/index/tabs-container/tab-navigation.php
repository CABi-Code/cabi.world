<div class="profile-tabs">
	<button 
		class="profile-tab <?= $activeTab === 'community' ? 'active' : '' ?> <?= !$canViewCommunity ? 'disabled' : '' ?>" 
		data-tab="community"
		<?= !$canViewCommunity ? 'disabled title="В папке пусто"' : '' ?>
	>
		<svg width="16" height="16"><use href="#icon-message-circle"/></svg>
		Моя папка
	</button>
	
	<button 
		class="profile-tab <?= $activeTab === 'applications' ? 'active' : '' ?>" 
		data-tab="applications"
	>
		<svg width="16" height="16"><use href="#icon-send"/></svg>
		<?= $isOwner ? 'Мои заявки' : 'Заявки' ?>
		<?php if (!empty($applications)): ?>
			<span class="tab-count"><?= count($applications) ?></span>
		<?php endif; ?>
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