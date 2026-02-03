// ========== Sidebar ==========

function openSidebar() {
    document.getElementById('folderSidebar').classList.add('open');
    document.querySelector('.folder-main').classList.add('sidebar-open');
}

function closeSidebar() {
    document.getElementById('folderSidebar').classList.remove('open');
    document.querySelector('.folder-main').classList.remove('sidebar-open');
}

async function openFolderItem(itemId, itemType) {
    const sidebar = document.getElementById('folderSidebar');
    const title = document.getElementById('sidebarTitle');
    const content = document.getElementById('sidebarContent');
    
    content.innerHTML = '<div class="loading">Загрузка...</div>';
    openSidebar();
    
    try {
        const res = await fetch(`/api/user-folder/item?id=${itemId}`);
        const data = await res.json();
        
        if (!res.ok) {
            content.innerHTML = '<div class="error">Ошибка загрузки</div>';
            return;
        }
        
        const item = data.item;
        const details = data.details;
        
        title.textContent = item.name;
        
        // Рендерим контент в зависимости от типа
        content.innerHTML = renderSidebarContent(item, details);
        
    } catch (err) {
        console.error('Load item error:', err);
        content.innerHTML = '<div class="error">Ошибка загрузки</div>';
    }
}

function renderSidebarContent(item, details) {
    let html = '';
    
    switch (item.item_type) {
        case 'category':
        case 'modpack':
        case 'mod':
            html = renderEntityContent(item);
            break;
        case 'server':
            html = renderServerContent(item, details);
            break;
        case 'application':
            html = renderApplicationContent(item, details);
            break;
        case 'chat':
            html = renderChatContent(item, details);
            break;
        case 'shortcut':
            html = renderShortcutContent(item, details);
            break;
        default:
            html = '<p>Неизвестный тип элемента</p>';
    }
    
    return html;
}

function renderEntityContent(item) {
    return `
        <div class="sidebar-section">
            <p class="sidebar-description">${item.description || 'Нет описания'}</p>
        </div>
        <div class="sidebar-section">
            <span class="sidebar-label">Тип:</span>
            <span class="sidebar-value">${getTypeName(item.item_type)}</span>
        </div>
    `;
}

function renderServerContent(item, details) {
    if (!details) return '<p>Данные сервера не найдены</p>';
    
    return `
        <div class="sidebar-section">
            <p class="sidebar-description">${details.description || 'Нет описания'}</p>
        </div>
        <div class="sidebar-section">
            <span class="sidebar-label">Адрес:</span>
            <span class="sidebar-value">${details.address || '—'}:${details.port || 25565}</span>
        </div>
        <div class="sidebar-section">
            <span class="sidebar-label">Версия:</span>
            <span class="sidebar-value">${details.version || '—'}</span>
        </div>
        <div class="sidebar-section">
            <span class="sidebar-label">Статус:</span>
            <span class="sidebar-value ${details.is_online ? 'online' : 'offline'}">
                ${details.is_online ? 'Онлайн' : 'Офлайн'}
            </span>
        </div>
        ${details.is_online ? `
        <div class="sidebar-section">
            <span class="sidebar-label">Игроков:</span>
            <span class="sidebar-value">${details.players_online}/${details.players_max}</span>
        </div>
        ` : ''}
    `;
}

function renderApplicationContent(item, details) {
    return `
        <div class="sidebar-section">
            <a href="/application/${item.reference_id}" class="btn btn-primary btn-sm" target="_blank">
                Открыть заявку
            </a>
        </div>
    `;
}

function renderChatContent(item, details) {
    return `
        <div class="sidebar-section">
            <a href="/chat/${item.reference_id}" class="btn btn-primary btn-sm">
                Перейти в чат
            </a>
        </div>
    `;
}

function renderShortcutContent(item, details) {
    if (!details) return '<p>Данные ярлыка не найдены</p>';
    
    return `
        <div class="sidebar-section">
            <p class="sidebar-description">${details.description || 'Нет описания'}</p>
        </div>
        <div class="sidebar-section">
            <a href="${details.url}" class="btn btn-primary btn-sm" target="_blank" rel="noopener">
                <svg width="14" height="14"><use href="#icon-external-link"/></svg>
                Открыть ссылку
            </a>
        </div>
    `;
}

function getTypeName(type) {
    const names = {
        category: 'Категория',
        modpack: 'Модпак',
        mod: 'Мод',
        server: 'Сервер',
        application: 'Заявка',
        chat: 'Чат',
        shortcut: 'Ярлык'
    };
    return names[type] || type;
}
