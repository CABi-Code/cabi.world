// ========== Модальные окна ==========

function showCreateItemModal(parentId) {
    currentParentId = parentId;
    selectedItemType = null;
    
    // Сброс формы
    document.getElementById('createItemName').value = '';
    document.getElementById('createItemDescription').value = '';
    document.getElementById('createItemFields').style.display = 'none';
    document.getElementById('serverFields').style.display = 'none';
    document.getElementById('shortcutFields').style.display = 'none';
    document.getElementById('createItemSubmit').disabled = true;
    
    // Сброс выбора типа
    document.querySelectorAll('.item-type-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    openModal('createItemModal');
}

function selectItemType(type) {
    selectedItemType = type;
    
    // Обновляем UI
    document.querySelectorAll('.item-type-btn').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.type === type);
    });
    
    document.getElementById('createItemFields').style.display = 'block';
    document.getElementById('serverFields').style.display = type === 'server' ? 'block' : 'none';
    document.getElementById('shortcutFields').style.display = type === 'shortcut' ? 'block' : 'none';
    document.getElementById('createItemSubmit').disabled = false;
    
    // Фокус на поле названия
    document.getElementById('createItemName').focus();
}

async function showItemSettings(itemId) {
    currentEditItemId = itemId;
    const content = document.getElementById('itemSettingsBody');
    content.innerHTML = '<div class="loading">Загрузка...</div>';
    
    openModal('itemSettingsModal');
    
    try {
        const res = await fetch(`/api/user-folder/item?id=${itemId}`);
        const data = await res.json();
        
        if (!res.ok) {
            content.innerHTML = '<div class="error">Ошибка загрузки</div>';
            return;
        }
        
        content.innerHTML = renderSettingsForm(data.item, data.details);
        
    } catch (err) {
        console.error('Load settings error:', err);
        content.innerHTML = '<div class="error">Ошибка загрузки</div>';
    }
}

function renderSettingsForm(item, details) {
    let html = `
        <div class="form-group">
            <label class="form-label">Название</label>
            <input type="text" class="form-input" id="editItemName" value="${escapeHtml(item.name)}">
        </div>
        <div class="form-group">
            <label class="form-label">Описание</label>
            <textarea class="form-textarea" id="editItemDescription" rows="2">${escapeHtml(item.description || '')}</textarea>
        </div>
        <div class="form-group">
            <label class="form-label">Иконка</label>
            <select class="form-select" id="editItemIcon">
                ${getIconOptions(item.icon || 'folder')}
            </select>
        </div>
        <div class="form-group">
            <label class="form-label">Цвет</label>
            <input type="color" class="form-input form-color" id="editItemColor" value="${item.color || '#3b82f6'}">
        </div>
    `;
    
    // Дополнительные поля для сервера
    if (item.item_type === 'server' && details) {
        html += `
            <hr class="form-divider">
            <h4 class="form-section-title">Настройки сервера</h4>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Адрес</label>
                    <input type="text" class="form-input" id="editServerAddress" value="${escapeHtml(details.address || '')}">
                </div>
                <div class="form-group" style="width: 100px;">
                    <label class="form-label">Порт</label>
                    <input type="number" class="form-input" id="editServerPort" value="${details.port || 25565}">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Версия</label>
                <input type="text" class="form-input" id="editServerVersion" value="${escapeHtml(details.version || '')}">
            </div>
        `;
    }
    
    // Дополнительные поля для ярлыка
    if (item.item_type === 'shortcut' && details) {
        html += `
            <hr class="form-divider">
            <h4 class="form-section-title">Настройки ярлыка</h4>
            <div class="form-group">
                <label class="form-label">URL</label>
                <input type="url" class="form-input" id="editShortcutUrl" value="${escapeHtml(details.url || '')}">
            </div>
        `;
    }
    
    return html;
}

function getIconOptions(selected) {
    const icons = [
        'folder', 'package', 'puzzle', 'server', 'message-circle', 'link',
        'star', 'heart', 'bookmark', 'flag', 'tag', 'box', 'archive',
        'file', 'file-text', 'image', 'video', 'music', 'code', 'terminal',
        'globe', 'home', 'settings', 'tool', 'zap', 'shield', 'lock'
    ];
    
    return icons.map(icon => 
        `<option value="${icon}" ${icon === selected ? 'selected' : ''}>${icon}</option>`
    ).join('');
}

function openModal(id) {
    document.getElementById(id).classList.add('open');
    document.body.classList.add('modal-open');
}

function closeModal(id) {
    document.getElementById(id).classList.remove('open');
    document.body.classList.remove('modal-open');
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
