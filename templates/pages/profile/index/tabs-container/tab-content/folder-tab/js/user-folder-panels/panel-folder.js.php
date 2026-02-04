<script>
/**
 * Панель для элемента типа "folder"
 */
window.PanelFolder = {
    render(item, path, children) {
        let html = PanelBase.getHeader();
        html += PanelBase.getPath(path);
        html += PanelBase.getItemHeader(item);
        html += PanelBase.getDescription(item);
        
        // Содержимое папки
        if (children && children.length > 0) {
            html += PanelBase.getChildrenList(children);
        } else {
            html += `<div class="panel-empty-children">
                <svg width="32" height="32"><use href="#icon-folder-open"/></svg>
                <p>Папка пуста</p>
            </div>`;
        }
        
        return html;
    },
    
    afterRender(item) {
        // Нет специальных действий
    },
    
    cleanup() {
        // Нет ресурсов для очистки
    }
};
</script>
