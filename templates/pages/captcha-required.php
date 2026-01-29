<?php
/**
 * Страница требования капчи при блокировке
 * 
 * @var string $siteKey
 * @var int $retryAfter
 * @var string $title
 */

$config = require CONFIG_PATH . '/app.php';
$siteKey = $config['turnstile']['site_key'] ?? '';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e($title) ?> — cabi.world</title>
    <link rel="stylesheet" href="/css/app.css">
    <style>
        .captcha-page { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 1rem; }
        .captcha-card { background: var(--surface); border-radius: 12px; padding: 2rem; max-width: 400px; width: 100%; text-align: center; border: 1px solid var(--border); }
        .captcha-icon { width: 64px; height: 64px; margin: 0 auto 1.5rem; color: var(--warning); }
        .captcha-title { font-size: 1.25rem; font-weight: 600; margin-bottom: 0.5rem; }
        .captcha-text { color: var(--text-secondary); margin-bottom: 1.5rem; font-size: 0.9375rem; }
        .captcha-timer { font-size: 0.875rem; color: var(--text-muted); margin-top: 1rem; }
        .cf-turnstile { display: flex; justify-content: center; margin: 1rem 0; }
        .captcha-error { color: var(--danger); font-size: 0.875rem; margin-top: 0.5rem; display: none; }
        .captcha-error.show { display: block; }
        .captcha-success { color: var(--success); font-size: 0.875rem; margin-top: 0.5rem; display: none; }
        .captcha-success.show { display: block; }
    </style>
</head>
<body>
    <div class="captcha-page">
        <div class="captcha-card">
            <svg class="captcha-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            
            <h1 class="captcha-title">Проверка безопасности</h1>
            <p class="captcha-text">
                Мы обнаружили необычную активность с вашего устройства. 
                Пожалуйста, подтвердите, что вы не робот.
            </p>
            
            <?php if ($siteKey): ?>
                <div class="cf-turnstile" 
                     data-sitekey="<?= e($siteKey) ?>" 
                     data-theme="dark"
                     data-callback="onCaptchaSuccess"
                     data-error-callback="onCaptchaError">
                </div>
                
                <div class="captcha-error" id="captchaError">
                    Ошибка проверки. Попробуйте обновить страницу.
                </div>
                
                <div class="captcha-success" id="captchaSuccess">
                    Проверка пройдена! Перенаправление...
                </div>
                
                <p class="captcha-timer">
                    Блокировка истекает через <span id="timer"><?= $retryAfter ?></span> сек.
                </p>
            <?php else: ?>
                <p class="captcha-text">
                    Пожалуйста, подождите <span id="timer"><?= $retryAfter ?></span> секунд и обновите страницу.
                </p>
                <button class="btn btn-primary" onclick="location.reload()" style="margin-top: 1rem;">
                    Обновить страницу
                </button>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    <script>
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
        let timerValue = <?= $retryAfter ?>;
        
        // Таймер
        const timerEl = document.getElementById('timer');
        const timerInterval = setInterval(() => {
            timerValue--;
            if (timerEl) timerEl.textContent = timerValue;
            if (timerValue <= 0) {
                clearInterval(timerInterval);
                location.reload();
            }
        }, 1000);
        
        async function onCaptchaSuccess(token) {
            try {
                const res = await fetch('/api/captcha/solve', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': csrf
                    },
                    body: JSON.stringify({ 'cf-turnstile-response': token })
                });
                
                const data = await res.json();
                
                if (data.success) {
                    document.getElementById('captchaSuccess').classList.add('show');
                    document.getElementById('captchaError').classList.remove('show');
                    
                    // Редирект на предыдущую страницу или главную
                    setTimeout(() => {
                        const returnUrl = new URLSearchParams(location.search).get('return') || '/';
                        location.href = returnUrl;
                    }, 1000);
                } else {
                    document.getElementById('captchaError').textContent = data.error || 'Ошибка проверки';
                    document.getElementById('captchaError').classList.add('show');
                    
                    if (typeof turnstile !== 'undefined') {
                        turnstile.reset();
                    }
                }
            } catch (err) {
                document.getElementById('captchaError').classList.add('show');
            }
        }
        
        function onCaptchaError() {
            document.getElementById('captchaError').classList.add('show');
        }
    </script>
</body>
</html>