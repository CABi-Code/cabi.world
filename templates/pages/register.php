<div class="auth-header">
    <h1 class="auth-title">Создать аккаунт</h1>
    <p class="auth-subtitle">Заполните форму для регистрации</p>
</div>

<form id="registerForm" class="auth-form" novalidate>
    <div class="form-group">
        <label class="form-label" for="login">Логин</label>
        <input type="text" id="login" name="login" class="form-input" autocomplete="username" required>
        <div class="form-hint">Только латинские буквы, цифры, - и _</div>
    </div>
    
    <div class="form-group">
        <label class="form-label" for="username">Отображаемое имя</label>
        <input type="text" id="username" name="username" class="form-input" autocomplete="name" required>
    </div>
    
    <div class="form-group">
        <label class="form-label" for="email">Email</label>
        <input type="email" id="email" name="email" class="form-input" autocomplete="email" required>
    </div>
    
    <div class="form-group">
        <label class="form-label" for="password">Пароль</label>
        <div class="password-toggle">
            <input type="password" id="password" name="password" class="form-input" autocomplete="new-password" required>
            <button type="button" class="password-toggle-btn" data-toggle="password">
                <svg width="18" height="18"><use href="#icon-eye"/></svg>
            </button>
        </div>
        <div class="form-hint">Минимум 8 символов</div>
    </div>
    
    <div class="form-group">
        <label class="form-label" for="password_confirm">Подтвердите пароль</label>
        <div class="password-toggle">
            <input type="password" id="password_confirm" name="password_confirm" class="form-input" autocomplete="new-password" required>
            <button type="button" class="password-toggle-btn" data-toggle="password">
                <svg width="18" height="18"><use href="#icon-eye"/></svg>
            </button>
        </div>
    </div>
    
    <button type="submit" class="btn btn-primary btn-block btn-lg">Зарегистрироваться</button>
</form>

<div class="auth-footer">
    Уже есть аккаунт? <a href="/login">Войти</a>
</div>