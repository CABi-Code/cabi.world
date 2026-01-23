<div class="auth-header">
    <h1 class="auth-title">Забыли пароль?</h1>
    <p class="auth-subtitle">Введите email для восстановления</p>
</div>

<form id="forgotForm" class="auth-form" novalidate>
    <div class="form-group">
        <label class="form-label" for="email">Email</label>
        <input type="email" id="email" name="email" class="form-input" autocomplete="email" required>
    </div>
    
    <button type="submit" class="btn btn-primary btn-block btn-lg">Отправить ссылку</button>
</form>

<div class="auth-footer">
    Вспомнили пароль? <a href="/login">Войти</a>
</div>
