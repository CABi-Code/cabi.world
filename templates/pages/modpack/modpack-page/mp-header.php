<div class="mp-header" style="display:flex;gap:1.25rem;background:var(--surface);border-radius:10px;padding:1.25rem;margin-bottom:1.5rem;border:1px solid var(--border);">
	<?php if ($modpack['icon_url']): ?>
		<img src="<?= e($modpack['icon_url']) ?>" alt="" style="width:100px;height:100px;border-radius:8px;object-fit:cover;flex-shrink:0;">
	<?php endif; ?>
	<div style="flex:1;">
		<h1 style="font-size:1.375rem;margin-bottom:0.25rem;"><?= e($modpack['name']) ?></h1>
		<p style="color:var(--text-secondary);margin-bottom:0.5rem;"><?= e($modpack['author']) ?></p>
		<div style="display:flex;gap:1rem;margin-bottom:0.75rem;font-size:0.875rem;color:var(--text-secondary);">
			<span><svg width="14" height="14" style="vertical-align:-2px;"><use href="#icon-download"/></svg> <?= number_format($modpack['downloads']) ?></span>
			<?php if ($modpack['follows'] > 0): ?>
				<span><svg width="14" height="14" style="vertical-align:-2px;"><use href="#icon-heart"/></svg> <?= number_format($modpack['follows']) ?></span>
			<?php endif; ?>
		</div>
		<a href="<?= e($modpack['external_url']) ?>" class="btn btn-secondary btn-sm" target="_blank" rel="noopener">
			<svg width="14" height="14"><use href="#icon-external"/></svg>
			<?= $platform === 'modrinth' ? 'Modrinth' : 'CurseForge' ?>
		</a>
	</div>
</div>