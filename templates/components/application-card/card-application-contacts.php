<?php if ($effectiveDiscord || $effectiveTelegram || $effectiveVk): ?>
	<div class="app-contacts" style="display:flex;flex-wrap:wrap;gap:0.375rem;margin:0.75rem 0;">
		<?php if ($effectiveDiscord): ?>
			<span class="contact-btn discord" style="font-size:0.75rem;">
				<svg width="12" height="12"><use href="#icon-discord"/></svg>
				<?= e($effectiveDiscord) ?>
			</span>
		<?php endif; ?>
		<?php if ($effectiveTelegram): ?>
			<a href="https://t.me/<?= e(ltrim($effectiveTelegram, '@')) ?>" target="_blank" class="contact-btn telegram" style="font-size:0.75rem;">
				<svg width="12" height="12"><use href="#icon-telegram"/></svg>
				<?= e($effectiveTelegram) ?>
			</a>
		<?php endif; ?>
		<?php if ($effectiveVk): ?>
			<a href="https://vk.com/<?= e($effectiveVk) ?>" target="_blank" class="contact-btn vk" style="font-size:0.75rem;">
				<svg width="12" height="12"><use href="#icon-vk"/></svg>
				<?= e($effectiveVk) ?>
			</a>
		<?php endif; ?>
	</div>
<?php endif; ?>