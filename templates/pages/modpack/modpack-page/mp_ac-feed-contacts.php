<div class="feed-contacts">
	<?php if ($app['contact_discord']): ?><span class="contact-btn discord"><svg width="14" height="14"><use href="#icon-discord"/></svg><?= e($app['contact_discord']) ?></span><?php endif; ?>
	<?php if ($app['contact_telegram']): ?><a href="https://t.me/<?= e(ltrim($app['contact_telegram'], '@')) ?>" class="contact-btn telegram" target="_blank"><svg width="14" height="14"><use href="#icon-telegram"/></svg><?= e($app['contact_telegram']) ?></a><?php endif; ?>
	<?php if ($app['contact_vk']): ?><a href="https://vk.com/<?= e($app['contact_vk']) ?>" class="contact-btn vk" target="_blank"><svg width="14" height="14"><use href="#icon-vk"/></svg><?= e($app['contact_vk']) ?></a><?php endif; ?>
</div>