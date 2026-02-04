<script>
/**
 * Панель для элемента типа "mod"
 */
window.PanelMod = {
    render(item, path, children) {
        let html = PanelBase.getHeader();
        html += PanelBase.getPath(path);
        html += PanelBase.getItemHeader(item);
        html += PanelBase.getDescription(item);
        
        // Если есть reference - ссылка на мод
        if (item.reference_id && item.reference_type) {
            html += `<div class="panel-mod-actions">
                <a href="/mod/${item.reference_id}" class="btn btn-secondary btn-sm">
                    <svg width="14" height="14"><use href="#icon-puzzle"/></svg>
                    Подробнее о моде
                </a>
            </div>`;
        }
        
        // Дочерние элементы
        if (children && children.length > 0) {
            html += PanelBase.getChildrenList(children);
        }
        
        return html;
    },
    
    afterRender(item) {},
    cleanup() {}
};
</script>
