<?php
/**
 * Страница чата сообщества
 * 
 * @var array $chat - данные чата
 * @var array $community - данные сообщества
 * @var array $owner - владелец сообщества
 * @var array|null $user - текущий пользователь
 * @var bool $isOwner - является ли текущий пользователь владельцем
 * @var bool $isModerator - является ли текущий пользователь модератором
 * @var bool $isBanned - забанен ли пользователь
 * @var array $settings - эффективные настройки чата
 */

use App\Core\Role;

$canSendMessages = $user && !$isBanned && !$settings['messages_disabled'];
$canSendFiles = $canSendMessages && !$settings['files_disabled'] && Role::isPremium($user['role'] ?? null);
?>

<div class="chat-page" data-chat-id="<?= $chat['id'] ?>">
    <!-- Заголовок -->
    <div class="chat-header">
        <a href="/@<?= e($owner['login']) ?>?tab=community" class="chat-back">
            <svg width="20" height="20"><use href="#icon-arrow-left"/></svg>
        </a>
        
        <div class="chat-info">
            <h1 class="chat-title"><?= e($chat['name']) ?></h1>
            <span class="chat-subtitle">
                <a href="/@<?= e($owner['login']) ?>"><?= e($owner['username']) ?></a>
                <?php if ($chat['description']): ?>
                    · <?= e($chat['description']) ?>
                <?php endif; ?>
            </span>
        </div>
        
        <?php if ($isOwner || $isModerator): ?>
        <div class="chat-actions-header">
            <button class="btn btn-ghost btn-sm btn-icon" onclick="openChatSettings()" title="Настройки">
                <svg width="18" height="18"><use href="#icon-settings"/></svg>
            </button>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Сообщения -->
    <div class="chat-messages-container" id="chatMessagesContainer">
        <div class="chat-messages" id="chatMessages">
            <div class="chat-messages-loading">Загрузка сообщений...</div>
        </div>
    </div>
    
    <!-- Форма отправки -->
    <?php if ($isBanned): ?>
        <div class="chat-input-container">
            <div class="chat-banned-notice">
                <svg width="16" height="16"><use href="#icon-ban"/></svg>
                Вы заблокированы в этом чате
            </div>
        </div>
    <?php elseif ($settings['messages_disabled']): ?>
        <div class="chat-input-container">
            <div class="chat-disabled-notice">
                <svg width="16" height="16"><use href="#icon-message-circle-off"/></svg>
                Отправка сообщений отключена
            </div>
        </div>
    <?php elseif (!$user): ?>
        <div class="chat-input-container">
            <div class="chat-disabled-notice">
                <a href="/login">Войдите</a>, чтобы отправлять сообщения
            </div>
        </div>
    <?php else: ?>
        <div class="chat-input-container">
            <div id="chatTimeoutNotice" class="chat-timeout-notice" style="display:none;"></div>
            
            <form id="chatMessageForm">
                <div class="chat-input-wrapper">
                    <div class="chat-input-field">
                        <textarea 
                            id="chatInput" 
                            class="chat-input" 
                            placeholder="Написать сообщение..." 
                            rows="1"
                            maxlength="2000"
                        ></textarea>
                        
                        <div id="chatImagePreview" class="chat-image-preview" style="display:none;"></div>
                        
                        <div class="chat-input-toolbar">
                            <?php if ($canSendFiles): ?>
                            <button type="button" class="chat-input-btn" id="chatAttachBtn" title="Прикрепить фото">
                                <svg width="18" height="18"><use href="#icon-image"/></svg>
                            </button>
                            <input type="file" id="chatImageInput" accept="image/*" multiple hidden>
                            <?php endif; ?>
                            
                            <button type="button" class="chat-input-btn" id="chatPollBtn" title="Создать опрос">
                                <svg width="18" height="18"><use href="#icon-bar-chart"/></svg>
                            </button>
                        </div>
                    </div>
                    
                    <button type="submit" class="chat-send-btn" id="chatSendBtn">
                        <svg width="20" height="20"><use href="#icon-send"/></svg>
                    </button>
                </div>
            </form>
        </div>
    <?php endif; ?>
