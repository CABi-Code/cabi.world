<?php if (!empty($app['relevant_until'])): ?>
	<?php $isExpired = strtotime($app['relevant_until']) < time(); ?>
	<p style="font-size:0.8125rem;color:<?= $isExpired ? 'var(--danger)' : 'var(--text-muted)' ?>;margin:0.5rem 0;">
		<svg width="12" height="12" style="vertical-align:-2px;"><use href="#icon-clock"/></svg>
		<?= $isExpired ? 'Истёк:' : 'Актуально до:' ?> <?= date('d.m.Y', strtotime($app['relevant_until'])) ?>
	</p>
<?php endif; ?>