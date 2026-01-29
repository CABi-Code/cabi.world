// Отправка формы создания чата/папки
document.getElementById('communityNameForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const type = formData.get('type');
    const endpoint = type === 'chat' ? '/api/community/chat/create' : '/api/community/folder/create';
    
    const res = await fetch(endpoint, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
        body: JSON.stringify({
            community_id: formData.get('community_id'),
            parent_id: formData.get('parent_id') || null,
            folder_id: formData.get('parent_id') || null,
            name: formData.get('name'),
            description: formData.get('description')
        })
    });
    
    if (res.ok) {
        location.reload();
    } else {
        const data = await res.json();
        alert(data.error || 'Ошибка');
    }
});

// Сохранить настройки сообщества
document.getElementById('communitySettingsForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const res = await fetch('/api/community/update', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
        body: JSON.stringify({
            id: formData.get('community_id'),
            message_timeout: formData.get('message_timeout') || null,
            files_disabled: formData.get('files_disabled') ? 1 : 0,
            messages_disabled: formData.get('messages_disabled') ? 1 : 0
        })
    });
    
    if (res.ok) {
        closeModal('communitySettingsModal');
    } else {
        const data = await res.json();
        alert(data.error || 'Ошибка');
    }
});
