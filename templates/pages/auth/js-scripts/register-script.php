<script>
let turnstileToken = null;
let isSubmitting = false;

function onTurnstileSuccess(token) {
    turnstileToken = token;
    document.querySelector('[data-error="captcha"]')?.classList.remove('show');
}

function onTurnstileError() {
    turnstileToken = null;
    const el = document.querySelector('[data-error="captcha"]');
    if (el) {
        el.textContent = 'Ошибка загрузки капчи, обновите страницу';
        el.classList.add('show');
    }
}

function clearErrors() {
    document.querySelectorAll('.form-error').forEach(el => {
        el.textContent = '';
        el.classList.remove('show');
    });
}

function showErrors(errors) {
    for (const [field, message] of Object.entries(errors)) {
        const el = document.querySelector(`[data-error="${field}"]`);
        if (el) {
            el.textContent = message;
            el.classList.add('show');
        }
    }
}

function resetButton(btn) {
    btn.disabled = false;
    btn.textContent = 'Зарегистрироваться';
    isSubmitting = false;
}

document.getElementById('registerForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    // Защита от повторной отправки
    if (isSubmitting) return;
    
    clearErrors();
    
    const password = this.password.value;
    const confirm = this.password_confirm.value;
    
    if (password !== confirm) {
        showErrors({ password_confirm: 'Пароли не совпадают' });
        return;
    }
    
    const btn = this.querySelector('button[type="submit"]');
    
    // Проверка капчи на клиенте
    const turnstileEnabled = document.querySelector('.cf-turnstile') !== null;
    if (turnstileEnabled && !turnstileToken) {
        showErrors({ captcha: 'Подтвердите, что вы не робот' });
        return;
    }
    
    isSubmitting = true;
    btn.disabled = true;
    btn.textContent = 'Регистрация...';
    
    const formData = new FormData(this);
    if (turnstileToken) {
        formData.append('cf-turnstile-response', turnstileToken);
    }
    
    try {
        const res = await fetch('/api/auth/register', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content
            },
            body: JSON.stringify(Object.fromEntries(formData))
        });
        
        const data = await res.json();
        
        if (data.success && data.redirect) {
            window.location.href = data.redirect;
            return;
        } else if (data.success) {
            window.location.href = '/';
            return;
        } else {
            showErrors(data.errors || { general: data.error || 'Ошибка регистрации' });
            
            if (typeof turnstile !== 'undefined') {
                turnstile.reset();
                turnstileToken = null;
            }
            
            resetButton(btn);
        }
    } catch (err) {
        console.error('Register error:', err);
        showErrors({ general: 'Ошибка сети, попробуйте снова' });
        resetButton(btn);
    }
});
</script>