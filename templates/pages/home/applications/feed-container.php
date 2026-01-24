<?php use App\Core\Role; ?>
<?php foreach ($applications as $app): ?>
	<?php $images = $appRepo->getImages($app['id']); ?>
	<div class="feed-card">
		<div class="feed-header">
			<a href="/@<?= e($app['login']) ?>" class="feed-user">
				<div class="feed-avatar" style="<?= getAvatarStyle($app) ?>">
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
		
		<div class="feed-footer">
			<div class="feed-contacts">
				<?php if ($app['contact_discord']): ?>
					<span class="contact-btn discord">
						<svg width="14" height="14"><use href="#icon-discord"/></svg>
						<?= e($app['contact_discord']) ?>
					</span>
				<?php endif; ?>
				<?php if ($app['contact_telegram']): ?>
					<a href="https://t.me/<?= e(ltrim($app['contact_telegram'], '@')) ?>" class="contact-btn telegram" target="_blank">
						<svg width="14" height="14"><use href="#icon-telegram"/></svg>
						<?= e($app['contact_telegram']) ?>
					</a>
				<?php endif; ?>
				<?php if ($app['contact_vk']): ?>
					<a href="https://vk.com/<?= e($app['contact_vk']) ?>" class="contact-btn vk" target="_blank">
						<svg width="14" height="14"><use href="#icon-vk"/></svg>
						<?= e($app['contact_vk']) ?>
					</a>
				<?php endif; ?>
			</div>
			<span class="feed-date"><?= date('d.m.Y', strtotime($app['created_at'])) ?></span>
		</div>
	</div>
<?php endforeach; ?>
