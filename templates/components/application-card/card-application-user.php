<?php if ($showUser && !empty($app['username'])): ?>
	<div class="app-user" style="display:flex;align-items:center;gap:0.5rem;margin-bottom:0.5rem;">
		<?php if (!empty($app['user_avatar'])): ?>
			<img src="<?= e($app['user_avatar']) ?>" alt="" style="width:24px;height:24px;border-radius:50%;">
		<?php else: ?>
			<div style="width:24px;height:24px;border-radius:50%;background:var(--primary-light);display:flex;align-items:center;justify-content:center;">
				<svg width="12" height="12" style="color:var(--primary);"><use href="#icon-user"/></svg>
			</div>
		<?php endif; ?>
		<a href="/user/<?= e($app['username']) ?>" style="font-weight:500;color:var(--text);">
			<?= e($app['username']) ?>
		</a>
	</div>
<?php endif; ?>