<?php if (!empty($images)): ?>
	<div style="display:flex;gap:0.5rem;margin:0.5rem 0;flex-wrap:wrap;">
		<?php foreach ($images as $img): ?>
			<a href="<?= e($img['image_path']) ?>" data-lightbox><img src="<?= e($img['image_path']) ?>" alt="" style="width:60px;height:60px;border-radius:4px;object-fit:cover;"></a>
		<?php endforeach; ?>
	</div>
<?php endif; ?>