</div>

<!-- Модальное окно создания опроса -->
<div class="modal" id="chatPollModal" style="display:none;">
    <div class="modal-backdrop" data-close></div>
    <div class="modal-content">
        <div class="modal-header">
            <h3>Создать опрос</h3>
            <button class="modal-close" data-close>&times;</button>
        </div>
        <div class="modal-body">
            <form id="chatPollForm">
                <div class="form-group">
                    <label class="form-label">Вопрос</label>
                    <input type="text" name="question" class="form-input" maxlength="500" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Варианты ответа</label>
                    <div id="pollOptions">
                        <div class="poll-option-row">
                            <input type="text" name="options[]" class="form-input" placeholder="Вариант 1" maxlength="200" required>
                        </div>
                        <div class="poll-option-row">
                            <input type="text" name="options[]" class="form-input" placeholder="Вариант 2" maxlength="200" required>
                        </div>
                    </div>
                    <button type="button" class="btn btn-ghost btn-sm" id="addPollOption" style="margin-top:0.5rem;">
                        <svg width="14" height="14"><use href="#icon-plus"/></svg>
                        Добавить вариант
                    </button>
                </div>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_multiple">
                        <span>Множественный выбор</span>
                    </label>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-ghost" data-close>Отмена</button>
                    <button type="submit" class="btn btn-primary">Создать опрос</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Модальное окно настроек чата (для владельца/модератора) -->
<?php if ($isOwner || $isModerator): ?>
<div class="modal" id="chatSettingsModal" style="display:none;">
    <div class="modal-backdrop" data-close></div>
    <div class="modal-content">
        <div class="modal-header">
            <h3>Настройки чата</h3>
            <button class="modal-close" data-close>&times;</button>
        </div>
        <div class="modal-body">
            <form id="chatSettingsForm">
                <div class="form-group">
                    <label class="form-label">Название</label>
                    <input type="text" name="name" class="form-input" value="<?= e($chat['name']) ?>" maxlength="100">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Описание</label>
                    <textarea name="description" class="form-input" rows="2"><?= e($chat['description'] ?? '') ?></textarea>
                </div>
                
                <div class="settings-section">
                    <h4>Ограничения</h4>
                    
                    <div class="form-group">
                        <label class="form-label">Тайм-аут на сообщения (секунды)</label>
                        <input type="number" name="message_timeout" class="form-input" 
                               value="<?= $chat['message_timeout'] ?? '' ?>" min="0" 
                               placeholder="Использовать общие настройки">
                    </div>
                    
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="files_disabled" <?= $chat['files_disabled'] ? 'checked' : '' ?>>
                            <span>Отключить отправку файлов</span>
                        </label>
                    </div>
                    
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="messages_disabled" <?= $chat['messages_disabled'] ? 'checked' : '' ?>>
                            <span>Отключить отправку сообщений</span>
                        </label>
                    </div>
                </div>
                
                <?php if ($isOwner): ?>
                <div class="settings-section">
                    <h4>Модераторы чата</h4>
                    <div id="chatModerators">
                        <!-- Загружается через JS -->
                    </div>
                    <button type="button" class="btn btn-ghost btn-sm" onclick="showAddModerator('chat')">
                        <svg width="14" height="14"><use href="#icon-plus"/></svg>
                        Добавить модератора
                    </button>
                </div>
                
                <div class="settings-section danger-zone">
                    <h4>Опасная зона</h4>
                    <button type="button" class="btn btn-danger btn-sm" onclick="deleteChat()">
                        <svg width="14" height="14"><use href="#icon-trash"/></svg>
                        Удалить чат
                    </button>
                </div>
                <?php endif; ?>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-ghost" data-close>Отмена</button>
                    <button type="submit" class="btn btn-primary">Сохранить</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
