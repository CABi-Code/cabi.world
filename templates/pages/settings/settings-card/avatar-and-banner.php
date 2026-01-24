<!-- Аватар и баннер -->
<div class="settings-card">
	<h3>Изображения профиля</h3>
	
	<div class="form-group">
		<label class="form-label">Аватар</label>
		<div class="upload-box" id="avatarUpload">
			<div class="upload-preview circle">
				<?php if ($user['avatar']): ?>
					<img src="<?= e($user['avatar']) ?>" alt="">
				<?php else: ?>
					<svg width="24" height="24"><use href="#icon-camera"/></svg>
				<?php endif; ?>
			</div>
			<div class="upload-text">
				<p><?= $user['avatar'] ? 'Изменить аватар' : 'Загрузить аватар' ?></p>
				<span>JPG, PNG до 5MB</span>
			</div>
		</div>
		<input type="file" id="avatarInput" accept="image/*" hidden>
		<?php if ($user['avatar']): ?>
			<div class="upload-actions">
				<button type="button" class="btn btn-ghost btn-sm" style="color:var(--danger)" id="deleteAvatar">
					<svg width="14" height="14"><use href="#icon-trash"/></svg>
					Удалить аватар
				</button>
			</div>
		<?php endif; ?>
	</div>
	
	<div class="form-group" style="margin-bottom:0;">
		<label class="form-label">Баннер</label>
		<div class="upload-box" id="bannerUpload">
			<div class="upload-preview" style="width:100px;height:40px;">
				<?php if ($user['banner']): ?>
					<img src="<?= e($user['banner']) ?>" alt="">
				<?php else: ?>
					<svg width="24" height="24"><use href="#icon-image"/></svg>
				<?php endif; ?>
			</div>
			<div class="upload-text">
				<p><?= $user['banner'] ? 'Изменить баннер' : 'Загрузить баннер' ?></p>
				<span>Рекомендуется 1200×300</span>
			</div>
		</div>
		<input type="file" id="bannerInput" accept="image/*" hidden>
		<?php if ($user['banner']): ?>
			<div class="upload-actions">
				<button type="button" class="btn btn-ghost btn-sm" style="color:var(--danger)" id="deleteBanner">
					<svg width="14" height="14"><use href="#icon-trash"/></svg>
					Удалить баннер
				</button>
			</div>
		<?php endif; ?>
	</div>
</div>