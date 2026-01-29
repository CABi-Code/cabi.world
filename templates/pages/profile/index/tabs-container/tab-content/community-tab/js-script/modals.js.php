// Открыть модалку выбора типа
function showCreateModal(communityId, parentId = null) {
    currentCommunityId = communityId;
    currentParentId = parentId;
    openModal('communityCreateModal');
}

// Создать сообщество если его нет
document.getElementById('communityCreateBtn')?.addEventListener('click', async function() {
    if (!currentCommunityId) {
        const res = await fetch('/api/community/create', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf }
        });
        const data = await res.json();
        if (data.id) {
            currentCommunityId = data.id;
            this.dataset.communityId = data.id;
        }
    }
    showCreateModal(currentCommunityId, null);
});

// Выбрать тип создаваемого элемента
function createCommunityItem(type) {
    if (document.body.classList.contains('creating')) return;
    document.body.classList.add('creating');
    setTimeout(() => document.body.classList.remove('creating'), 1500);

    currentItemType = type;
    closeModal('communityCreateModal');
    
    document.getElementById('nameModalTitle').textContent = type === 'chat' ? 'Новый чат' : 'Новая папка';
    document.getElementById('nameFormCommunityId').value = currentCommunityId;
    document.getElementById('nameFormParentId').value = currentParentId || '';
    document.getElementById('nameFormType').value = type;
    document.getElementById('nameFormInput').value = '';
    document.getElementById('nameFormDescription').value = '';
    document.getElementById('descriptionGroup').style.display = type === 'chat' ? 'block' : 'none';
    document.querySelectorAll('.form-error').forEach(el => el.textContent = '');
    
    setTimeout(() => openModal('communityNameModal'), 200);
}

// Настройки сообщества
function openCommunitySettings(communityId) {
    document.getElementById('settingsCommunityId').value = communityId;
    openModal('communitySettingsModal');
}

// Удалить сообщество
async function deleteCommunity() {
    if (!confirm('Вы уверены? Это действие нельзя отменить.')) return;
    if (!confirm('Точно удалить? Последний шанс отменить.')) return;
    
    const communityId = document.getElementById('settingsCommunityId').value;
    const res = await fetch('/api/community/delete', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
        body: JSON.stringify({ id: communityId })
    });
    
    if (res.ok) {
        location.reload();
    } else {
        const data = await res.json();
        alert(data.error || 'Ошибка');
    }
}
