<?php
/**
 * Модальное окно создания сервера
 */
?>
<div class="modal-overlay" id="serverCreateModal">
    <div class="modal">
        <div class="modal-header">
            <h3>Добавить сервер</h3>
            <button class="modal-close" onclick="closeModal('serverCreateModal')">
                <svg width="20" height="20"><use href="#icon-x"/></svg>
            </button>
        </div>
        
        <form id="serverCreateForm" class="modal-form">
            <input type="hidden" name="parent_id" id="serverFormParentId">
            
            <div class="form-group">
                <label for="serverFormName">Название сервера</label>
                <input type="text" id="serverFormName" name="name" required 
                       placeholder="Мой сервер" maxlength="100">
            </div>
            
            <div class="form-row">
                <div class="form-group" style="flex: 2;">
                    <label for="serverFormIp">IP адрес или домен</label>
                    <input type="text" id="serverFormIp" name="server_ip" required 
                           placeholder="play.example.com" maxlength="255">
                </div>
                <div class="form-group" style="flex: 1;">
                    <label for="serverFormPort">Порт</label>
                    <input type="number" id="serverFormPort" name="server_port" 
                           placeholder="25565" value="25565" min="1" max="65535">
                </div>
            </div>
            
            <div class="form-group">
                <label for="serverFormDescription">Описание</label>
                <textarea id="serverFormDescription" name="description" rows="2" 
                          placeholder="Краткое описание сервера" maxlength="500"></textarea>
            </div>
            
            <!-- Query настройки (опционально) -->
            <details class="form-details">
                <summary>Расширенные настройки (Query)</summary>
                
                <div class="form-group">
                    <label class="form-checkbox">
                        <input type="checkbox" id="serverQueryEnabled" name="query_enabled">
                        <span>Включить Query пинг</span>
                    </label>
                    <p class="form-hint">
                        Query позволяет получать расширенную информацию о сервере 
                        (требуется включить в server.properties)
                    </p>
                </div>
                
                <div id="querySettings" style="display: none;">
                    <div class="form-row">
                        <div class="form-group" style="flex: 2;">
                            <label for="serverQueryIp">Query IP/домен</label>
                            <input type="text" id="serverQueryIp" name="query_ip" 
                                   placeholder="Тот же, что и основной">
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label for="serverQueryPort">Query порт</label>
                            <input type="number" id="serverQueryPort" name="query_port" 
                                   placeholder="25565" min="1" max="65535">
                        </div>
                    </div>
                </div>
            </details>
            
            <div class="modal-actions">
                <button type="button" class="btn btn-ghost" onclick="closeModal('serverCreateModal')">
                    Отмена
                </button>
                <button type="submit" class="btn btn-primary">
                    <svg width="16" height="16"><use href="#icon-plus"/></svg>
                    Добавить
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('serverQueryEnabled')?.addEventListener('change', function() {
    document.getElementById('querySettings').style.display = this.checked ? 'block' : 'none';
});
</script>
