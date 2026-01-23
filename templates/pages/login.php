<div class="auth-header">
    <h1 class="auth-title">Добро пожаловать</h1>
    <p class="auth-subtitle">Войдите в свой аккаунт</p>
</div>

<form id="loginForm" class="auth-form" novalidate>
    <div class="form-group">
        <label class="form-label" for="login">Логин или Email</label>
        <input type="text" id="login" name="login" class="form-input" autocomplete="username" required>
    </div>
    
    <div class="form-group">
        <label class="form-label" for="password">Пароль</label>
        <div class="password-toggle">
            <input type="password" id="password" name="password" class="form-input" autocomplete="current-password" required>
            <button type="button" class="password-toggle-btn" data-toggle="password">
                <svg width="18" height="18"><use href="#icon-eye"/></svg>
            </button>
        </div>
    </div>
    
    <button type="submit" class="btn btn-primary btn-block btn-lg">Войти</button>
</form>

<div class="auth-footer">
    Нет аккаунта? <a href="/register">Зарегистрироваться</a>
</div>

<div class="auth-divider"><span>или</span></div>

<div style="text-align: center;">
    <a href="/forgot-password">Забыли пароль?</a>
</div>