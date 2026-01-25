<div class="auth-header">
    <h1 class="auth-title">Создать аккаунт</h1>
    <!-- убрали подзаголовок — он часто не нужен -->
</div>

<form id="registerForm" class="auth-form compact" novalidate>

    <div class="form-group">
        <label class="form-label" for="email">Email</label>
        <input type="email" id="email" name="email" class="form-input" required>
    </div>

    <div class="form-group">
        <label class="form-label" for="username">Имя</label>
        <input type="text" id="username" name="username" class="form-input" maxlength="50" required>
    </div>

    <div class="form-group input-group">
        <label class="form-label" for="login">Логин</label>
        <div class="input-addon-wrapper">
            <span class="input-addon">@</span>
            <input 
                type="text" 
                id="login" 
                name="login" 
                class="form-input login-input" 
                pattern="[a-zA-Z0-9_-]+"
				maxlength="16"
                title="Только латинские буквы, цифры, - и _" 
                required
            >
        </div>
        <div class="form-hint small">Можно использовать только a-z, 0-9, -, _</div>
    </div>

    <div class="form-group">
        <label class="form-label" for="password">Пароль</label>
        <div class="password-toggle">
            <input type="password" id="password" name="password" class="form-input" required minlength="8">
            <button type="button" class="password-toggle-btn" data-toggle="password">
                <svg width="18" height="18"><use href="#icon-eye"/></svg>
            </button>
        </div>
    </div>

    <div class="form-group">
        <label class="form-label" for="password_confirm">Подтверждение</label>
        <div class="password-toggle">
            <input type="password" id="password_confirm" name="password_confirm" class="form-input" required>
            <button type="button" class="password-toggle-btn" data-toggle="password">
                <svg width="18" height="18"><use href="#icon-eye"/></svg>
            </button>
        </div>
    </div>

    <button type="submit" class="btn btn-primary btn-block btn-md">Зарегистрироваться</button>
</form>

<div class="auth-footer">
    Уже есть аккаунт? <a href="/login">Войти</a>
</div>