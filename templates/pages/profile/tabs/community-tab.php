<?php
/**
 * Вкладка "Моё сообщество" в профиле
 * 
 * @var array $profileUser
 * @var array|null $user
 * @var bool $isOwner
 * @var array|null $community
 * @var bool $hasCommunity
 * @var bool $communityIsEmpty
 * @var bool $isSubscribed
 * @var CommunityRepository $communityRepo
 */

// Получаем структуру сообщества если оно есть
$structure = [];
if ($hasCommunity) {
    $structure = $communityRepo->getStructure($community['id']);
}
?>

<?php if ($isOwner): ?>
    <!-- Владелец видит кнопку создания или структуру -->
    <?php if (!$hasCommunity || $communityIsEmpty): ?>
        <!-- Пустое сообщество - показываем кнопку создания -->
        <div class="community-empty-owner">
            <button class="community-create-btn" id="communityCreateBtn" data-community-id="<?= $community['id'] ?? '' ?>">
                <svg width="32" height="32"><use href="#icon-plus"/></svg>
            </button>
            <p class="community-create-hint">Нажмите, чтобы создать чат или папку</p>
        </div>
        
        <?php if ($hasCommunity): ?>
            <div class="community-settings-link">
                <a href="#" class="btn btn-ghost btn-sm" onclick="openCommunitySettings(<?= $community['id'] ?>)">
                    <svg width="14" height="14"><use href="#icon-settings"/></svg>
                    Настройки сообщества
                </a>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <!-- Есть контент - показываем структуру -->
        <div class="community-structure" data-community-id="<?= $community['id'] ?>">
            <?php include __DIR__ . '/community-structure.php'; ?>
        </div>
        
        <!-- Кнопка добавления в корень -->
        <div class="community-add-root">
            <button class="btn btn-ghost btn-sm" onclick="showCreateModal(<?= $community['id'] ?>, null)">
                <svg width="14" height="14"><use href="#icon-plus"/></svg>
                Добавить
            </button>
        </div>
        
        <div class="community-settings-link">
            <a href="#" class="btn btn-ghost btn-sm" onclick="openCommunitySettings(<?= $community['id'] ?>)">
                <svg width="14" height="14"><use href="#icon-settings"/></svg>
                Настройки сообщества
            </a>
        </div>
    <?php endif; ?>
    
<?php else: ?>
    <!-- Гость или другой пользователь -->
    <?php if ($hasCommunity && !$communityIsEmpty): ?>
        <!-- Кнопка подписки -->
        <?php if ($user): ?>
            <div class="community-subscribe-wrap">
                <?php if ($isSubscribed): ?>
                    <button class="btn btn-secondary btn-sm" onclick="toggleSubscription(<?= $community['id'] ?>, false)">
                        <svg width="14" height="14"><use href="#icon-check"/></svg>
                        Вы подписаны
                    </button>
                <?php else: ?>
                    <button class="btn btn-primary btn-sm" onclick="toggleSubscription(<?= $community['id'] ?>, true)">
                        <svg width="14" height="14"><use href="#icon-plus"/></svg>
                        Подписаться
                    </button>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <!-- Структура сообщества для просмотра -->
        <div class="community-structure" data-community-id="<?= $community['id'] ?>">
            <?php include __DIR__ . '/community-structure.php'; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <svg width="48" height="48" class="empty-icon"><use href="#icon-message-circle"/></svg>
            <p>Сообщество пусто</p>
        </div>
    <?php endif; ?>
<?php endif; ?>

<!-- Модальное окно создания чата/папки -->
<div class="modal" id="communityCreateModal" style="display:none;">
    <div class="modal-backdrop" data-close></div>
    <div class="modal-content modal-sm">
        <div class="modal-header">
            <h3>Создать</h3>
            <button class="modal-close" data-close>&times;</button>
        </div>
        <div class="modal-body">
            <div class="create-options">
                <button class="create-option" onclick="createCommunityItem('chat')">
                    <svg width="24" height="24"><use href="#icon-message-circle"/></svg>
                    <span>Создать чат</span>
                    <p>Общайтесь с подписчиками</p>
                </button>
                <button class="create-option" onclick="createCommunityItem('folder')">
                    <svg width="24" height="24"><use href="#icon-folder"/></svg>
                    <span>Создать папку</span>
                    <p>Группируйте чаты</p>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно ввода имени -->
<div class="modal" id="communityNameModal" style="display:none;">
    <div class="modal-backdrop" data-close></div>
    <div class="modal-content modal-sm">
        <div class="modal-header">
            <h3 id="nameModalTitle">Название</h3>
            <button class="modal-close" data-close>&times;</button>
        </div>
        <div class="modal-body">
            <form id="communityNameForm">
                <input type="hidden" name="community_id" id="nameFormCommunityId">
                <input type="hidden" name="parent_id" id="nameFormParentId">
                <input type="hidden" name="type" id="nameFormType">
                
                <div class="form-group">
                    <label class="form-label">Название</label>
                    <input type="text" name="name" class="form-input" id="nameFormInput" maxlength="100" required>
                </div>
                
                <div class="form-group" id="descriptionGroup" style="display:none;">
                    <label class="form-label">Описание (необязательно)</label>
                    <textarea name="description" class="form-input" rows="2" id="nameFormDescription"></textarea>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-ghost" data-close>Отмена</button>
                    <button type="submit" class="btn btn-primary">Создать</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Модальное окно настроек сообщества -->
<div class="modal" id="communitySettingsModal" style="display:none;">
    <div class="modal-backdrop" data-close></div>
    <div class="modal-content">
        <div class="modal-header">
            <h3>Настройки сообщества</h3>
            <button class="modal-close" data-close>&times;</button>
        </div>
        <div class="modal-body">
            <form id="communitySettingsForm">
                <input type="hidden" name="community_id" id="settingsCommunityId">
                
                <div class="settings-section">
                    <h4>Общие настройки (по умолчанию)</h4>
                    
                    <div class="form-group">
                        <label class="form-label">Тайм-аут на сообщения (секунды)</label>
                        <input type="number" name="message_timeout" class="form-input" id="settingsTimeout" min="0" placeholder="0 = без ограничений">
                        <p class="form-hint">Минимальное время между сообщениями одного пользователя</p>
                    </div>
                    
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="files_disabled" id="settingsFilesDisabled">
                            <span>Отключить отправку файлов</span>
                        </label>
                    </div>
                    
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="messages_disabled" id="settingsMessagesDisabled">
                            <span>Отключить отправку сообщений</span>
                        </label>
                    </div>
                </div>
                
                <div class="settings-section danger-zone">
                    <h4>Опасная зона</h4>
                    <button type="button" class="btn btn-danger btn-sm" onclick="deleteCommunity()">
                        <svg width="14" height="14"><use href="#icon-trash"/></svg>
                        Удалить сообщество
                    </button>
                    <p class="form-hint">Это действие нельзя отменить. Все чаты и сообщения будут удалены.</p>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-ghost" data-close>Отмена</button>
                    <button type="submit" class="btn btn-primary">Сохранить</button>
                </div>
            </form>
        </div>
    </div>
</div>

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
    document.getElementById('nameFormInput').focus();
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

// Закрытие модалок
document.querySelectorAll('.modal [data-close]').forEach(el => {
    el.addEventListener('click', function() {
        this.closest('.modal').style.display = 'none';
    });
});
</script>
