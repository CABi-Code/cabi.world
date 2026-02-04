<script>

window.openItemPanel = async function(itemId, itemType) {
	const panel = document.getElementById('itemPanel');
	if (!panel) return;
	
	panel.innerHTML = '<div class="panel-loading"><div class="spinner"></div></div>';
	panel.classList.add('open');
	
	try {
		const res = await fetch(`/api/user-folder/public/item?id=${itemId}`);
		if (!res.ok) throw new Error('Not found');
		
		const { item, path, children } = await res.json();
		renderPanel(item, path, children);
	} catch (e) {
		panel.innerHTML = `
			<div class="panel-error">
				<span>Не удалось загрузить элемент</span>
				<button class="panel-close-btn" onclick="closeItemPanel()">
					<svg width="16" height="16"><use href="#icon-x"/></svg>
				</button>
			</div>
		`;
	}
};

window.closeItemPanel = function() {
	const panel = document.getElementById('itemPanel');
	if (panel) {
		panel.classList.remove('open');
		panel.innerHTML = `<div class="panel-placeholder">
			<svg width="24" height="24"><use href="#icon-info"/></svg>
			<p>Выберите элемент</p>
		</div>`;
	}
};


function renderPanel(item, path, children) {
	const panel = document.getElementById('itemPanel');
	const iconData = iconMap[item.item_type] || { icon: 'file', color: '#94a3b8' };
	const icon = item.icon || iconData.icon;
	const color = item.color || iconData.color;
	const isEntity = ['folder', 'chat', 'modpack', 'mod'].includes(item.item_type);
	
	let html = `
		<div class="panel-header">
			<button class="panel-close-btn" onclick="closeItemPanel()">
				<svg width="18" height="18"><use href="#icon-x"/></svg>
			</button>
		</div>
		
		<div class="panel-path">${renderPath(path)}</div>
		
		<div class="panel-item-header">
			<span class="panel-icon" style="color:${esc(color)}">
				<svg width="24" height="24"><use href="#icon-${esc(icon)}"/></svg>
			</span>
			<h3 class="panel-title">${esc(item.name)}</h3>
		</div>
	`;
	
	if (item.description) {
		html += `<p class="panel-description">${esc(item.description)}</p>`;
	}
	
	// Для чата - загружаем чат прямо здесь
	if (item.item_type === 'chat') {
		html += `<div class="panel-chat" id="panelChat" data-chat-id="${item.id}">
			<div class="chat-loading">Загрузка чата...</div>
		</div>`;
	}
	// Для папок/сущностей - показываем содержимое
	else if (isEntity && children.length > 0) {
		html += `<div class="panel-children">
			<div class="panel-children-title">Содержимое:</div>
			${renderChildrenList(children)}
		</div>`;
	} else if (isEntity) {
		html += `<div class="panel-empty-children">Папка пуста</div>`;
	}
	
	// Для сервера - показываем IP
	if (item.item_type === 'server' && item.settings) {
		const settings = typeof item.settings === 'string' ? JSON.parse(item.settings) : item.settings;
		if (settings.ip) {
			html += `<div class="panel-server-info">
				<span class="server-ip">${esc(settings.ip)}${settings.port && settings.port !== 25565 ? ':' + settings.port : ''}</span>
				<button class="btn btn-ghost btn-xs" onclick="copyToClipboard('${esc(settings.ip)}')">Копировать</button>
			</div>`;
		}
	}
	
	panel.innerHTML = html;
	
	// Загружаем чат если это чат
	if (item.item_type === 'chat') {
		loadChatInPanel(item.id);
	}
}

function renderPath(path) {
	if (!path || path.length === 0) return '';
	
	// Обрезаем если слишком длинный (оставляем последние 3)
	const maxItems = 3;
	let items = path;
	let truncated = false;
	
	if (items.length > maxItems) {
		truncated = true;
		items = items.slice(-maxItems);
	}
	
	let html = '<div class="path-items">';
	
	if (truncated) {
		html += '<span class="path-ellipsis">...</span>';
	}
	
	items.forEach((item, idx) => {
		const iconData = iconMap[item.item_type] || { icon: 'file', color: '#94a3b8' };
		const isLast = idx === items.length - 1;
		
		html += `
			<button class="path-item ${isLast ? 'current' : ''}" onclick="openItemPanel(${item.id}, '${item.item_type}')">
				<svg width="12" height="12" style="color:${item.color || iconData.color}"><use href="#icon-${item.icon || iconData.icon}"/></svg>
				<span>${esc(item.name)}</span>
			</button>
		`;
		
		if (!isLast) {
			html += '<span class="path-sep">/</span>';
		}
	});
	
	html += '</div>';
	return html;
}

function renderChildrenList(children) {
	return children.map(child => {
		const iconData = iconMap[child.item_type] || { icon: 'file', color: '#94a3b8' };
		const icon = child.icon || iconData.icon;
		const color = child.color || iconData.color;
		
		return `
			<button class="panel-child-item" onclick="openItemPanel(${child.id}, '${child.item_type}')">
				<svg width="16" height="16" style="color:${color}"><use href="#icon-${icon}"/></svg>
				<span>${esc(child.name)}</span>
			</button>
		`;
	}).join('');
}

async function loadChatInPanel(chatId) {
	const container = document.getElementById('panelChat');
	if (!container) return;
	
	// TODO: Загрузить и отрендерить чат
	container.innerHTML = `
		<div class="panel-chat-placeholder">
			<a href="/chat/${chatId}" class="btn btn-primary btn-sm">Открыть чат</a>
		</div>
	`;
}


</script>