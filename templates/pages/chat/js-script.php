<?

// присоеденен в файле pages/chat/index.php через include

?>

<script>
const chatId = <?= $chat['id'] ?>;
const communityId = <?= $community['id'] ?>;
const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
const currentUserId = <?= $user['id'] ?? 'null' ?>;
const canSendMessages = <?= $canSendMessages ? 'true' : 'false' ?>;
const canSendFiles = <?= $canSendFiles ? 'true' : 'false' ?>;
const messageTimeout = <?= $settings['message_timeout'] ?? 0 ?>;

let isLoadingOlder = false;           // Флаг, чтобы не запрашивать несколько раз подряд
let hasMoreOlder = true;              // Есть ли ещё старые сообщения (false, когда сервер вернул 0)
const scrollThreshold = 300;          // Пикселей от верха, при которых подгружаем
const messagesContainer = document.getElementById('chatMessagesContainer'); // ← ваш внешний контейнер с overflow-y: auto

let lastMessageId = 0;
let selectedImages = [];
let pollingInterval = null;



// Вычисляем примерное количество сообщений, которое помещается + запас
function calculateDesiredLimit() {
    const container = document.getElementById('chatMessagesContainer');
    if (!container) return 60; // fallback

    const containerHeight = container.clientHeight;
    
    // Средняя высота одного сообщения (замерьте реальную на вашем дизайне)
    // Обычно 60–120 px в зависимости от текста, аватарки, картинок
    const avgMessageHeight = 90; // ← подберите под ваш дизайн (протестируйте)
    
    // Запас в пикселях (чтобы подгружалось чуть раньше, чем пользователь дошел до конца)
    const bufferPx = 400; // ~4–5 сообщений сверху
    
    const visibleCount = Math.ceil((containerHeight + bufferPx) / avgMessageHeight);
    
    // Ограничиваем разумными значениями
    const minLimit = 10;
    const maxLimit = 120;
    
    return Math.min(Math.max(visibleCount, minLimit), maxLimit);
}

// Текущий лимит (начальное значение)
let currentLimit = calculateDesiredLimit();



// Загрузка сообщений
async function loadMessages(beforeId = null, isInitial = false) {
    if (isLoadingOlder && !isInitial) return; // Защита от параллельных запросов
   // if (!hasMoreOlder && !isInitial) return;

    if (!isInitial) isLoadingOlder = true;

    const url = `/api/chat/messages?chat_id=${chatId}` +
				`&limit=${currentLimit}` +
                (beforeId ? `&before_id=${beforeId}` : '');

    try {
        const res = await fetch(url);
        if (!res.ok) throw new Error('Ошибка загрузки');

        const data = await res.json();

        if (data.messages && data.messages.length > 0) {
            // Сохраняем позицию перед добавлением старых
            const prevScrollHeight = messagesContainer.scrollHeight;
            const prevScrollTop = messagesContainer.scrollTop;
			
			if (beforeId) {
				data.messages.reverse();  // переворачиваем, чтобы старые были первыми
			}
			
			if (data.messages && data.messages.length > 0) {
				renderMessages(data.messages, !!beforeId); // prepend = true если beforeId
				if (data.messages.length < currentLimit) {
					hasMoreOlder = false;
					console.log('Больше старых сообщений нет (пришло меньше лимита)');
				}
			} else {
				hasMoreOlder = false;
				console.log('Сервер вернул 0 сообщений → hasMoreOlder = false');
			}

            if (beforeId) {
                // После prepend восстанавливаем позицию
                const newScrollHeight = messagesContainer.scrollHeight;
                messagesContainer.scrollTop = prevScrollTop + (newScrollHeight - prevScrollHeight);
            } else {
                scrollToBottom();
            }

            // Обновляем lastMessageId только для новых (не для старых)
            if (!beforeId) {
                lastMessageId = Math.max(...data.messages.map(m => m.id));
            }

            // Если вернулось меньше лимита — больше нет старых
            if (data.messages.length < 50) { // предполагаем лимит 50 на бэкенде
                hasMoreOlder = false;
            }
        } else {
            hasMoreOlder = false;
        }
    } catch (err) {
        console.error('Ошибка загрузки сообщений:', err);
    } finally {
        if (!isInitial) isLoadingOlder = false;
		isLoadingOlder = false;
		console.log('Загрузка старых завершена, isLoadingOlder = false');
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
        const wasAtBottom = isAtBottom(); // Проверяем до добавления

        data.messages.forEach(msg => {
            const html = createMessageHtml(msg);
            document.getElementById('chatMessages').insertAdjacentHTML('beforeend', html);
        });

        lastMessageId = Math.max(...data.messages.map(m => m.id));

        if (wasAtBottom) {
            scrollToBottom();
        }
    }
}

// Вспомогательная функция — пользователь внизу?
function isAtBottom() {
    const threshold = 100; // пикселей
    return messagesContainer.scrollHeight - messagesContainer.scrollTop - messagesContainer.clientHeight < threshold;
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
currentLimit = calculateDesiredLimit();
loadMessages(null, true);


let resizeTimeout;
window.addEventListener('resize', () => {
    clearTimeout(resizeTimeout);
    resizeTimeout = setTimeout(() => {
        const newLimit = calculateDesiredLimit();
        if (newLimit !== currentLimit) {
            currentLimit = newLimit;
            console.log(`Лимит сообщений изменён на ${currentLimit}`);
            // Опционально: можно сразу подгрузить больше, если пользователь наверху
            if (messagesContainer.scrollTop < 500) {
                const oldest = document.querySelector('#chatMessages .chat-message:first-child');
                if (oldest) {
                    const oldestId = parseInt(oldest.dataset.messageId);
                    loadMessages(oldestId);
                }
            }
        }
    }, 300); // debounce 300 мс
});


// Бесконечная прокрутка вверх
messagesContainer.addEventListener('scroll', () => {
	
	console.log('scrollTop:', messagesContainer.scrollTop);
		
	if (isLoadingOlder) {
	//	console.log('→ заблокировано: isLoadingOlder = true');
	//	return;
	}
	if (!hasMoreOlder) {
		//console.log('→ заблокировано: hasMoreOlder = false');
		//return;
	}
    
    if (messagesContainer.scrollTop <= 200) {
        console.log('→ Пора подгружать старые');
        console.log('Близко к верху → пытаемся подгрузить старые сообщения');

        const oldestMessage = document.querySelector('#chatMessages .chat-message:first-child');
        if (!oldestMessage) {
            console.log('Нет сообщений в DOM — ничего не подгружаем');
            return;
        }

        const oldestId = parseInt(oldestMessage.dataset.messageId);
        console.log('Самое старое ID:', oldestId);

        if (oldestId && !isNaN(oldestId)) {
            loadMessages(oldestId);
        } else {
            console.warn('Не удалось получить oldestId');
        }
    }
});


// Polling для новых сообщений
pollingInterval = setInterval(loadNewMessages, 3000);

// Остановка polling при уходе со страницы
window.addEventListener('beforeunload', () => {
    clearInterval(pollingInterval);
});
</script>