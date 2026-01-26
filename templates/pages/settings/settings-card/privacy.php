<!-- Приватность -->
<div class="settings-card">
    <h3>Приватность</h3>
    <form id="privacyForm">
        <div class="form-group" style="margin-bottom:0;">
            <label class="checkbox-label">
                <input type="checkbox" name="subscriptions_visible" <?= ($user['subscriptions_visible'] ?? 1) ? 'checked' : '' ?>>
                <span>Показывать вкладку "Подписки" в профиле</span>
            </label>
            <p class="form-hint">Если выключено, другие пользователи не увидят, на какие сообщества вы подписаны</p>
        </div>
        <button type="submit" class="btn btn-primary btn-sm" style="margin-top:1rem;">Сохранить</button>
    </form>
</div>

<script>
document.getElementById('privacyForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    const formData = new FormData(this);
    
    await fetch('/api/user/update', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
        body: JSON.stringify({
            subscriptions_visible: formData.get('subscriptions_visible') ? 1 : 0
        })
    });
    
    const btn = this.querySelector('button[type="submit"]');
    btn.textContent = 'Сохранено!';
    setTimeout(() => btn.textContent = 'Сохранить', 2000);
});
</script>
