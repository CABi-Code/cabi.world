<?php
/**
 * Модальные окна для "Моей папки"
 */
?>

<!-- Модалка создания элемента -->
<div class="modal" id="createItemModal">
    <div class="modal-overlay" onclick="closeModal('createItemModal')"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Добавить элемент</h3>
            <button class="modal-close" onclick="closeModal('createItemModal')">
                <svg width="20" height="20"><use href="#icon-x"/></svg>
            </button>
        </div>
        
        <div class="modal-body">
            <!-- Выбор типа -->
            <div class="form-group">
                <label class="form-label">Тип</label>
                <div class="item-type-grid">
                    <button class="item-type-btn" data-type="category" onclick="selectItemType('category')">
                        <svg width="24" height="24"><use href="#icon-folder"/></svg>
                        <span>Категория</span>
                    </button>
                    <button class="item-type-btn" data-type="modpack" onclick="selectItemType('modpack')">
                        <svg width="24" height="24"><use href="#icon-package"/></svg>
                        <span>Модпак</span>
                    </button>
                    <button class="item-type-btn" data-type="mod" onclick="selectItemType('mod')">
                        <svg width="24" height="24"><use href="#icon-puzzle"/></svg>
                        <span>Мод</span>
                    </button>
                    <button class="item-type-btn" data-type="server" onclick="selectItemType('server')">
                        <svg width="24" height="24"><use href="#icon-server"/></svg>
                        <span>Сервер</span>
                    </button>
                    <button class="item-type-btn" data-type="chat" onclick="selectItemType('chat')">
                        <svg width="24" height="24"><use href="#icon-message-circle"/></svg>
                        <span>Чат</span>
                    </button>
                    <button class="item-type-btn" data-type="shortcut" onclick="selectItemType('shortcut')">
                        <svg width="24" height="24"><use href="#icon-link"/></svg>
                        <span>Ярлык</span>
                    </button>
                </div>
            </div>
            
            <!-- Общие поля -->
            <div id="createItemFields" style="display: none;">
                <div class="form-group">
                    <label class="form-label">Название</label>
                    <input type="text" class="form-input" id="createItemName" placeholder="Введите название">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Описание <span class="form-hint">(необязательно)</span></label>
                    <textarea class="form-textarea" id="createItemDescription" rows="2"></textarea>
                </div>
                
                <!-- Дополнительные поля для сервера -->
                <div id="serverFields" style="display: none;">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Адрес сервера</label>
                            <input type="text" class="form-input" id="serverAddress" placeholder="play.server.com">
                        </div>
                        <div class="form-group" style="width: 100px;">
                            <label class="form-label">Порт</label>
                            <input type="number" class="form-input" id="serverPort" value="25565">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Версия</label>
                        <input type="text" class="form-input" id="serverVersion" placeholder="1.20.1">
                    </div>
                </div>
                
                <!-- Дополнительные поля для ярлыка -->
                <div id="shortcutFields" style="display: none;">
                    <div class="form-group">
                        <label class="form-label">URL</label>
                        <input type="url" class="form-input" id="shortcutUrl" placeholder="https://...">
                    </div>
                </div>
            </div>
        </div>
        
        <div class="modal-footer">
            <button class="btn btn-ghost" onclick="closeModal('createItemModal')">Отмена</button>
            <button class="btn btn-primary" id="createItemSubmit" onclick="createItem()" disabled>Создать</button>
        </div>
    </div>
</div>

<!-- Модалка настроек элемента -->
<div class="modal" id="itemSettingsModal">
    <div class="modal-overlay" onclick="closeModal('itemSettingsModal')"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Настройки</h3>
            <button class="modal-close" onclick="closeModal('itemSettingsModal')">
                <svg width="20" height="20"><use href="#icon-x"/></svg>
            </button>
        </div>
        
        <div class="modal-body" id="itemSettingsBody">
            <!-- Загружается динамически -->
        </div>
        
        <div class="modal-footer">
            <button class="btn btn-danger btn-sm" onclick="deleteCurrentItem()">
                <svg width="14" height="14"><use href="#icon-trash"/></svg>
                Удалить
            </button>
            <div style="flex: 1;"></div>
            <button class="btn btn-ghost" onclick="closeModal('itemSettingsModal')">Отмена</button>
            <button class="btn btn-primary" onclick="saveItemSettings()">Сохранить</button>
        </div>
    </div>
</div>
