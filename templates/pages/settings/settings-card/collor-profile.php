<!-- Цвета профиля -->
<div class="settings-card">
	<h3>Цвета профиля</h3>
	<p class="form-hint" style="margin-top:-0.5rem;margin-bottom:0.75rem;">Используются когда нет изображения</p>
	
	<div class="form-group">
		<label class="form-label">Фон баннера</label>
		<div class="color-row">
			<input type="color" class="color-input" name="banner_color1" value="<?= e(explode(',', $user['banner_bg_value'] ?? '#3b82f6')[0]) ?>">
			<input type="color" class="color-input" name="banner_color2" value="<?= e(explode(',', $user['banner_bg_value'] ?? '#3b82f6,#8b5cf6')[1] ?? '#8b5cf6') ?>">
			<span style="font-size:0.8125rem;color:var(--text-muted)">Градиент</span>
		</div>
	</div>
	
	<div class="form-group" style="margin-bottom:0;">
		<label class="form-label">Фон аватара</label>
		<div class="color-row">
			<input type="color" class="color-input" name="avatar_color1" value="<?= e(explode(',', $user['avatar_bg_value'] ?? '#3b82f6')[0]) ?>">
			<input type="color" class="color-input" name="avatar_color2" value="<?= e(explode(',', $user['avatar_bg_value'] ?? '#3b82f6,#8b5cf6')[1] ?? '#8b5cf6') ?>">
			<span style="font-size:0.8125rem;color:var(--text-muted)">Градиент</span>
		</div>
	</div>
	
	<button type="button" class="btn btn-primary btn-sm" id="saveColors" style="margin-top:1rem;">Сохранить цвета</button>
</div>