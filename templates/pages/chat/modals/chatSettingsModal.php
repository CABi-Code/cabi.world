<?php
/**
 * Модалка настроек чата
 * Подключается в pages/chat/index.php
 * 
 * @var array $chat
 * @var bool $isOwner
 * @var bool $isModerator
 */

if (!$isOwner && !$isModerator) return;
?>

<div class="modal" id="chatSettingsModal" style="display:none;">
    <div class="modal-backdrop" data-modal-close></div>
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Настройки чата</h3>
            <button class="modal-close" data-modal-close>&times;</button>
        </div>
        <div class="modal-body">
            <form id="chatSettingsForm">
                <div class="form-group">
                    <label class="form-label">Название</label>
                    <input type="text" name="name" class="form-input" value="<?= e($chat['name']) ?>" maxlength="100">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Описание</label>
                    <textarea name="description" class="form-input" rows="2"><?= e($chat['description'] ?? '') ?></textarea>
                </div>
                
                <div class="settings-section">
                    <h4>Ограничения</h4>
                    
                    <div class="form-group">
                        <label class="form-label">Тайм-аут на сообщения (секунды)</label>
                        <input type="number" name="message_timeout" class="form-input" 
                               value="<?= $chat['message_timeout'] ?? '' ?>" min="0" 
                               placeholder="Использовать общие настройки">
                    </div>
                    
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="files_disabled" <?= $chat['files_disabled'] ? 'checked' : '' ?>>
                            <span>Отключить отправку файлов</span>
                        </label>
                    </div>
                    
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="messages_disabled" <?= $chat['messages_disabled'] ? 'checked' : '' ?>>
                            <span>Отключить отправку сообщений</span>
                        </label>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-ghost" data-modal-close>Отмена</button>
                    <button type="submit" class="btn btn-primary">Сохранить</button>
                </div>
            </form>
        </div>
    </div>
</div>
