<!-- Пароль -->
<div class="settings-card">
	<h3>Смена пароля</h3>
	<form id="passwordForm">
		<div class="form-group">
			<label class="form-label">Текущий пароль</label>
			<div class="password-toggle">
				<input type="password" name="current_password" class="form-input">
				<button type="button" class="password-toggle-btn" data-toggle="password">
					<svg width="18" height="18"><use href="#icon-eye"/></svg>
				</button>
			</div>
		</div>
		<div class="form-row">
			<div class="form-group">
				<label class="form-label">Новый пароль</label>
				<div class="password-toggle">
					<input type="password" name="new_password" class="form-input">
					<button type="button" class="password-toggle-btn" data-toggle="password">
						<svg width="18" height="18"><use href="#icon-eye"/></svg>
					</button>
				</div>
			</div>
			<div class="form-group">
				<label class="form-label">Подтверждение</label>
				<div class="password-toggle">
					<input type="password" name="new_password_confirm" class="form-input">
					<button type="button" class="password-toggle-btn" data-toggle="password">
						<svg width="18" height="18"><use href="#icon-eye"/></svg>
					</button>
				</div>
			</div>
		</div>
		<button type="submit" class="btn btn-primary btn-sm">Изменить</button>
	</form>
</div>