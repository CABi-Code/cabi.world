<!-- Профиль -->
<div class="settings-card">
	<h3>Профиль</h3>
	<form id="profileForm">
		<div class="form-group">
			<label class="form-label">Логин</label>
			<div class="input-with-prefix">
				<span class="input-prefix">@</span>
				<input type="text" class="form-input" value="<?= e($user['login']) ?>" disabled style="cursor:not-allowed;">
			</div>
			<div class="form-hint">Логин нельзя изменить</div>
		</div>
		<div class="form-group">
			<label class="form-label">Имя</label>
			<input type="text" name="username" class="form-input" value="<?= e($user['username']) ?>">
		</div>
		<div class="form-group" style="margin-bottom:0;">
			<label class="form-label">О себе</label>
			<textarea name="bio" class="form-input" rows="2"><?= e($user['bio'] ?? '') ?></textarea>
		</div>
		<button type="submit" class="btn btn-primary btn-sm" style="margin-top:1rem;">Сохранить</button>
	</form>
</div>