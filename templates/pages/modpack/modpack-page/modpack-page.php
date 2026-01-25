<div class="modpack-page">
	
	<?php include_once 'mp-header.php'; ?>
	
	<?php include_once 'mp-description.php'; ?>
	
	<div style="background:var(--surface);border-radius:8px;padding:1.25rem;margin-bottom:1.5rem;border:1px solid var(--border);">
		<h2 style="font-size:1rem;margin-bottom:0.75rem;">Оставить заявку</h2>

		<?php include_once 'mp-logic-application.php'; ?>
		
	</div>
	
	<div style="background:var(--surface);border-radius:8px;padding:1.25rem;border:1px solid var(--border);">
		<?php include_once 'mp-applications-list.php'; ?>
	</div>
	
	<?php if (!empty($applications)): ?>
		<div class="app-list">
			<?php foreach ($applications as $app): ?>
				<?php 
				$isPending = $app['status'] === 'pending'; 
				$isOwnApp = $user && $app['user_id'] === $user['id']; 
				$images = $appRepo->getImages($app['id']);
				$isExpired = $app['relevant_until'] && strtotime($app['relevant_until']) < time();
				?>
				<div class="app-card <?= $isPending ? 'pending' : '' ?>">

					<?php include_once 'mp_ac-feed-user.php'; ?>
					
					<p style="line-height:1.6;margin-bottom:0.5rem;"><?= nl2br(e($app['message'])) ?></p>
					
					<?php include_once 'mp_ac-images.php'; ?>					
					
					<?php include_once 'mp_ac-relevant-until.php'; ?>					
					
					<?php include_once 'mp_ac-feed-contacts.php'; ?>					
					
				</div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</div>