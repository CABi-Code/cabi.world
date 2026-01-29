<?php
$config = require CONFIG_PATH . '/app.php';
$siteKey = $config['turnstile']['site_key'];
$enabled = $config['turnstile']['enabled'];

if (!$enabled || empty($siteKey)) return;
?>

<div class="form-group">
    <div class="cf-turnstile" 
         data-sitekey="<?= e($siteKey) ?>" 
         data-theme="dark"
         data-callback="onTurnstileSuccess"
         data-error-callback="onTurnstileError">
    </div>
    <div class="form-error" data-error="captcha"></div>
</div>

<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>