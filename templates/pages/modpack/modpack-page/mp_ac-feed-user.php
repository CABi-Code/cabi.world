<div class="feed-user" style="margin-bottom:0.5rem;">
	<a href="/@<?= e($app['login']) ?>" class="feed-avatar">
		<?php if ($app['avatar']): ?><img src="<?= e($app['avatar']) ?>" alt=""><?php else: ?><?= mb_strtoupper(mb_substr($app['username'], 0, 1)) ?><?php endif; ?>
	</a>
	<div>
		<a href="/@<?= e($app['login']) ?>" class="feed-name"><?= e($app['username']) ?></a>
		<div style="font-size:0.75rem;color:var(--text-muted);"><?= date('d.m.Y H:i', strtotime($app['created_at'])) ?></div>
	</div>
	<?php if ($isOwnApp): ?>
		<span class="app-status status-<?= $app['status'] ?>" style="margin-left:auto;">
			<?= match($app['status']) { 'pending'=>'На рассмотрении', 'accepted'=>'Одобрена', 'rejected'=>'Отклонена', default=>$app['status'] } ?>
		</span>
	<?php endif; ?>
</div>