const chatId = <?= $chat['id'] ?>;
const communityId = <?= $community['id'] ?>;
const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
const currentUserId = <?= $user['id'] ?? 'null' ?>;
const canSendMessages = <?= $canSendMessages ? 'true' : 'false' ?>;
const canSendFiles = <?= $canSendFiles ? 'true' : 'false' ?>;
const messageTimeout = <?= $settings['message_timeout'] ?? 0 ?>;

let lastMessageId = 0;
let selectedImages = [];
let pollingInterval = null;

// Загрузка сообщений
async function loadMessages(beforeId = null) {
    const url = `/api/chat/messages?chat_id=${chatId}` + (beforeId ? `&before_id=${beforeId}` : '');
    const res = await fetch(url);
    const data = await res.json();
    
    if (data.messages) {
        renderMessages(data.messages, beforeId !== null);
        if (data.messages.length > 0) {
            lastMessageId = Math.max(...data.messages.map(m => m.id));
        }
    }
}

// Рендер сообщений
function renderMessages(messages, prepend = false) {
    const container = document.getElementById('chatMessages');
    
    if (!prepend) {
        container.innerHTML = '';
    }
    
    messages.forEach(msg => {
        const html = createMessageHtml(msg);
        if (prepend) {
            container.insertAdjacentHTML('afterbegin', html);
        } else {
            container.insertAdjacentHTML('beforeend', html);
        }
    });
    
    if (!prepend) {
        scrollToBottom();
    }
}

// Создание HTML сообщения
function createMessageHtml(msg) {
    const avatarColors = (msg.avatar_bg_value || '#3b82f6,#8b5cf6').split(',');
    const avatarStyle = msg.avatar ? '' : `background:linear-gradient(135deg,${avatarColors[0]},${avatarColors[1] || avatarColors[0]})`;
    const avatarContent = msg.avatar 
        ? `<img src="${escapeHtml(msg.avatar)}" alt="">` 
        : escapeHtml(msg.username.charAt(0).toUpperCase());
    
    let imagesHtml = '';
    if (msg.images && msg.images.length > 0) {
        imagesHtml = '<div class="chat-message-images">' + 
            msg.images.map(img => `<img src="${escapeHtml(img.image_path)}" class="chat-message-image" data-lightbox>`).join('') +
            '</div>';
    }
    
    let pollHtml = '';
    if (msg.is_poll && msg.poll) {
        pollHtml = createPollHtml(msg.poll);
    }
    
    const isOwn = msg.user_id == currentUserId;
    const actionsHtml = isOwn ? `
        <div class="chat-message-actions">
            <button class="btn btn-ghost btn-icon btn-xs" onclick="deleteMessage(${msg.id})" title="Удалить">
                <svg width="12" height="12"><use href="#icon-trash"/></svg>
            </button>
        </div>
    ` : '';
    
    return `
        <div class="chat-message" data-message-id="${msg.id}">
            <div class="chat-message-avatar" style="${avatarStyle}">${avatarContent}</div>
            <div class="chat-message-content">
                <div class="chat-message-header">
                    <a href="/@${escapeHtml(msg.login)}" class="chat-message-author">${escapeHtml(msg.username)}</a>
                    <span class="chat-message-time">${formatTime(msg.created_at)}</span>
                </div>
                ${msg.message ? `<div class="chat-message-text">${escapeHtml(msg.message)}</div>` : ''}
                ${imagesHtml}
                ${pollHtml}
                <div class="chat-message-footer">
                    <button class="chat-message-like ${msg.liked ? 'liked' : ''}" onclick="toggleLike(${msg.id})">
                        <svg width="14" height="14"><use href="#icon-heart"/></svg>
                        <span>${msg.likes_count || ''}</span>
                    </button>
                    ${actionsHtml}
                </div>
            </div>
        </div>
    `;
}

