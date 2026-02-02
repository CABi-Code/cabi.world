// ========== CRUD операции ==========

async function createItem() {
    if (!selectedItemType) return;
    
    const name = document.getElementById('createItemName').value.trim();
    if (!name && !['application', 'chat'].includes(selectedItemType)) {
        alert('Введите название');
        return;
    }
    
    const data = {
        type: selectedItemType,
        name: name,
        parent_id: currentParentId,
        description: document.getElementById('createItemDescription').value.trim()
    };
    
    // Дополнительные поля
    if (selectedItemType === 'server') {
        data.address = document.getElementById('serverAddress').value.trim();
        data.port = parseInt(document.getElementById('serverPort').value) || 25565;
        data.version = document.getElementById('serverVersion').value.trim();
    } else if (selectedItemType === 'shortcut') {
        data.url = document.getElementById('shortcutUrl').value.trim();
        if (!data.url) {
            alert('Введите URL');
            return;
        }
    }
    
    try {
        const res = await fetch('/api/user-folder/create', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
            body: JSON.stringify(data)
        });
        
        const result = await res.json();
        
        if (res.ok && result.success) {
            closeModal('createItemModal');
            location.reload();
        } else {
            alert(result.error || 'Ошибка создания');
        }
    } catch (err) {
        console.error('Create error:', err);
        alert('Ошибка сети');
    }
}

async function saveItemSettings() {
    if (!currentEditItemId) return;
    
    const name = document.getElementById('editItemName')?.value.trim();
    const description = document.getElementById('editItemDescription')?.value.trim();
    const icon = document.getElementById('editItemIcon')?.value;
    const color = document.getElementById('editItemColor')?.value;
    
    const data = { id: currentEditItemId };
    if (name) data.name = name;
    if (description !== undefined) data.description = description;
    if (icon) data.icon = icon;
    if (color) data.color = color;
    
    try {
        const res = await fetch('/api/user-folder/update', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
            body: JSON.stringify(data)
        });
        
        if (res.ok) {
            closeModal('itemSettingsModal');
            location.reload();
        } else {
            const result = await res.json();
            alert(result.error || 'Ошибка сохранения');
        }
    } catch (err) {
        console.error('Save error:', err);
    }
}

async function deleteCurrentItem() {
    if (!currentEditItemId) return;
    if (!confirm('Удалить этот элемент? Все вложенные элементы также будут удалены.')) return;
    
    try {
        const res = await fetch('/api/user-folder/delete', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
            body: JSON.stringify({ id: currentEditItemId })
        });
        
        if (res.ok) {
            closeModal('itemSettingsModal');
            location.reload();
        } else {
            const result = await res.json();
            alert(result.error || 'Ошибка удаления');
        }
    } catch (err) {
        console.error('Delete error:', err);
    }
}

function toggleFolderItem(itemId) {
    const item = document.querySelector(`.folder-item[data-id="${itemId}"]`);
    if (item) {
        item.classList.toggle('collapsed');
        
        // Сохраняем состояние на сервере
        fetch('/api/user-folder/toggle-collapse', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
            body: JSON.stringify({ id: itemId })
        }).catch(console.error);
    }
}
