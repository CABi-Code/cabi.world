<?php if ($app['relevant_until']): ?>
	<p style="font-size:0.8125rem;color:<?= $isExpired ? 'var(--danger)' : 'var(--text-muted)' ?>;margin-bottom:0.5rem;">
		<svg width="12" height="12" style="vertical-align:-2px;"><use href="#icon-clock"/></svg>
		<?= $isExpired ? 'Истёк:' : 'Актуально до:' ?> <?= date('d.m.Y', strtotime($app['relevant_until'])) ?>
	</p>
<?php endif; ?>