// Создание HTML опроса
function createPollHtml(poll) {
    const totalVotes = poll.options.reduce((sum, opt) => sum + opt.votes_count, 0);
    
    const optionsHtml = poll.options.map(opt => {
        const percent = totalVotes > 0 ? Math.round(opt.votes_count / totalVotes * 100) : 0;
        const voted = opt.user_voted ? 'voted' : '';
        return `
            <div class="chat-poll-option ${voted}" onclick="vote(${opt.id})" data-option-id="${opt.id}">
                <div class="chat-poll-bar" style="width:${percent}%"></div>
                <div class="chat-poll-checkbox"></div>
                <span class="chat-poll-text">${escapeHtml(opt.option_text)}</span>
                <span class="chat-poll-count">${opt.votes_count}</span>
            </div>
        `;
    }).join('');
    
    return `
        <div class="chat-poll" data-poll-id="${poll.id}">
            <div class="chat-poll-question">${escapeHtml(poll.question)}</div>
            <div class="chat-poll-options">${optionsHtml}</div>
        </div>
    `;
}

// Отправка сообщения
document.getElementById('chatMessageForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const input = document.getElementById('chatInput');
    const message = input.value.trim();
    
    if (!message && selectedImages.length === 0) return;
    
    const formData = new FormData();
    formData.append('chat_id', chatId);
    formData.append('message', message);
    
    selectedImages.forEach((file, i) => {
        formData.append('images[]', file);
    });
    
    const res = await fetch('/api/chat/send', {
        method: 'POST',
        headers: { 'X-CSRF-Token': csrf },
        body: formData
    });
    
    const data = await res.json();
    
    if (data.error) {
        alert(data.error);
        return;
    }
    
    input.value = '';
    selectedImages = [];
    document.getElementById('chatImagePreview').style.display = 'none';
    document.getElementById('chatImagePreview').innerHTML = '';
    
    // Подгружаем новые сообщения
    loadNewMessages();
});

// Подгрузка новых сообщений
async function loadNewMessages() {
    if (lastMessageId === 0) return;
    
    const res = await fetch(`/api/chat/messages/new?chat_id=${chatId}&after_id=${lastMessageId}`);
    const data = await res.json();
    
    if (data.messages && data.messages.length > 0) {
        const container = document.getElementById('chatMessages');
        data.messages.forEach(msg => {
            const html = createMessageHtml(msg);
            container.insertAdjacentHTML('beforeend', html);
        });
        lastMessageId = Math.max(...data.messages.map(m => m.id));
        scrollToBottom();
    }
}

// Лайк
async function toggleLike(messageId) {
    const res = await fetch('/api/chat/like', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
        body: JSON.stringify({ message_id: messageId })
    });
    const data = await res.json();
    
    const btn = document.querySelector(`.chat-message[data-message-id="${messageId}"] .chat-message-like`);
    if (btn) {
        btn.classList.toggle('liked', data.liked);
        const count = btn.querySelector('span');
        const current = parseInt(count.textContent) || 0;
        count.textContent = data.liked ? current + 1 : Math.max(0, current - 1);
    }
}

// Голосование
async function vote(optionId) {
    const res = await fetch('/api/chat/poll/vote', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
        body: JSON.stringify({ option_id: optionId })
    });
    
    if (res.ok) {
        loadNewMessages(); // Перезагружаем для обновления опроса
    }
}

// Удаление сообщения
async function deleteMessage(messageId) {
    if (!confirm('Удалить сообщение?')) return;
    
    const res = await fetch('/api/chat/message/delete', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
        body: JSON.stringify({ message_id: messageId })
    });
    
    if (res.ok) {
        document.querySelector(`.chat-message[data-message-id="${messageId}"]`)?.remove();
    }
}

// Прикрепление изображений
document.getElementById('chatAttachBtn')?.addEventListener('click', () => {
    document.getElementById('chatImageInput').click();
});

document.getElementById('chatImageInput')?.addEventListener('change', function() {
    const files = Array.from(this.files);
    if (selectedImages.length + files.length > 4) {
        alert('Максимум 4 изображения');
        return;
    }
    
    selectedImages.push(...files);
    updateImagePreview();
});

