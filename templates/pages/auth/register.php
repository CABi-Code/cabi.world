<div class="auth-header">
    <h1 class="auth-title">Создать аккаунт</h1>
</div>

<form id="registerForm" class="auth-form compact" novalidate>
    <div class="form-group">
        <label class="form-label" for="username">Имя</label>
        <input type="text" id="username" name="username" class="form-input" 
               placeholder="Каби Кабович" maxlength="50" autocomplete="name" required>
        <div class="form-error" data-error="username"></div>
    </div>

    <div class="form-group">
        <label class="form-label" for="email">Почта</label>
        <input type="email" id="email" name="email" class="form-input" 
               placeholder="mail@gmail.com" autocomplete="email" required>
        <div class="form-error" data-error="email"></div>
    </div>

    <div class="form-group input-group">
        <label class="form-label" for="login">Юзернейм</label>
        <div class="input-addon-wrapper">
            <span class="input-addon">@</span>
            <input type="text" id="login" name="login" class="form-input login-input" 
                   pattern="[a-zA-Z0-9_-]+" maxlength="16" placeholder="cabi" autocomplete="on" required>
        </div>
        <div class="form-hint small">Можно использовать только a-z, 0-9, -, _</div>
        <div class="form-error" data-error="login"></div>
    </div>

    <div class="form-group">
        <label class="form-label" for="password">Пароль</label>
        <div class="password-toggle">
            <input type="password" id="password" name="password" class="form-input" 
                   autocomplete="new-password" minlength="8" required>
            <button type="button" class="password-toggle-btn" data-toggle="password">
                <svg width="18" height="18"><use href="#icon-eye"/></svg>
            </button>
        </div>
        <div class="form-error" data-error="password"></div>
    </div>

    <div class="form-group">
        <label class="form-label" for="password_confirm">Подтверждение</label>
        <div class="password-toggle">
            <input type="password" id="password_confirm" name="password_confirm" class="form-input" 
                   autocomplete="new-password" minlength="8" required>
            <button type="button" class="password-toggle-btn" data-toggle="password">
                <svg width="18" height="18"><use href="#icon-eye"/></svg>
            </button>
        </div>
        <div class="form-error" data-error="password_confirm"></div>
    </div>

    <?php include TEMPLATES_PATH . '/components/turnstile-captcha.php'; ?>
    
    <div class="form-error" data-error="general"></div>

    <button type="submit" class="btn btn-primary btn-block btn-md">Зарегистрироваться</button>
</form>

<div class="auth-footer">
    Уже есть аккаунт? <a href="/login">Войти</a>
</div>

<?php include TEMPLATES_PATH . '/pages/auth/js-scripts/register-script.php'; ?>