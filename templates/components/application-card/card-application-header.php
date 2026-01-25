<?php if ($showModpack && !empty($app['modpack_name'])): ?>
	<div class="app-header">
		<?php if (!empty($app['icon_url'])): ?>
			<img src="<?= e($app['icon_url']) ?>" alt="" class="app-icon">
		<?php endif; ?>
		<div style="flex:1;">
			<a href="/modpack/<?= e($app['platform'] ?? '') ?>/<?= e($app['slug'] ?? '') ?>" class="app-modpack">
				<?= e($app['modpack_name']) ?>
			</a>
		</div>
	</div>
<?php endif; ?>