<script>
(function() {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    const isOwner = <?= $isOwner ? 'true' : 'false' ?>;
    const userId = <?= $profileUser['id'] ?>;
    
    let currentParentId = null;
    let currentItemId = null;
    let draggedElement = null;
    let dropIndicator = null;

    // === Свернуть/развернуть ===
    window.toggleItem = function(itemId) {
        const item = document.querySelector(`[data-id="${itemId}"]`);
        if (!item) return;
        item.classList.toggle('collapsed');
        fetch('/api/user-folder/toggle-collapse', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
            body: JSON.stringify({ id: itemId })
        });
    };

    // === Модалка создания ===
    window.showCreateModal = function(parentId) {
        currentParentId = parentId;
        window.openModal('folderCreateModal');
    };

    window.selectCreateType = function(type) {
        window.closeModal('folderCreateModal');
        
        // Для модпака - открываем селектор
        if (type === 'modpack') {
            if (typeof window.openModpackSelector === 'function') {
                window.openModpackSelector((modpack) => {
                    createItemWithData('modpack', modpack.name, {
                        reference_id: modpack.id,
                        reference_type: 'modpacks',
                        icon: 'package',
                        color: '#8b5cf6'
                    });
                });
            } else {
                alert('Модуль выбора модпаков не загружен');
            }
            return;
        }
        
        const titles = {
            folder: 'Новая папка', chat: 'Новый чат', mod: 'Новый мод',
            server: 'Новый сервер', shortcut: 'Новый ярлык'
        };
        
        document.getElementById('nameModalTitle').textContent = titles[type] || 'Название';
        document.getElementById('nameFormParentId').value = currentParentId || '';
        document.getElementById('nameFormType').value = type;
        document.getElementById('nameFormInput').value = '';
        document.getElementById('nameFormDescription').value = '';
        
        setTimeout(() => {
            window.openModal('folderNameModal');
            document.getElementById('nameFormInput').focus();
        }, 250);
    };

    async function createItemWithData(type, name, data = {}) {
        const res = await fetch('/api/user-folder/create', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
            body: JSON.stringify({
                type, name,
                parent_id: currentParentId,
                ...data
            })
        });
        if (res.ok) location.reload();
        else alert((await res.json()).error || 'Ошибка');
    }

    document.getElementById('folderCreateBtn')?.addEventListener('click', () => showCreateModal(null));

    document.getElementById('folderNameForm')?.addEventListener('submit', async function(e) {
        e.preventDefault();
        const fd = new FormData(this);
        await createItemWithData(fd.get('type'), fd.get('name'), { description: fd.get('description') });
    });

    // === Модалка настроек ===
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

    document.getElementById('folderSettingsForm')?.addEventListener('submit', async function(e) {
        e.preventDefault();
        const fd = new FormData(this);
        const res = await fetch('/api/user-folder/update', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
            body: JSON.stringify({
                id: fd.get('id'), name: fd.get('name'),
                description: fd.get('description'), color: fd.get('color')
            })
        });
        if (res.ok) location.reload();
        else alert((await res.json()).error || 'Ошибка');
    });

    window.deleteItem = async function() {
        if (!currentItemId || !confirm('Удалить элемент и всё содержимое?')) return;
        const res = await fetch('/api/user-folder/delete', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
            body: JSON.stringify({ id: currentItemId })
        });
        if (res.ok) location.reload();
        else alert((await res.json()).error || 'Ошибка');
    };

    // === Подписка ===
    window.toggleSubscription = async function(ownerId, subscribe) {
        const endpoint = subscribe ? '/api/user-folder/subscribe' : '/api/user-folder/unsubscribe';
        const res = await fetch(endpoint, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
            body: JSON.stringify({ user_id: ownerId })
        });
        if (res.ok) location.reload();
    };

    // === Панель элемента ===
    window.openItemPanel = async function(itemId, type) {
        const panel = document.getElementById('itemPanel');
        if (!panel) return;
        
        panel.classList.add('loading');
        panel.classList.add('open');
        
        const res = await fetch(`/api/user-folder/item?id=${itemId}`);
        if (!res.ok) {
            panel.classList.remove('loading');
            panel.innerHTML = '<div class="panel-error">Ошибка загрузки</div>';
            return;
        }
        
        const { item } = await res.json();
        panel.classList.remove('loading');
        
        let content = `
            <div class="panel-header">
                <h3>${escapeHtml(item.name)}</h3>
                <button class="panel-close" onclick="closeItemPanel()">
                    <svg width="20" height="20"><use href="#icon-x"/></svg>
                </button>
            </div>
            <div class="panel-body">
        `;
        
        if (item.description) {
            content += `<p class="panel-description">${escapeHtml(item.description)}</p>`;
        }
        
        content += `<div class="panel-meta">Тип: ${item.item_type}</div>`;
        
        if (type === 'chat') {
            content += `<a href="/chat/${item.id}" class="btn btn-primary btn-sm">Открыть чат</a>`;
        }
        
        content += '</div>';
        panel.innerHTML = content;
    };

    window.closeItemPanel = function() {
        document.getElementById('itemPanel')?.classList.remove('open');
    };

    function escapeHtml(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    // === Drag and Drop с анимацией ===
    if (isOwner) {
        // Создаём индикатор drop
        dropIndicator = document.createElement('div');
        dropIndicator.className = 'drop-indicator';
        dropIndicator.style.display = 'none';
        document.body.appendChild(dropIndicator);

        document.querySelectorAll('.folder-item[draggable="true"]').forEach(item => {
            item.addEventListener('dragstart', handleDragStart);
            item.addEventListener('dragend', handleDragEnd);
            item.addEventListener('dragover', handleDragOver);
            item.addEventListener('dragleave', handleDragLeave);
            item.addEventListener('drop', handleDrop);
        });

        // Корневая зона
        const structure = document.querySelector('.community-structure');
        if (structure) {
            structure.addEventListener('dragover', handleRootDragOver);
            structure.addEventListener('drop', handleRootDrop);
        }
    }

    function handleDragStart(e) {
        draggedElement = this;
        this.classList.add('dragging');
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', this.dataset.id);
        
        // Скрываем детей от drop
        document.querySelectorAll('.folder-item').forEach(item => {
            if (isDescendantOf(item, this)) {
                item.classList.add('drag-disabled');
            }
        });
    }

    function handleDragEnd() {
        this.classList.remove('dragging');
        dropIndicator.style.display = 'none';
        document.querySelectorAll('.folder-item').forEach(item => {
            item.classList.remove('drag-over', 'drag-disabled', 'drop-before', 'drop-inside');
        });
        draggedElement = null;
    }

    function handleDragOver(e) {
        e.preventDefault();
        if (!draggedElement || draggedElement === this) return;
        if (this.classList.contains('drag-disabled')) return;
        
        const rect = this.getBoundingClientRect();
        const y = e.clientY - rect.top;
        const isEntity = this.dataset.isEntity === '1';
        
        // Убираем старые классы
        this.classList.remove('drop-before', 'drop-inside');
        
        if (isEntity && y > rect.height * 0.3 && y < rect.height * 0.7) {
            // Drop внутрь сущности
            this.classList.add('drop-inside');
        } else if (y < rect.height / 2) {
            // Drop перед элементом
            this.classList.add('drop-before');
        }
        
        this.classList.add('drag-over');
    }

    function handleDragLeave() {
        this.classList.remove('drag-over', 'drop-before', 'drop-inside');
    }

    async function handleDrop(e) {
        e.preventDefault();
        e.stopPropagation();
        
        if (!draggedElement || draggedElement === this) return;
        
        const itemId = draggedElement.dataset.id;
        const targetId = this.dataset.id;
        const targetIsEntity = this.dataset.isEntity === '1';
        const dropInside = this.classList.contains('drop-inside');
        
        let newParentId = null;
        let afterId = null;
        
        if (dropInside && targetIsEntity) {
            // Кладём внутрь
            newParentId = targetId;
        } else {
            // Кладём рядом (на том же уровне)
            newParentId = this.dataset.parent === 'root' ? null : this.dataset.parent;
            afterId = targetId;
        }
        
        const res = await fetch('/api/user-folder/move', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
            body: JSON.stringify({ item_id: itemId, parent_id: newParentId, after_id: afterId })
        });
        
        if (res.ok) location.reload();
        else alert((await res.json()).error || 'Ошибка перемещения');
    }

    function handleRootDragOver(e) {
        if (!draggedElement) return;
        if (e.target.closest('.folder-item')) return;
        e.preventDefault();
    }

    async function handleRootDrop(e) {
        if (!draggedElement) return;
        if (e.target.closest('.folder-item')) return;
        e.preventDefault();
        
        const itemId = draggedElement.dataset.id;
        const res = await fetch('/api/user-folder/move', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
            body: JSON.stringify({ item_id: itemId, parent_id: null, after_id: null })
        });
        
        if (res.ok) location.reload();
    }

    function isDescendantOf(child, parent) {
        let node = child.parentElement;
        while (node) {
            if (node === parent) return true;
            node = node.parentElement;
        }
        return false;
    }
})();
</script>
