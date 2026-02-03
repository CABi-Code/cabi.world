<script>
(function() {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    const isOwner = <?= $isOwner ? 'true' : 'false' ?>;
    const userId = <?= $profileUser['id'] ?>;
    const iconMap = {
        folder: { icon: 'folder', color: '#eab308' },
        chat: { icon: 'message-circle', color: '#ec4899' },
        modpack: { icon: 'package', color: '#8b5cf6' },
        mod: { icon: 'puzzle', color: '#10b981' },
        server: { icon: 'server', color: '#f59e0b' },
        application: { icon: 'file-text', color: '#3b82f6' },
        shortcut: { icon: 'link', color: '#6366f1' }
    };
    
    let currentParentId = null;
    let currentItemId = null;
    let draggedElement = null;

    // === ПАНЕЛЬ ПРОСМОТРА ===
    
    window.openItemPanel = async function(itemId, itemType) {
        const panel = document.getElementById('itemPanel');
        if (!panel) return;
        
        panel.innerHTML = '<div class="panel-loading"><div class="spinner"></div></div>';
        panel.classList.add('open');
        
        try {
            const res = await fetch(`/api/user-folder/public/item?id=${itemId}`);
            if (!res.ok) throw new Error('Not found');
            
            const { item, path, children } = await res.json();
            renderPanel(item, path, children);
        } catch (e) {
            panel.innerHTML = `
                <div class="panel-error">
                    <span>Не удалось загрузить элемент</span>
                    <button class="panel-close-btn" onclick="closeItemPanel()">
                        <svg width="16" height="16"><use href="#icon-x"/></svg>
                    </button>
                </div>
            `;
        }
    };
    
    window.closeItemPanel = function() {
        const panel = document.getElementById('itemPanel');
        if (panel) {
            panel.classList.remove('open');
            panel.innerHTML = `<div class="panel-placeholder">
                <svg width="24" height="24"><use href="#icon-info"/></svg>
                <p>Выберите элемент</p>
            </div>`;
        }
    };
    
    function renderPanel(item, path, children) {
        const panel = document.getElementById('itemPanel');
        const iconData = iconMap[item.item_type] || { icon: 'file', color: '#94a3b8' };
        const icon = item.icon || iconData.icon;
        const color = item.color || iconData.color;
        const isEntity = ['folder', 'chat', 'modpack', 'mod'].includes(item.item_type);
        
        let html = `
            <div class="panel-header">
                <button class="panel-close-btn" onclick="closeItemPanel()">
                    <svg width="18" height="18"><use href="#icon-x"/></svg>
                </button>
            </div>
            
            <div class="panel-path">${renderPath(path)}</div>
            
            <div class="panel-item-header">
                <span class="panel-icon" style="color:${esc(color)}">
                    <svg width="24" height="24"><use href="#icon-${esc(icon)}"/></svg>
                </span>
                <h3 class="panel-title">${esc(item.name)}</h3>
            </div>
        `;
        
        if (item.description) {
            html += `<p class="panel-description">${esc(item.description)}</p>`;
        }
        
        // Для чата - загружаем чат прямо здесь
        if (item.item_type === 'chat') {
            html += `<div class="panel-chat" id="panelChat" data-chat-id="${item.id}">
                <div class="chat-loading">Загрузка чата...</div>
            </div>`;
        }
        // Для папок/сущностей - показываем содержимое
        else if (isEntity && children.length > 0) {
            html += `<div class="panel-children">
                <div class="panel-children-title">Содержимое:</div>
                ${renderChildrenList(children)}
            </div>`;
        } else if (isEntity) {
            html += `<div class="panel-empty-children">Папка пуста</div>`;
        }
        
        // Для сервера - показываем IP
        if (item.item_type === 'server' && item.settings) {
            const settings = typeof item.settings === 'string' ? JSON.parse(item.settings) : item.settings;
            if (settings.ip) {
                html += `<div class="panel-server-info">
                    <span class="server-ip">${esc(settings.ip)}${settings.port && settings.port !== 25565 ? ':' + settings.port : ''}</span>
                    <button class="btn btn-ghost btn-xs" onclick="copyToClipboard('${esc(settings.ip)}')">Копировать</button>
                </div>`;
            }
        }
        
        panel.innerHTML = html;
        
        // Загружаем чат если это чат
        if (item.item_type === 'chat') {
            loadChatInPanel(item.id);
        }
    }
    
    function renderPath(path) {
        if (!path || path.length === 0) return '';
        
        // Обрезаем если слишком длинный (оставляем последние 3)
        const maxItems = 3;
        let items = path;
        let truncated = false;
        
        if (items.length > maxItems) {
            truncated = true;
            items = items.slice(-maxItems);
        }
        
        let html = '<div class="path-items">';
        
        if (truncated) {
            html += '<span class="path-ellipsis">...</span>';
        }
        
        items.forEach((item, idx) => {
            const iconData = iconMap[item.item_type] || { icon: 'file', color: '#94a3b8' };
            const isLast = idx === items.length - 1;
            
            html += `
                <button class="path-item ${isLast ? 'current' : ''}" onclick="openItemPanel(${item.id}, '${item.item_type}')">
                    <svg width="12" height="12" style="color:${item.color || iconData.color}"><use href="#icon-${item.icon || iconData.icon}"/></svg>
                    <span>${esc(item.name)}</span>
                </button>
            `;
            
            if (!isLast) {
                html += '<span class="path-sep">/</span>';
            }
        });
        
        html += '</div>';
        return html;
    }
    
    function renderChildrenList(children) {
        return children.map(child => {
            const iconData = iconMap[child.item_type] || { icon: 'file', color: '#94a3b8' };
            const icon = child.icon || iconData.icon;
            const color = child.color || iconData.color;
            
            return `
                <button class="panel-child-item" onclick="openItemPanel(${child.id}, '${child.item_type}')">
                    <svg width="16" height="16" style="color:${color}"><use href="#icon-${icon}"/></svg>
                    <span>${esc(child.name)}</span>
                </button>
            `;
        }).join('');
    }
    
    async function loadChatInPanel(chatId) {
        const container = document.getElementById('panelChat');
        if (!container) return;
        
        // TODO: Загрузить и отрендерить чат
        container.innerHTML = `
            <div class="panel-chat-placeholder">
                <a href="/chat/${chatId}" class="btn btn-primary btn-sm">Открыть чат</a>
            </div>
        `;
    }
    
    window.copyToClipboard = function(text) {
        navigator.clipboard.writeText(text).then(() => {
            // Можно показать уведомление
        });
    };
    
    function esc(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    // === СВЕРНУТЬ/РАЗВЕРНУТЬ ===
    window.toggleItem = function(itemId) {
        const item = document.querySelector(`[data-id="${itemId}"]`);
        if (!item) return;
        item.classList.toggle('collapsed');
        if (isOwner) {
            fetch('/api/user-folder/toggle-collapse', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
                body: JSON.stringify({ id: itemId })
            });
        }
    };

    // === МОДАЛКА СОЗДАНИЯ ===
    window.showCreateModal = function(parentId) {
        currentParentId = parentId;
        window.openModal('folderCreateModal');
    };

    window.selectCreateType = function(type) {
        window.closeModal('folderCreateModal');
        
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
        
        // Для сервера - специальная форма
        if (type === 'server') {
            showServerModal();
            return;
        }
        
        const titles = { folder: 'Новая папка', chat: 'Новый чат', mod: 'Новый мод', shortcut: 'Новый ярлык' };
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
    
    function showServerModal() {
        document.getElementById('serverFormParentId').value = currentParentId || '';
        document.getElementById('serverFormName').value = '';
        document.getElementById('serverFormIp').value = '';
        document.getElementById('serverFormDescription').value = '';
        
        setTimeout(() => {
            window.openModal('serverCreateModal');
            document.getElementById('serverFormName').focus();
        }, 250);
    }

    async function createItemWithData(type, name, data = {}) {
        const res = await fetch('/api/user-folder/create', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
            body: JSON.stringify({ type, name, parent_id: currentParentId, ...data })
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
    
    document.getElementById('serverCreateForm')?.addEventListener('submit', async function(e) {
        e.preventDefault();
        const fd = new FormData(this);
        await createItemWithData('server', fd.get('name'), {
            description: fd.get('description'),
            server_ip: fd.get('server_ip'),
            icon: 'server',
            color: '#f59e0b'
        });
    });

    // === МОДАЛКА НАСТРОЕК ===
    window.showSettingsModal = async function(itemId) {
        currentItemId = itemId;
        const res = await fetch(`/api/user-folder/item?id=${itemId}`);
        if (!res.ok) { alert('Ошибка загрузки'); return; }
        
        const { item } = await res.json();
        const iconData = iconMap[item.item_type] || { color: '#3b82f6' };
        
        document.getElementById('settingsModalTitle').textContent = 'Настройки: ' + item.name;
        document.getElementById('settingsFormId').value = item.id;
        document.getElementById('settingsFormName').value = item.name || '';
        document.getElementById('settingsFormDescription').value = item.description || '';
        document.getElementById('settingsFormColor').value = item.color || iconData.color;
        
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

    // === ПОДПИСКА ===
    window.toggleSubscription = async function(ownerId, subscribe) {
        const endpoint = subscribe ? '/api/user-folder/subscribe' : '/api/user-folder/unsubscribe';
        const res = await fetch(endpoint, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
            body: JSON.stringify({ user_id: ownerId })
        });
        if (res.ok) location.reload();
        else alert('Войдите чтобы подписаться');
    };


    // === DRAG AND DROP ===
	if (isOwner) {
		const structure = document.querySelector('.community-structure');
		if (!structure) return;

		// Delegation — один обработчик на весь контейнер
		structure.addEventListener('dragstart', handleDragStart, true);   // true = capture phase
		structure.addEventListener('dragend', handleDragEnd, true);
		structure.addEventListener('dragover', handleDragOver);
		structure.addEventListener('dragleave', handleDragLeave);
		structure.addEventListener('drop', handleDrop);

		// Для корневого уровня (drop на пустое пространство)
		structure.addEventListener('dragover', handleRootDragOver);
		structure.addEventListener('drop', handleRootDrop);
	}

	function getDropPosition(e, element) {
		const rect = element.getBoundingClientRect();
		const y = e.clientY - rect.top;
		const h = rect.height;
		const isEntity = element.dataset.isEntity === '1';

		// Увеличили центральную зону для папок (25%–75%) — теперь гораздо легче попасть
		if (isEntity && y > h * 0.25 && y < h * 0.75) {
			return 'inside';
		}
		return y < h * 0.5 ? 'before' : 'after';
	}

	function findPreviousSibling(element) {
		let sibling = element.previousElementSibling;
		while (sibling && !sibling.classList.contains('folder-item')) {
			sibling = sibling.previousElementSibling;
		}
		return sibling;
	}

	function handleDragStart(e) {
		const item = e.target.closest('.folder-item[draggable="true"]');
		if (!item) return;

		draggedElement = item;
		item.classList.add('dragging');

		// Отключаем потомков
		document.querySelectorAll('.folder-item').forEach(el => {
			if (isDescendantOf(el, item)) {
				el.classList.add('drag-disabled');
				el.draggable = false;
			}
		});

		e.dataTransfer.effectAllowed = 'move';
	}

	function handleDragEnd(e) {
		if (draggedElement) {
			draggedElement.classList.remove('dragging');
		}

		document.querySelectorAll('.folder-item').forEach(item => {
			item.classList.remove('dragging', 'drag-over', 'drop-before', 'drop-inside', 'drop-after', 'drag-disabled');
			item.draggable = true;
		});

		draggedElement = null;
	}

	function handleDragOver(e) {
		const item = e.target.closest('.folder-item');
		if (!item || !draggedElement || draggedElement === item || item.classList.contains('drag-disabled')) {
			return;
		}

		e.preventDefault();

		const position = getDropPosition(e, item);

		item.classList.remove('drop-before', 'drop-inside', 'drop-after', 'drag-over');
		item.classList.add(`drop-${position}`, 'drag-over');
	}
	
	function handleDragLeave(e) {
		const item = e.target.closest('.folder-item');
		if (item) {
			item.classList.remove('drag-over', 'drop-before', 'drop-inside', 'drop-after');
		}
	}
	
	async function handleDrop(e) {
		const item = e.target.closest('.folder-item');
		if (!item || !draggedElement || draggedElement === item || item.classList.contains('drag-disabled')) {
			return;
		}

		e.preventDefault();
		e.stopPropagation();

		const itemId = draggedElement.dataset.id;
		const targetId = item.dataset.id;
		const targetIsEntity = item.dataset.isEntity === '1';

		const position = getDropPosition(e, item);   // используем новую функцию

		let parentId = null;
		let afterId = null;

		if (position === 'inside' && targetIsEntity) {
			parentId = parseInt(targetId, 10);     // ← обязательно число
			afterId = null;                        // в конец папки
		} else {
			// Вставка перед/после на том же уровне
			parentId = (item.dataset.parent === 'root' || !item.dataset.parent)
				? null 
				: parseInt(item.dataset.parent, 10);

			if (position === 'before') {
				const prev = findPreviousSibling(item);
				afterId = prev ? parseInt(prev.dataset.id, 10) : null;
			} else {
				afterId = parseInt(targetId, 10);
			}
		}

		console.log('Drop debug:', { 
			position, 
			parentId, 
			afterId, 
			targetId, 
			targetIsEntity 
		});

		try {
			const res = await fetch('/api/user-folder/move', {
				method: 'POST',
				headers: { 
					'Content-Type': 'application/json', 
					'X-CSRF-Token': csrf 
				},
				body: JSON.stringify({ 
					item_id: parseInt(itemId, 10), 
					parent_id: parentId, 
					after_id: afterId 
				})
			});

			if (res.ok) {
				location.reload();
			} else {
				const data = await res.json();
				alert(data.error || 'Ошибка перемещения');
			}
		} catch (err) {
			console.error(err);
			alert('Сетевая ошибка');
		}
	}

	function handleRootDragOver(e) {
		if (!draggedElement || e.target.closest('.folder-item')) return;
		e.preventDefault();
		// Можно добавить класс на .community-structure для визуальной подсветки корня
	}

	async function handleRootDrop(e) {
		if (!draggedElement || e.target.closest('.folder-item')) return;
		e.preventDefault();

		try {
			const res = await fetch('/api/user-folder/move', {
				method: 'POST',
				headers: { 
					'Content-Type': 'application/json', 
					'X-CSRF-Token': csrf 
				},
				body: JSON.stringify({ 
					item_id: draggedElement.dataset.id, 
					parent_id: null, 
					after_id: null // в конец корневого уровня
				})
			});

			if (res.ok) {
				location.reload();
			} else {
				const data = await res.json();
				alert(data.error || 'Ошибка перемещения в корень');
			}
		} catch (err) {
			alert('Ошибка сети');
		}
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
