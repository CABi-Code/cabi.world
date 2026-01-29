<?php
/**
 * Модалки для вкладки сообщества
 */
?>

<!-- Модальное окно создания чата/папки -->
<div class="modal" id="communityCreateModal" style="display:none;">
    <div class="modal-backdrop" data-modal-close></div>
    <div class="modal-content modal-sm">
        <div class="modal-header">
            <h3 class="modal-title">Создать</h3>
            <button class="modal-close" data-modal-close>&times;</button>
        </div>
        <div class="modal-body">
            <div class="create-options">
                <button class="create-option" onclick="createCommunityItem('chat')">
                    <svg width="24" height="24"><use href="#icon-message-circle"/></svg>
                    <div class="create-option-content">
                        <span>Создать чат</span>
                        <p>Общайтесь с подписчиками</p>
                    </div>
                </button>
                <button class="create-option" onclick="createCommunityItem('folder')">
                    <svg width="24" height="24"><use href="#icon-folder"/></svg>
                    <div class="create-option-content">
                        <span>Создать папку</span>
                        <p>Группируйте чаты</p>
                    </div>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно ввода имени -->
<div class="modal" id="communityNameModal" style="display:none;">
    <div class="modal-backdrop" data-modal-close></div>
    <div class="modal-content modal-sm">
        <div class="modal-header">
            <h3 class="modal-title" id="nameModalTitle">Название</h3>
            <button class="modal-close" data-modal-close>&times;</button>
        </div>
        <div class="modal-body">
            <form id="communityNameForm">
                <input type="hidden" name="community_id" id="nameFormCommunityId">
                <input type="hidden" name="parent_id" id="nameFormParentId">
                <input type="hidden" name="type" id="nameFormType">
                
                <div class="form-group">
                    <label class="form-label">Название</label>
                    <input type="text" name="name" class="form-input" id="nameFormInput" maxlength="100" required>
                </div>
                
                <div class="form-group" id="descriptionGroup" style="display:none;">
                    <label class="form-label">Описание (необязательно)</label>
                    <textarea name="description" class="form-input" rows="2" id="nameFormDescription"></textarea>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-ghost" data-modal-close>Отмена</button>
                    <button type="submit" class="btn btn-primary">Создать</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Модальное окно настроек сообщества -->
<div class="modal" id="communitySettingsModal" style="display:none;">
    <div class="modal-backdrop" data-modal-close></div>
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Настройки сообщества</h3>
            <button class="modal-close" data-modal-close>&times;</button>
        </div>
        <div class="modal-body">
            <form id="communitySettingsForm">
                <input type="hidden" name="community_id" id="settingsCommunityId">
                
                <div class="settings-section">
                    <h4>Общие настройки</h4>
                    
                    <div class="form-group">
                        <label class="form-label">Тайм-аут на сообщения (секунды)</label>
                        <input type="number" name="message_timeout" class="form-input" id="settingsTimeout" min="0" placeholder="0 = без ограничений">
                        <p class="form-hint">Минимальное время между сообщениями</p>
                    </div>
                    
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="files_disabled" id="settingsFilesDisabled">
                            <span>Отключить отправку файлов</span>
                        </label>
                    </div>
                    
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="messages_disabled" id="settingsMessagesDisabled">
                            <span>Отключить отправку сообщений</span>
                        </label>
                    </div>
                </div>
                
                <div class="settings-section danger-zone">
                    <h4>Опасная зона</h4>
                    <button type="button" class="btn btn-danger btn-sm" onclick="deleteCommunity()">
                        <svg width="14" height="14"><use href="#icon-trash"/></svg>
                        Удалить сообщество
                    </button>
                    <p class="form-hint">Это действие нельзя отменить.</p>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-ghost" data-modal-close>Отмена</button>
                    <button type="submit" class="btn btn-primary">Сохранить</button>
                </div>
            </form>
        </div>
    </div>
</div>
