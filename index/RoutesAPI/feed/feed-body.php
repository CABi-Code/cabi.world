<div class="feed-body">
	<p class="feed-message"><?= nl2br(e($app['message'])) ?></p>
	
	<?php if (!empty($app['relevant_until'])): ?>
		<?php $expired = strtotime($app['relevant_until']) < time(); ?>
		<div style="font-size:0.8125rem;color:<?= $expired ? 'var(--danger)' : 'var(--text-muted)' ?>;margin-top:0.5rem;">
			<svg width="12" height="12" style="vertical-align:-2px;"><use href="#icon-clock"/></svg>
			<?= $expired ? 'Истёк:' : 'До:' ?> <?= date('d.m.Y', strtotime($app['relevant_until'])) ?>
		</div>
	<?php endif; ?>
	
	<?php if (!empty($images)): ?>
		<div class="feed-images">
			<?php foreach ($images as $img): ?>
				<a href="<?= e($img['image_path']) ?>" data-lightbox>
					<img src="<?= e($img['image_path']) ?>" alt="" class="feed-img">
				</a>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</div>