function updateImagePreview() {
    const preview = document.getElementById('chatImagePreview');
    preview.innerHTML = '';
    preview.style.display = selectedImages.length > 0 ? 'flex' : 'none';
    
    selectedImages.forEach((file, i) => {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.insertAdjacentHTML('beforeend', `
                <div class="chat-image-preview-item">
                    <img src="${e.target.result}" alt="">
                    <button type="button" class="chat-image-preview-remove" onclick="removeImage(${i})">&times;</button>
                </div>
            `);
        };
        reader.readAsDataURL(file);
    });
}

function removeImage(index) {
    selectedImages.splice(index, 1);
    updateImagePreview();
}

// Создание опроса
document.getElementById('chatPollBtn')?.addEventListener('click', () => {
    document.getElementById('chatPollModal').style.display = 'flex';
});

document.getElementById('addPollOption')?.addEventListener('click', () => {
    const container = document.getElementById('pollOptions');
    const count = container.children.length + 1;
    if (count > 10) return;
    
    container.insertAdjacentHTML('beforeend', `
        <div class="poll-option-row">
            <input type="text" name="options[]" class="form-input" placeholder="Вариант ${count}" maxlength="200" required>
        </div>
    `);
});

document.getElementById('chatPollForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const options = formData.getAll('options[]').filter(o => o.trim());
    
    if (options.length < 2) {
        alert('Минимум 2 варианта ответа');
        return;
    }
    
    const res = await fetch('/api/chat/poll/create', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
        body: JSON.stringify({
            chat_id: chatId,
            question: formData.get('question'),
            options: options,
            is_multiple: formData.get('is_multiple') === 'on'
        })
    });
    
    if (res.ok) {
        document.getElementById('chatPollModal').style.display = 'none';
        this.reset();
        loadNewMessages();
    }
});

// Настройки чата
function openChatSettings() {
    document.getElementById('chatSettingsModal').style.display = 'flex';
}

document.getElementById('chatSettingsForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const res = await fetch('/api/community/chat/update', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
        body: JSON.stringify({
            id: chatId,
            name: formData.get('name'),
            description: formData.get('description'),
            message_timeout: formData.get('message_timeout') || null,
            files_disabled: formData.get('files_disabled') ? 1 : 0,
            messages_disabled: formData.get('messages_disabled') ? 1 : 0
        })
    });
    
    if (res.ok) {
        location.reload();
    }
});

async function deleteChat() {
    if (!confirm('Удалить чат? Все сообщения будут потеряны.')) return;
    
    const res = await fetch('/api/community/chat/delete', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
        body: JSON.stringify({ id: chatId })
    });
    
    if (res.ok) {
        history.back();
    }
}

// Вспомогательные функции
function scrollToBottom() {
    const container = document.getElementById('chatMessagesContainer');
    container.scrollTop = container.scrollHeight;
}

function formatTime(dateStr) {
    const date = new Date(dateStr);
    const now = new Date();
    const diff = now - date;
    
    if (diff < 60000) return 'только что';
    if (diff < 3600000) return Math.floor(diff / 60000) + ' мин';
    if (diff < 86400000) return date.toLocaleTimeString('ru', { hour: '2-digit', minute: '2-digit' });
    return date.toLocaleDateString('ru', { day: 'numeric', month: 'short' });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Автоматическое расширение textarea
document.getElementById('chatInput')?.addEventListener('input', function() {
    this.style.height = 'auto';
    this.style.height = Math.min(this.scrollHeight, 120) + 'px';
});

// Закрытие модалок
document.querySelectorAll('.modal [data-close]').forEach(el => {
    el.addEventListener('click', function() {
        this.closest('.modal').style.display = 'none';
    });
});

// Инициализация
loadMessages();

// Polling для новых сообщений
pollingInterval = setInterval(loadNewMessages, 3000);

// Остановка polling при уходе со страницы
window.addEventListener('beforeunload', () => {
    clearInterval(pollingInterval);
});
</script>
