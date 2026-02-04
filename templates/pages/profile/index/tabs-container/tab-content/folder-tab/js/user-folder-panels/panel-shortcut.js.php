<script>
/**
 * Панель для элемента типа "shortcut"
 */
window.PanelShortcut = {
    render(item, path, children) {
        const settings = typeof item.settings === 'string' 
            ? JSON.parse(item.settings) : (item.settings || {});
        
        let html = PanelBase.getHeader();
        html += PanelBase.getPath(path);
        html += PanelBase.getItemHeader(item);
        html += PanelBase.getDescription(item);
        
        // Ссылка
        if (settings.url) {
            const displayUrl = settings.url.length > 50 
                ? settings.url.substring(0, 47) + '...' 
                : settings.url;
            
            html += `<div class="panel-shortcut-info">
                <a href="${esc(settings.url)}" target="_blank" rel="noopener" class="shortcut-link">
                    <svg width="14" height="14"><use href="#icon-external-link"/></svg>
                    <span>${esc(displayUrl)}</span>
                </a>
            </div>`;
        }
        
        return html;
    },
    
    afterRender(item) {},
    cleanup() {}
};
</script>
