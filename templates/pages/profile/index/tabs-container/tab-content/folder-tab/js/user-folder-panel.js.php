<script>
/**
 * Главный контроллер панелей элементов папки
 * Подключает соответствующие панели по типу элемента
 */

// Текущая активная панель
let currentPanelType = null;

// Маппинг типов на панели
const panelMap = {
    folder: 'PanelFolder',
    chat: 'PanelChat',
    modpack: 'PanelModpack',
    mod: 'PanelMod',
    server: 'PanelServer',
    application: 'PanelApplication',
    shortcut: 'PanelShortcut'
};

window.openItemPanel = async function(itemId, itemType) {
    const panel = document.getElementById('itemPanel');
    if (!panel) return;
    
    // Очищаем предыдущую панель
    cleanupCurrentPanel();
    
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
            </div>`;
    }
};

window.closeItemPanel = function() {
    cleanupCurrentPanel();
    
    const panel = document.getElementById('itemPanel');
    if (panel) {
        panel.classList.remove('open');
        panel.innerHTML = `<div class="panel-placeholder">
            <svg width="24" height="24"><use href="#icon-info"/></svg>
            <p>Выберите элемент</p>
        </div>`;
    }
};

function cleanupCurrentPanel() {
    if (currentPanelType) {
        const panelClass = window[panelMap[currentPanelType]];
        if (panelClass && typeof panelClass.cleanup === 'function') {
            panelClass.cleanup();
        }
        currentPanelType = null;
    }
}

function renderPanel(item, path, children) {
    const panel = document.getElementById('itemPanel');
    const panelClassName = panelMap[item.item_type];
    const panelClass = window[panelClassName];
    
    let html = '';
    
    if (panelClass && typeof panelClass.render === 'function') {
        html = panelClass.render(item, path, children);
        currentPanelType = item.item_type;
    } else {
        // Fallback на базовый рендер
        html = renderFallbackPanel(item, path, children);
    }
    
    panel.innerHTML = html;
    
    // Вызываем afterRender
    if (panelClass && typeof panelClass.afterRender === 'function') {
        panelClass.afterRender(item);
    }
}

function renderFallbackPanel(item, path, children) {
    let html = PanelBase.getHeader();
    html += PanelBase.getPath(path);
    html += PanelBase.getItemHeader(item);
    html += PanelBase.getDescription(item);
    
    if (children && children.length > 0) {
        html += PanelBase.getChildrenList(children);
    }
    
    return html;
}
</script>
