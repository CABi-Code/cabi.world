<?php use App\Core\Role; ?>

<div class="feed-header">
	<a href="/profile/@<?= e($app['login']) ?>" class="feed-user">
		<div class="feed-avatar" style="<?= $avatarStyle ?>">
			<?php if (!empty($app['avatar'])): ?>
				<img src="<?= e($app['avatar']) ?>" alt="">
			<?php else: ?>
				<?= mb_strtoupper(mb_substr($app['username'], 0, 1)) ?>
			<?php endif; ?>
		</div>
		<div>
			<div class="feed-name">
				<?= e($app['username']) ?>
				<?= Role::badge($app['role'] ?? 'user') ?>
			</div>
			<div class="feed-login">@<?= e($app['login']) ?></div>
		</div>
	</a>
	<a href="/modpack/<?= e($app['platform']) ?>/<?= e($app['slug']) ?>" class="feed-modpack">
		<?php if ($app['icon_url']): ?>
			<img src="<?= e($app['icon_url']) ?>" alt="" class="feed-mp-icon">
		<?php endif; ?>
		<?= e($app['modpack_name']) ?>
	</a>
</div>