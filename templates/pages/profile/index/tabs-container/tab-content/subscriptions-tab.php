<?php
/**
 * Вкладка "Подписки" в профиле
 * 
 * @var array $subscriptions
 * @var int $subscriptionsCount
 * @var bool $isOwner
 */
?>

<?php if ($showSubscriptions): ?>
<div class="tab-pane <?= $activeTab === 'subscriptions' ? 'active' : '' ?>" id="tab-subscriptions">
	<?php if (!empty($subscriptions)): ?>
		<div class="subscriptions-list">
			<?php foreach ($subscriptions as $sub): ?>
				<?php
				$avatarColors = explode(',', $sub['owner_avatar_bg'] ?? '#3b82f6,#8b5cf6');
				$avatarStyle = $sub['owner_avatar'] 
					? '' 
					: 'background:linear-gradient(135deg,' . $avatarColors[0] . ',' . ($avatarColors[1] ?? $avatarColors[0]) . ')';
				?>
				<a href="/@<?= e($sub['owner_login']) ?>?tab=community" class="subscription-item">
					<div class="subscription-avatar" style="<?= $avatarStyle ?>">
						<?php if ($sub['owner_avatar']): ?>
							<img src="<?= e($sub['owner_avatar']) ?>" alt="">
						<?php else: ?>
							<?= mb_strtoupper(mb_substr($sub['owner_username'], 0, 1)) ?>
						<?php endif; ?>
					</div>
					<div class="subscription-info">
						<span class="subscription-name"><?= e($sub['owner_username']) ?></span>
						<span class="subscription-login">@<?= e($sub['owner_login']) ?></span>
					</div>
					<div class="subscription-meta">
						<span class="subscription-subscribers">
							<svg width="12" height="12"><use href="#icon-users"/></svg>
							<?= number_format($sub['subscribers_count'], 0, '', ' ') ?>
						</span>
					</div>
				</a>
			<?php endforeach; ?>
		</div>
	<?php elseif ($isOwner): ?>
		<div class="empty-state">
			<svg width="48" height="48" class="empty-icon"><use href="#icon-star"/></svg>
			<p>Вы пока ни на что не подписаны</p>
			<p class="empty-hint">Подписывайтесь на сообщества других пользователей</p>
		</div>
	<?php else: ?>
		<div class="empty-state">
			<svg width="48" height="48" class="empty-icon"><use href="#icon-star"/></svg>
			<p>Нет подписок</p>
		</div>
	<?php endif; ?>
</div>
<?php endif; ?>