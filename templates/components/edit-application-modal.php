<?php
/**
 * Модальное окно редактирования заявки
 * 
 * @var array $application - данные заявки для редактирования
 * @var string $modalId - ID модального окна (по умолчанию 'editAppModal')
 */

$modalId = $modalId ?? 'editAppModal';
$maxRelevantDate = date('Y-m-d', strtotime('+31 days'));
$minRelevantDate = date('Y-m-d');
$defaultRelevantDate = date('Y-m-d', strtotime('+7 days'));

// Значения по умолчанию из заявки, если она передана
$appId = $application['id'] ?? '';
$appMessage = $application['message'] ?? '';
$appRelevantUntil = $application['relevant_until'] ?? $defaultRelevantDate;
$appDiscord = $application['contact_discord'] ?? '';
$appTelegram = $application['contact_telegram'] ?? '';
$appVk = $application['contact_vk'] ?? '';
?>

<div id="<?= e($modalId) ?>" class="modal" style="display:none;">
    <div class="modal-overlay" data-close></div>
    <div class="modal-content">
        <h3>Редактировать заявку</h3>
        <div class="alert alert-warning" style="font-size:0.8125rem;">После редактирования заявка снова будет на рассмотрении</div>
        <form id="<?= e($modalId) ?>Form" class="edit-app-form">
            <input type="hidden" name="id" class="edit-app-id" value="<?= e($appId) ?>">
            <div class="form-group">
                <label class="form-label">Сообщение <span style="font-weight:400;color:var(--text-muted);">(макс. 2000 символов)</span></label>
                <textarea name="message" class="form-input edit-app-message" rows="3" required maxlength="2000"><?= e($appMessage) ?></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Актуально до <span style="font-weight:400;color:var(--text-muted);">(обязательно, макс. 1 месяц)</span></label>
                <input type="date" name="relevant_until" class="form-input edit-app-relevant" 
                       value="<?= e($appRelevantUntil) ?>" 
                       min="<?= $minRelevantDate ?>" 
                       max="<?= $maxRelevantDate ?>" 
                       required>
                <div class="form-hint">Влияет на сортировку в поиске. После этой даты заявка уйдёт вниз списка.</div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Discord</label>
                    <input type="text" name="discord" class="form-input edit-app-discord" value="<?= e($appDiscord) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Telegram</label>
                    <input type="text" name="telegram" class="form-input edit-app-telegram" value="<?= e($appTelegram) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">VK</label>
                    <input type="text" name="vk" class="form-input edit-app-vk" value="<?= e($appVk) ?>">
                </div>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary btn-sm" data-close>Отмена</button>
                <button type="submit" class="btn btn-primary btn-sm">Сохранить</button>
            </div>
        </form>
    </div>
</div>

<script>
(function() {
    const modal = document.getElementById('<?= e($modalId) ?>');
    if (!modal) return;
    
    const form = modal.querySelector('form');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    
    // Закрытие модального окна
    modal.querySelectorAll('[data-close]').forEach(el => {
        el.addEventListener('click', () => modal.style.display = 'none');
    });
    
    // Обработка формы
    form?.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const btn = form.querySelector('[type="submit"]');
        const originalText = btn.textContent;
        btn.disabled = true;
        btn.textContent = '...';
        
        const data = Object.fromEntries(new FormData(form));
        
        // Валидация на клиенте
        if (!data.message?.trim()) {
            showFormError(form, 'message', 'Введите сообщение');
            btn.disabled = false;
            btn.textContent = originalText;
            return;
        }
        
        if (!data.relevant_until) {
            showFormError(form, 'relevant_until', 'Укажите дату актуальности');
            btn.disabled = false;
            btn.textContent = originalText;
            return;
        }
        
        // Проверка диапазона дат
        const relevantDate = new Date(data.relevant_until);
        const minDate = new Date('<?= $minRelevantDate ?>');
        const maxDate = new Date('<?= $maxRelevantDate ?>');
        
        if (relevantDate < minDate || relevantDate > maxDate) {
            showFormError(form, 'relevant_until', 'Дата должна быть от сегодня до <?= date('d.m.Y', strtotime('+31 days')) ?>');
            btn.disabled = false;
            btn.textContent = originalText;
            return;
        }
        
        try {
            const res = await fetch('/api/application/update', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
                body: JSON.stringify(data)
            });
            const result = await res.json();
            
            if (res.ok && result.success) {
                location.reload();
            } else {
                if (result.errors) {
                    Object.entries(result.errors).forEach(([field, msg]) => {
                        showFormError(form, field, msg);
                    });
                } else {
                    alert(result.error || 'Ошибка сохранения');
                }
                btn.disabled = false;
                btn.textContent = originalText;
            }
        } catch (err) {
            alert('Ошибка сети');
            btn.disabled = false;
            btn.textContent = originalText;
        }
    });
    
    function showFormError(form, field, msg) {
        const input = form.querySelector(`[name="${field}"]`);
        if (input) {
            input.classList.add('error');
            const group = input.closest('.form-group');
            if (group) {
                group.querySelector('.form-error')?.remove();
                const el = document.createElement('div');
                el.className = 'form-error';
                el.textContent = msg;
                group.appendChild(el);
            }
        }
    }
    
    // Очистка ошибок при вводе
    form?.querySelectorAll('.form-input').forEach(input => {
        input.addEventListener('input', () => {
            input.classList.remove('error');
            input.closest('.form-group')?.querySelector('.form-error')?.remove();
        });
    });
    
    // Глобальная функция для открытия модалки с данными заявки
    window.openEditAppModal = window.openEditAppModal || {};
    window.openEditAppModal['<?= e($modalId) ?>'] = function(appData) {
        modal.querySelector('.edit-app-id').value = appData.id || '';
        modal.querySelector('.edit-app-message').value = appData.message || '';
        modal.querySelector('.edit-app-relevant').value = appData.relevant_until || '<?= $defaultRelevantDate ?>';
        modal.querySelector('.edit-app-discord').value = appData.contact_discord || '';
        modal.querySelector('.edit-app-telegram').value = appData.contact_telegram || '';
        modal.querySelector('.edit-app-vk').value = appData.contact_vk || '';
        
        // Очищаем ошибки
        modal.querySelectorAll('.form-input').forEach(input => input.classList.remove('error'));
        modal.querySelectorAll('.form-error').forEach(el => el.remove());
        
        modal.style.display = 'flex';
    };
})();
</script>