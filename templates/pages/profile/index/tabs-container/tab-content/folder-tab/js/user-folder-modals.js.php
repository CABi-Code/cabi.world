<script>

// Slug prefixes map
const slugPrefixes = <?= json_encode(\App\Repository\UserFolderRepository::SLUG_PREFIXES) ?>;

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

// === МОДАЛКА НАСТРОЕК (единая) ===
let currentSettingsItem = null;

window.showSettingsModal = async function(itemId) {
	currentItemId = itemId;
	const res = await fetch(`/api/user-folder/item?id=${itemId}`);
	if (!res.ok) { alert('Ошибка загрузки'); return; }

	const { item } = await res.json();
	currentSettingsItem = item;
	const iconData = iconMap[item.item_type] || { color: '#3b82f6' };
	const typePrefix = slugPrefixes[item.item_type] || 'string-';

	document.getElementById('settingsModalTitle').textContent = 'Настройки: ' + item.name;
	document.getElementById('settingsFormId').value = item.id;
	document.getElementById('settingsFormName').value = item.name || '';
	document.getElementById('settingsFormDescription').value = item.description || '';
	document.getElementById('settingsFormColor').value = item.color || iconData.color;

	// Slug
	const slugPrefix = document.getElementById('settingsSlugPrefix');
	const slugInput = document.getElementById('settingsSlugInput');
	const slugPreview = document.getElementById('settingsSlugPreview');
	if (slugPrefix) slugPrefix.textContent = typePrefix;
	if (slugInput) slugInput.value = item.slug || '';
	if (slugPreview) slugPreview.textContent = '/item/' + typePrefix + (item.slug || '');

	// Hide toggle
	const hiddenGroup = document.getElementById('settingsHiddenGroup');
	const hiddenCheckbox = document.getElementById('settingsFormHidden');
	if (hiddenGroup) hiddenGroup.style.display = '';
	if (hiddenCheckbox) hiddenCheckbox.checked = !!item.is_hidden;

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

    // Slug input live preview
    document.getElementById('settingsSlugInput')?.addEventListener('input', function() {
        const val = this.value.replace(/[^a-zA-Z0-9_-]/g, '');
        this.value = val;
        const prefix = document.getElementById('settingsSlugPrefix')?.textContent || '';
        const preview = document.getElementById('settingsSlugPreview');
        if (preview) preview.textContent = '/item/' + prefix + val;
        const error = document.getElementById('settingsSlugError');
        if (error) error.style.display = 'none';
    });

    // Единая форма настроек
    document.getElementById('folderSettingsForm')?.addEventListener('submit', async function(e) {
        e.preventDefault();
        const fd = new FormData(this);
        const itemId = fd.get('id');
        const slugInput = document.getElementById('settingsSlugInput');
        const slugError = document.getElementById('settingsSlugError');
        const hiddenCheckbox = document.getElementById('settingsFormHidden');

        try {
            // Сохраняем основные данные
            const updateData = {
                id: itemId,
                name: fd.get('name'),
                description: fd.get('description'),
                color: fd.get('color'),
            };
            if (hiddenCheckbox) {
                updateData.is_hidden = hiddenCheckbox.checked ? 1 : 0;
            }

            const res = await fetch('/api/user-folder/update', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
                body: JSON.stringify(updateData)
            });

            if (!res.ok) {
                const err = await res.json();
                alert(err.error || 'Ошибка');
                return;
            }

            // Сохраняем slug если он изменился
            const newSlug = (slugInput?.value || '').trim();
            const originalSlug = currentSettingsItem?.slug || '';
            if (newSlug && newSlug !== originalSlug) {
                const slugRes = await fetch('/api/user-folder/update-slug', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
                    body: JSON.stringify({ id: itemId, slug: newSlug })
                });
                if (!slugRes.ok) {
                    const slugResult = await slugRes.json();
                    if (slugError) {
                        slugError.textContent = slugResult.error || 'Ошибка';
                        slugError.style.display = 'block';
                    }
                    return;
                }
            }

            location.reload();
        } catch (err) {
            alert('Ошибка сети');
        }
    });
}

</script>
