<?

// присоеденен в файле pages/profile/tabs/community-tab.php через include

?>


<script>
const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
let currentCommunityId = <?= $community['id'] ?? 'null' ?>;
let currentParentId = null;
let currentItemType = null;

// Открыть модалку выбора типа
function showCreateModal(communityId, parentId = null) {
    currentCommunityId = communityId;
    currentParentId = parentId;
    document.getElementById('communityCreateModal').style.display = 'flex';
	lockBodyScroll();
	setTimeout(() => document.getElementById('communityCreateModal').classList.add('show'), 10);
}

// Создать сообщество если его нет
document.getElementById('communityCreateBtn')?.addEventListener('click', async function() {
    if (!currentCommunityId) {
        // Создаём сообщество
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
    document.getElementById('communityCreateModal').style.display = 'none';
    
    document.getElementById('nameModalTitle').textContent = type === 'chat' ? 'Новый чат' : 'Новая папка';
    document.getElementById('nameFormCommunityId').value = currentCommunityId;
    document.getElementById('nameFormParentId').value = currentParentId || '';
    document.getElementById('nameFormType').value = type;
    document.getElementById('nameFormInput').value = '';
    document.getElementById('nameFormDescription').value = '';
    document.getElementById('descriptionGroup').style.display = type === 'chat' ? 'block' : 'none';
    
    document.getElementById('communityNameModal').style.display = 'flex';
	lockBodyScroll();
	document.querySelectorAll('.form-error').forEach(el => el.textContent = '');
	setTimeout(() => document.getElementById('communityNameModal').classList.add('show'), 10);
    document.getElementById('nameFormInput').scrollIntoView({ behavior: 'smooth', block: 'center' });
}

// Отправка формы создания
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

// Настройки сообщества
function openCommunitySettings(communityId) {
    document.getElementById('settingsCommunityId').value = communityId;
    // TODO: Загрузить текущие настройки
    document.getElementById('communitySettingsModal').style.display = 'flex';
	lockBodyScroll();
	setTimeout(() => document.getElementById('communitySettingsModal').classList.add('show'), 10);
}

// Удалить сообщество
async function deleteCommunity() {
    if (!confirm('Вы уверены? Это действие нельзя отменить. Все чаты и сообщения будут удалены.')) return;
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

// Сохранить настройки
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
        document.getElementById('communitySettingsModal').style.display = 'none';
    } else {
        const data = await res.json();
        alert(data.error || 'Ошибка');
    }
});

// Подписка/отписка
async function toggleSubscription(communityId, subscribe) {
    const endpoint = subscribe ? '/api/community/subscribe' : '/api/community/unsubscribe';
    const res = await fetch(endpoint, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
        body: JSON.stringify({ community_id: communityId })
    });
    
    if (res.ok) {
        location.reload();
    }
}

function showodal(el) {
    el.addEventListener('click', function() {
        const modal = this.closest('.modal');
        modal.classList.remove('show');
        setTimeout(() => {
			unlockBodyScroll();
            modal.style.display = 'none';
        }, 150);
    });
}

// Закрытие модалок
document.querySelectorAll('.modal [data-close]').forEach(el => {
    showodal(el);
});

// Закрытие по клику на фон
document.querySelectorAll('.modal-backdrop').forEach(el => {
    showodal(el);
});


// Вычисляем ширину скроллбара один раз (чтобы компенсировать прыжок)
function getScrollbarWidth() {
  const outer = document.createElement('div');
  outer.style.visibility = 'hidden';
  outer.style.overflow = 'scroll';
  document.body.appendChild(outer);
  const innerWidth = outer.clientWidth;
  document.body.removeChild(outer);
  return outer.offsetWidth - innerWidth + 'px';
}

// Устанавливаем переменную при загрузке страницы
document.documentElement.style.setProperty('--scrollbar-width', getScrollbarWidth());

// Функции блокировки / разблокировки скролла
function lockBodyScroll() {
  document.body.classList.add('no-scroll');
  document.documentElement.classList.add('no-scroll');
}

function unlockBodyScroll() {
  document.body.classList.remove('no-scroll');
  document.documentElement.classList.remove('no-scroll');
}

</script>