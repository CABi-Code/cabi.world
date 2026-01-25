<?php if (!empty($images)): ?>
	<div style="display:flex;gap:0.5rem;margin:0.75rem 0;flex-wrap:wrap;">
		<?php foreach ($images as $img): ?>
			<a href="<?= e($img['image_path']) ?>" data-lightbox class="app-image-thumb">
				<img src="<?= e($img['image_path']) ?>" alt="" style="width:60px;height:60px;border-radius:4px;object-fit:cover;">
			</a>
		<?php endforeach; ?>
	</div>
<?php endif; ?>