<script>
/**
 * Базовый класс для панелек элементов папки
 */
window.PanelBase = {
    panel: null,
    currentItem: null,
    
    init() {
        this.panel = document.getElementById('itemPanel');
    },
    
    getHeader(onClose = 'closeItemPanel()') {
        return `<div class="panel-header">
            <button class="panel-close-btn" onclick="${onClose}">
                <svg width="18" height="18"><use href="#icon-x"/></svg>
            </button>
        </div>`;
    },
    
    getPath(path) {
        if (!path || path.length === 0) return '';
        const maxItems = 3;
        let items = path;
        let truncated = false;
        
        if (items.length > maxItems) {
            truncated = true;
            items = items.slice(-maxItems);
        }
        
        let html = '<div class="panel-path"><div class="path-items">';
        if (truncated) html += '<span class="path-ellipsis">...</span>';
        
        items.forEach((item, idx) => {
            const iconData = iconMap[item.item_type] || { icon: 'file', color: '#94a3b8' };
            const isLast = idx === items.length - 1;
            
            html += `<button class="path-item ${isLast ? 'current' : ''}" 
                onclick="openItemPanel(${item.id}, '${item.item_type}')">
                <svg width="12" height="12" style="color:${item.color || iconData.color}">
                    <use href="#icon-${item.icon || iconData.icon}"/>
                </svg>
                <span>${esc(item.name)}</span>
            </button>`;
            
            if (!isLast) html += '<span class="path-sep">/</span>';
        });
        
        html += '</div></div>';
        return html;
    },
    
    getItemHeader(item) {
        const iconData = iconMap[item.item_type] || { icon: 'file', color: '#94a3b8' };
        const icon = item.icon || iconData.icon;
        const color = item.color || iconData.color;
        
        return `<div class="panel-item-header">
            <span class="panel-icon" style="color:${esc(color)}">
                <svg width="24" height="24"><use href="#icon-${esc(icon)}"/></svg>
            </span>
            <h3 class="panel-title">${esc(item.name)}</h3>
            <!-- <button class="panel-link-btn" onclick="PanelBase.copyItemLink(${item.id})" title="Копировать ссылку"> -->
            <button class="panel-link-btn" onclick="window.open(window.location.origin + '/item/' + ${item.id}, '_blank')" title="Открыть ссылку">
                <svg width="14" height="14"><use href="#icon-link"/></svg>
            </button>
        </div>`;
    },
    
    getDescription(item) {
        if (!item.description) return '';
        return `<p class="panel-description">${esc(item.description)}</p>`;
    },
    
    getChildrenList(children) {
        if (!children || children.length === 0) return '';
        
        let html = `<div class="panel-children">
            <div class="panel-children-title">Содержимое:</div>`;
        
        children.forEach(child => {
            const iconData = iconMap[child.item_type] || { icon: 'file', color: '#94a3b8' };
            const icon = child.icon || iconData.icon;
            const color = child.color || iconData.color;
            
            html += `<button class="panel-child-item" onclick="openItemPanel(${child.id}, '${child.item_type}')">
                <svg width="16" height="16" style="color:${color}"><use href="#icon-${icon}"/></svg>
                <span>${esc(child.name)}</span>
            </button>`;
        });
        
        html += '</div>';
        return html;
    },
    
    copyItemLink(itemId) {
        const url = `${window.location.origin}/item/${itemId}`;
        navigator.clipboard.writeText(url).then(() => {
            // Показываем уведомление
            const btn = event.currentTarget;
            const originalHtml = btn.innerHTML;
            btn.innerHTML = '<svg width="14" height="14"><use href="#icon-check"/></svg>';
            btn.classList.add('copied');
            setTimeout(() => {
                btn.innerHTML = originalHtml;
                btn.classList.remove('copied');
            }, 2000);
        });
    }
};

PanelBase.init();
</script>
