<!-- Контакты -->
<div class="settings-card">
	<h3>Контакты</h3>
	<form id="contactsForm">
		<div class="form-row">
			<div class="form-group">
				<label class="form-label">Discord</label>
				<input type="text" name="discord" class="form-input" value="<?= e($user['discord'] ?? '') ?>" placeholder="username">
			</div>
			<div class="form-group">
				<label class="form-label">Telegram</label>
				<input type="text" name="telegram" class="form-input" value="<?= e($user['telegram'] ?? '') ?>" placeholder="@username">
			</div>
			<div class="form-group">
				<label class="form-label">VK</label>
				<input type="text" name="vk" class="form-input" value="<?= e($user['vk'] ?? '') ?>" placeholder="id">
			</div>
		</div>
		<button type="submit" class="btn btn-primary btn-sm">Сохранить</button>
	</form>
</div>