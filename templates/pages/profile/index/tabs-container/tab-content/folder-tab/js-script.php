<script>
(function() {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    const isOwner = <?= $isOwner ? 'true' : 'false' ?>;
    const userId = <?= $profileUser['id'] ?>;
    
    let currentParentId = null;
    let currentItemId = null;
    let currentItemType = null;

    // Свернуть/развернуть
    window.toggleItem = function(itemId) {
        const item = document.querySelector(`[data-item-id="${itemId}"]`);
        if (item) {
            item.classList.toggle('collapsed');
            fetch('/api/user-folder/toggle-collapse', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
                body: JSON.stringify({ id: itemId })
            });
        }
    };

    // Открыть модалку создания
    window.showCreateModal = function(parentId) {
        currentParentId = parentId;
        window.openModal('folderCreateModal');
    };

    // Выбрать тип создания
    window.selectCreateType = function(type) {
        currentItemType = type;
        window.closeModal('folderCreateModal');
        
        const titles = {
            folder: 'Новая папка', chat: 'Новый чат', modpack: 'Новый модпак',
            mod: 'Новый мод', server: 'Новый сервер', shortcut: 'Новый ярлык'
        };
        
        document.getElementById('nameModalTitle').textContent = titles[type] || 'Название';
        document.getElementById('nameFormParentId').value = currentParentId || '';
        document.getElementById('nameFormType').value = type;
        document.getElementById('nameFormInput').value = '';
        document.getElementById('nameFormDescription').value = '';
        
        setTimeout(() => {
            window.openModal('folderNameModal');
            document.getElementById('nameFormInput').focus();
        }, 200);
    };

    // Кнопка создания (пустая папка)
    document.getElementById('folderCreateBtn')?.addEventListener('click', function() {
        showCreateModal(null);
    });

    // Форма создания
    document.getElementById('folderNameForm')?.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const res = await fetch('/api/user-folder/create', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
            body: JSON.stringify({
                type: formData.get('type'),
                name: formData.get('name'),
                description: formData.get('description'),
                parent_id: formData.get('parent_id') || null
            })
        });
        
        if (res.ok) {
            location.reload();
        } else {
            const data = await res.json();
            alert(data.error || 'Ошибка');
        }
    });

    // Открыть настройки
    window.showSettingsModal = async function(itemId) {
        currentItemId = itemId;
        
        const res = await fetch(`/api/user-folder/item?id=${itemId}`);
        if (!res.ok) { alert('Ошибка загрузки'); return; }
        
        const { item } = await res.json();
        
        document.getElementById('settingsModalTitle').textContent = 'Настройки: ' + item.name;
        document.getElementById('settingsFormId').value = item.id;
        document.getElementById('settingsFormName').value = item.name || '';
        document.getElementById('settingsFormDescription').value = item.description || '';
        document.getElementById('settingsFormColor').value = item.color || '#3b82f6';
        
        window.openModal('folderSettingsModal');
    };

    // Форма настроек
    document.getElementById('folderSettingsForm')?.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const res = await fetch('/api/user-folder/update', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
            body: JSON.stringify({
                id: formData.get('id'),
                name: formData.get('name'),
                description: formData.get('description'),
                color: formData.get('color')
            })
        });
        
        if (res.ok) {
            location.reload();
        } else {
            const data = await res.json();
            alert(data.error || 'Ошибка');
        }
    });

    // Удалить элемент
    window.deleteItem = async function() {
        if (!currentItemId) return;
        if (!confirm('Удалить этот элемент и все вложенные?')) return;
        
        const res = await fetch('/api/user-folder/delete', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
            body: JSON.stringify({ id: currentItemId })
        });
        
        if (res.ok) {
            location.reload();
        } else {
            const data = await res.json();
            alert(data.error || 'Ошибка');
        }
    };

    // Подписка
    window.toggleSubscription = async function(ownerId, subscribe) {
        const endpoint = subscribe ? '/api/user-folder/subscribe' : '/api/user-folder/unsubscribe';
        const res = await fetch(endpoint, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
            body: JSON.stringify({ user_id: ownerId })
        });
        if (res.ok) location.reload();
    };

    // Открыть сайдбар с информацией
    window.openItemSidebar = function(itemId, type) {
        // TODO: реализовать выдвигающийся сайдбар
        console.log('Open sidebar:', itemId, type);
    };

    // Drag and Drop
    if (isOwner) {
        let draggedItem = null;
        
        document.querySelectorAll('.community-folder[draggable="true"]').forEach(item => {
            item.addEventListener('dragstart', function(e) {
                draggedItem = this;
                this.classList.add('dragging');
                e.dataTransfer.effectAllowed = 'move';
            });
            
            item.addEventListener('dragend', function() {
                this.classList.remove('dragging');
                document.querySelectorAll('.drag-over').forEach(el => el.classList.remove('drag-over'));
                draggedItem = null;
            });
            
            item.addEventListener('dragover', function(e) {
                e.preventDefault();
                if (draggedItem === this) return;
                this.classList.add('drag-over');
            });
            
            item.addEventListener('dragleave', function() {
                this.classList.remove('drag-over');
            });
            
            item.addEventListener('drop', async function(e) {
                e.preventDefault();
                this.classList.remove('drag-over');
                if (!draggedItem || draggedItem === this) return;
                
                const itemId = draggedItem.dataset.itemId;
                const targetId = this.dataset.itemId;
                const targetType = this.dataset.type;
                
                // Если цель - сущность, кладём внутрь
                const entities = ['folder', 'chat', 'modpack', 'mod'];
                const newParentId = entities.includes(targetType) ? targetId : null;
                
                const res = await fetch('/api/user-folder/move', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
                    body: JSON.stringify({
                        item_id: itemId,
                        parent_id: newParentId,
                        after_id: entities.includes(targetType) ? null : targetId
                    })
                });
                
                if (res.ok) location.reload();
            });
        });
    }
})();
</script>
