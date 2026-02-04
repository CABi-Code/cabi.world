<script>

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


// Подключение слушателей форм
function initModals() {
    document.getElementById('folderCreateBtn')?.addEventListener('click', () => window.showCreateModal(null));
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
}

</script>