<?php if ($modpack['description']): ?>
	<div style="background:var(--surface);border-radius:8px;padding:1.25rem;margin-bottom:1.5rem;border:1px solid var(--border);">
		<h2 style="font-size:1rem;margin-bottom:0.5rem;">Описание</h2>
		<p style="color:var(--text-secondary);line-height:1.6;"><?= nl2br(e($modpack['description'])) ?></p>
	</div>
<?php endif; ?>