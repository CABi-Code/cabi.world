<div class="form-group">
    <label class="form-label">Контакты для связи</label>
    
    <div class="contacts-mode-selector">
        <label class="radio-option">
            <input 
                type="radio" 
                name="contacts_mode" 
                value="default" 
                class="contacts-mode-radio"
                <?= $useDefaultContacts ? 'checked' : '' ?>
            >
            <span class="radio-label">
                По умолчанию
                <span class="info-tooltip" title="Будут использоваться контакты из вашего профиля. При изменении контактов в профиле они автоматически обновятся в заявке.">
                    <svg width="14" height="14"><use href="#icon-info"/></svg>
                </span>
            </span>
        </label>
        
        <label class="radio-option">
            <input 
                type="radio" 
                name="contacts_mode" 
                value="custom" 
                class="contacts-mode-radio"
                <?= !$useDefaultContacts ? 'checked' : '' ?>
            >
            <span class="radio-label">На выбор</span>
        </label>
    </div>
    
    <div class="contacts-default-info" style="<?= $useDefaultContacts ? '' : 'display:none;' ?>">
        <?php if ($userDiscord || $userTelegram || $userVk): ?>
            <div class="current-contacts">
                <span style="color:var(--text-muted);font-size:0.8125rem;">Ваши текущие контакты:</span>
                <div class="contacts-preview" style="margin-top:0.375rem;">
                    <?php if ($userDiscord): ?>
                        <span class="contact-btn discord" style="font-size:0.75rem;">
                            <svg width="12" height="12"><use href="#icon-discord"/></svg>
                            <?= e($userDiscord) ?>
                        </span>
                    <?php endif; ?>
                    <?php if ($userTelegram): ?>
                        <span class="contact-btn telegram" style="font-size:0.75rem;">
                            <svg width="12" height="12"><use href="#icon-telegram"/></svg>
                            <?= e($userTelegram) ?>
                        </span>
                    <?php endif; ?>
                    <?php if ($userVk): ?>
                        <span class="contact-btn vk" style="font-size:0.75rem;">
                            <svg width="12" height="12"><use href="#icon-vk"/></svg>
                            <?= e($userVk) ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-warning" style="font-size:0.8125rem;margin-top:0.5rem;">
                У вас не указаны контакты в профиле. 
                <a href="/settings" target="_blank">Добавить контакты</a>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="contacts-custom-fields" style="<?= $useDefaultContacts ? 'display:none;' : '' ?>">
        <div class="form-row" style="margin-top:0.75rem;">
            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label" style="font-size:0.8125rem;">Discord</label>
                <input 
                    type="text" 
                    name="discord" 
                    class="form-input app-field-discord" 
                    value="<?= e($useDefaultContacts ? $userDiscord : ($appDiscord ?? $userDiscord)) ?>"
                    placeholder="username"
                >
            </div>
            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label" style="font-size:0.8125rem;">Telegram</label>
                <input 
                    type="text" 
                    name="telegram" 
                    class="form-input app-field-telegram" 
                    value="<?= e($useDefaultContacts ? $userTelegram : ($appTelegram ?? $userTelegram)) ?>"
                    placeholder="@username"
                >
            </div>
            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label" style="font-size:0.8125rem;">VK</label>
                <input 
                    type="text" 
                    name="vk" 
                    class="form-input app-field-vk" 
                    value="<?= e($useDefaultContacts ? $userVk : ($appVk ?? $userVk)) ?>"
                    placeholder="id или username"
                >
            </div>
        </div>
        <div class="form-hint" style="margin-top:0.5rem;">
            Укажите хотя бы один способ связи
        </div>
    </div>
</div>
