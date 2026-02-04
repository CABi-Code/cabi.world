<script>
/**
 * Панель для элемента типа "modpack"
 */
window.PanelModpack = {
    render(item, path, children) {
        let html = PanelBase.getHeader();
        html += PanelBase.getPath(path);
        html += PanelBase.getItemHeader(item);
        html += PanelBase.getDescription(item);
        
        // Если есть reference_id - показываем ссылку на модпак
        if (item.reference_id && item.reference_type === 'modpacks') {
            html += `<div class="panel-modpack-actions">
                <a href="/modpack/${item.reference_id}" class="btn btn-primary btn-sm">
                    <svg width="14" height="14"><use href="#icon-external"/></svg>
                    Открыть модпак
                </a>
            </div>`;
        }
        
        // Содержимое (дочерние элементы)
        if (children && children.length > 0) {
            html += PanelBase.getChildrenList(children);
        }
        
        return html;
    },
    
    afterRender(item) {
        // Можно загрузить данные о модпаке с API
    },
    
    cleanup() {}
};
</script>
