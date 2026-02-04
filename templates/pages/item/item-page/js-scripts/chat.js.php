<?php
/**
 * JS логика для страницы чата
 * @var array $item
 */
?>

const chatId = <?= $item['id'] ?>;
let lastMsgId = 0;
let pollingInterval = null;

// Загрузка сообщений
async function loadMessages(beforeId = null) {
    try {
        let url = `/api/chat/messages?chat_id=${chatId}&limit=30`;
        if (beforeId) url += `&before_id=${beforeId}`;
        
        const res = await fetch(url);
        const data = await res.json();
        
        renderMessages(data.messages || [], !beforeId);
        
        if (data.messages?.length > 0) {
            lastMsgId = Math.max(...data.messages.map(m => m.id));
        }
    } catch (e) {
        document.getElementById('chatMessages').innerHTML = 
            '<div class="chat-error">Не удалось загрузить сообщения</div>';
    }
}

function renderMessages(messages, replace = true) {
    const container = document.getElementById('chatMessages');
    if (replace) container.innerHTML = '';
    
    if (messages.length === 0 && replace) {
        container.innerHTML = '<div class="chat-empty">Нет сообщений</div>';
        return;
    }
    
    messages.forEach(msg => {
        container.insertAdjacentHTML('beforeend', createMessageHtml(msg));
    });
    
    container.scrollTop = container.scrollHeight;
}

function createMessageHtml(msg) {
    const colors = (msg.avatar_bg_value || '#3b82f6,#8b5cf6').split(',');
    const avatarStyle = msg.avatar ? '' : 
        `background:linear-gradient(135deg,${colors[0]},${colors[1] || colors[0]})`;
    const avatarContent = msg.avatar 
        ? `<img src="${escapeHtml(msg.avatar)}" alt="">` 
        : escapeHtml((msg.username || 'U').charAt(0).toUpperCase());
    
    const time = new Date(msg.created_at).toLocaleString('ru', {
        hour: '2-digit', minute: '2-digit'
    });
    
    return `<div class="chat-msg" data-id="${msg.id}">
        <a href="/@${escapeHtml(msg.login)}" class="msg-avatar" style="${avatarStyle}">${avatarContent}</a>
        <div class="msg-body">
            <div class="msg-header">
                <a href="/@${escapeHtml(msg.login)}" class="msg-author">${escapeHtml(msg.username)}</a>
                <span class="msg-time">${time}</span>
            </div>
            <div class="msg-text">${escapeHtml(msg.message || '')}</div>
        </div>
    </div>`;
}

// Отправка сообщения
document.getElementById('chatForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const input = document.getElementById('chatInput');
    const message = input.value.trim();
    if (!message) return;
    
    input.disabled = true;
    
    try {
        const fd = new FormData();
        fd.append('chat_id', chatId);
        fd.append('message', message);
        
        const res = await fetch('/api/chat/send', {
            method: 'POST',
            headers: { 'X-CSRF-Token': csrf },
            body: fd
        });
        
        if (res.ok) {
            input.value = '';
            loadNewMessages();
        } else {
            const data = await res.json();
            alert(data.error || 'Ошибка отправки');
        }
    } catch (e) {
        alert('Ошибка сети');
    } finally {
        input.disabled = false;
        input.focus();
    }
});

// Подгрузка новых сообщений
async function loadNewMessages() {
    if (lastMsgId === 0) return;
    
    try {
        const res = await fetch(`/api/chat/messages/new?chat_id=${chatId}&after_id=${lastMsgId}`);
        const data = await res.json();
        
        if (data.messages?.length > 0) {
            renderMessages(data.messages, false);
            lastMsgId = Math.max(...data.messages.map(m => m.id));
        }
    } catch (e) {}
}

function startPolling() {
    pollingInterval = setInterval(loadNewMessages, 5000);
}

function escapeHtml(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

// Инициализация
document.addEventListener('DOMContentLoaded', () => {
    loadMessages();
    startPolling();
});
