<script>
/**
 * Панель для элемента типа "chat"
 */
window.PanelChat = {
    currentChatId: null,
    lastMessageId: 0,
    pollingInterval: null,
    
    render(item, path, children) {
        this.currentChatId = item.id;
        
        let html = PanelBase.getHeader();
        html += PanelBase.getPath(path);
        html += PanelBase.getItemHeader(item);
        html += PanelBase.getDescription(item);
        
        // Контейнер чата
        html += `<div class="panel-chat-container" id="panelChatContainer">
            <div class="panel-chat-messages" id="panelChatMessages">
                <div class="chat-loading">
                    <div class="spinner"></div>
                    <span>Загрузка чата...</span>
                </div>
            </div>
            
            <div class="panel-chat-input" id="panelChatInput">
                <form id="panelChatForm" class="chat-mini-form">
                    <input type="text" id="panelChatText" 
                        placeholder="Написать сообщение..." 
                        maxlength="500" autocomplete="off">
                    <button type="submit" class="chat-mini-send">
                        <svg width="16" height="16"><use href="#icon-send"/></svg>
                    </button>
                </form>
            </div>
            
            <a href="/chat/${item.id}" class="panel-chat-expand">
                <svg width="14" height="14"><use href="#icon-maximize"/></svg>
                Открыть на всю страницу
            </a>
        </div>`;
        
        return html;
    },
    
    afterRender(item) {
        this.loadMessages(item.id);
        this.setupForm(item.id);
        this.startPolling(item.id);
    },
    
    async loadMessages(chatId, beforeId = null) {
        try {
            let url = `/api/chat/messages?chat_id=${chatId}&limit=20`;
            if (beforeId) url += `&before_id=${beforeId}`;
            
            const res = await fetch(url);
            if (!res.ok) throw new Error('Failed to load');
            
            const data = await res.json();
            this.renderMessages(data.messages || [], !beforeId);
            
            if (data.messages?.length > 0) {
                this.lastMessageId = Math.max(...data.messages.map(m => m.id));
            }
        } catch (e) {
            document.getElementById('panelChatMessages').innerHTML = `
                <div class="chat-error">Не удалось загрузить сообщения</div>`;
        }
    },
    
    renderMessages(messages, replace = true) {
        const container = document.getElementById('panelChatMessages');
        if (!container) return;
        
        if (replace) {
            container.innerHTML = '';
        }
        
        if (messages.length === 0 && replace) {
            container.innerHTML = '<div class="chat-empty">Нет сообщений</div>';
            return;
        }
        
        messages.forEach(msg => {
            const html = this.createMessageHtml(msg);
            container.insertAdjacentHTML('beforeend', html);
        });
        
        // Скролл вниз
        container.scrollTop = container.scrollHeight;
    },
    
    createMessageHtml(msg) {
        const avatarColors = (msg.avatar_bg_value || '#3b82f6,#8b5cf6').split(',');
        const avatarStyle = msg.avatar ? '' : 
            `background:linear-gradient(135deg,${avatarColors[0]},${avatarColors[1] || avatarColors[0]})`;
        const avatarContent = msg.avatar 
            ? `<img src="${esc(msg.avatar)}" alt="">` 
            : esc((msg.username || 'U').charAt(0).toUpperCase());
        
        const time = new Date(msg.created_at).toLocaleTimeString('ru', {
            hour: '2-digit', minute: '2-digit'
        });
        
        return `<div class="panel-chat-msg" data-id="${msg.id}">
            <a href="/@${esc(msg.login)}" class="msg-avatar" style="${avatarStyle}">${avatarContent}</a>
            <div class="msg-content">
                <div class="msg-header">
                    <a href="/@${esc(msg.login)}" class="msg-author">${esc(msg.username)}</a>
                    <span class="msg-time">${time}</span>
                </div>
                <div class="msg-text">${esc(msg.message || '')}</div>
            </div>
        </div>`;
    },
    
    setupForm(chatId) {
        const form = document.getElementById('panelChatForm');
        if (!form) return;
        
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const input = document.getElementById('panelChatText');
            const message = input.value.trim();
            if (!message) return;
            
            input.disabled = true;
            
            try {
                const formData = new FormData();
                formData.append('chat_id', chatId);
                formData.append('message', message);
                
                const res = await fetch('/api/chat/send', {
                    method: 'POST',
                    headers: { 'X-CSRF-Token': window.csrf },
                    body: formData
                });
                
                if (res.ok) {
                    input.value = '';
                    this.loadNewMessages(chatId);
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
    },
    
    async loadNewMessages(chatId) {
        if (this.lastMessageId === 0) return;
        
        try {
            const res = await fetch(
                `/api/chat/messages/new?chat_id=${chatId}&after_id=${this.lastMessageId}`
            );
            const data = await res.json();
            
            if (data.messages?.length > 0) {
                this.renderMessages(data.messages, false);
                this.lastMessageId = Math.max(...data.messages.map(m => m.id));
            }
        } catch (e) {
            console.warn('Failed to load new messages:', e);
        }
    },
    
    startPolling(chatId) {
        this.stopPolling();
        this.pollingInterval = setInterval(() => {
            if (this.currentChatId === chatId) {
                this.loadNewMessages(chatId);
            }
        }, 5000);
    },
    
    stopPolling() {
        if (this.pollingInterval) {
            clearInterval(this.pollingInterval);
            this.pollingInterval = null;
        }
    },
    
    cleanup() {
        this.stopPolling();
        this.currentChatId = null;
        this.lastMessageId = 0;
    }
};
</script>
