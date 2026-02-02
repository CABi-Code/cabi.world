<?php
/**
 * Модальные окна для "Моей папки"
 */
?>

<!-- Модалка создания -->
<div class="modal" id="folderCreateModal" style="display:none;">
    <div class="modal-backdrop" data-modal-close></div>
    <div class="modal-content modal-sm">
        <div class="modal-header">
            <h3 class="modal-title">Создать</h3>
            <button class="modal-close" data-modal-close>&times;</button>
        </div>
        <div class="modal-body">
            <div class="create-options">
                <button class="create-option" onclick="selectCreateType('folder')">
                    <svg width="24" height="24"><use href="#icon-folder"/></svg>
                    <div class="create-option-content">
                        <span>Папка</span>
                        <p>Группируйте элементы</p>
                    </div>
                </button>
                <button class="create-option" onclick="selectCreateType('chat')">
                    <svg width="24" height="24"><use href="#icon-message-circle"/></svg>
                    <div class="create-option-content">
                        <span>Чат</span>
                        <p>Общение с подписчиками</p>
                    </div>
                </button>
                <button class="create-option" onclick="selectCreateType('modpack')">
                    <svg width="24" height="24"><use href="#icon-package"/></svg>
                    <div class="create-option-content">
                        <span>Модпак</span>
                        <p>Добавьте модпак</p>
                    </div>
                </button>
                <button class="create-option" onclick="selectCreateType('server')">
                    <svg width="24" height="24"><use href="#icon-server"/></svg>
                    <div class="create-option-content">
                        <span>Сервер</span>
                        <p>Добавьте сервер</p>
                    </div>
                </button>
                <button class="create-option" onclick="selectCreateType('shortcut')">
                    <svg width="24" height="24"><use href="#icon-link"/></svg>
                    <div class="create-option-content">
                        <span>Ярлык</span>
                        <p>Внешняя ссылка</p>
                    </div>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Модалка ввода названия -->
<div class="modal" id="folderNameModal" style="display:none;">
    <div class="modal-backdrop" data-modal-close></div>
    <div class="modal-content modal-sm">
        <div class="modal-header">
            <h3 class="modal-title" id="nameModalTitle">Название</h3>
            <button class="modal-close" data-modal-close>&times;</button>
        </div>
        <div class="modal-body">
            <form id="folderNameForm">
                <input type="hidden" name="parent_id" id="nameFormParentId">
                <input type="hidden" name="type" id="nameFormType">
                
                <div class="form-group">
                    <label class="form-label">Название</label>
                    <input type="text" name="name" class="form-input" id="nameFormInput" maxlength="100" required>
                </div>
                
                <div class="form-group" id="descriptionGroup">
                    <label class="form-label">Описание <span class="form-hint">(необязательно)</span></label>
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

<!-- Модалка настроек элемента -->
<div class="modal" id="folderSettingsModal" style="display:none;">
    <div class="modal-backdrop" data-modal-close></div>
    <div class="modal-content modal-sm">
        <div class="modal-header">
            <h3 class="modal-title" id="settingsModalTitle">Настройки</h3>
            <button class="modal-close" data-modal-close>&times;</button>
        </div>
        <div class="modal-body">
            <form id="folderSettingsForm">
                <input type="hidden" name="id" id="settingsFormId">
                
                <div class="form-group">
                    <label class="form-label">Название</label>
                    <input type="text" name="name" class="form-input" id="settingsFormName" maxlength="100">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Описание</label>
                    <textarea name="description" class="form-input" rows="2" id="settingsFormDescription"></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Цвет</label>
                    <input type="color" name="color" class="form-input form-color" id="settingsFormColor" value="#3b82f6">
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-danger btn-sm" onclick="deleteItem()">
                        <svg width="14" height="14"><use href="#icon-trash"/></svg>
                        Удалить
                    </button>
                    <div style="flex:1"></div>
                    <button type="button" class="btn btn-ghost" data-modal-close>Отмена</button>
                    <button type="submit" class="btn btn-primary">Сохранить</button>
                </div>
            </form>
        </div>
    </div>
</div>
