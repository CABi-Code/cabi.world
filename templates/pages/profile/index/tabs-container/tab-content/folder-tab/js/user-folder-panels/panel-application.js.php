<script>
/**
 * Панель для элемента типа "application" (заметка)
 */
window.PanelApplication = {
    render(item, path, children) {
        let html = PanelBase.getHeader();
        html += PanelBase.getPath(path);
        html += PanelBase.getItemHeader(item);
        
        // Содержимое заметки
        if (item.description) {
            html += `<div class="panel-note-content">
                <div class="note-text">${this.formatText(item.description)}</div>
            </div>`;
        } else {
            html += `<div class="panel-note-empty">
                <svg width="24" height="24"><use href="#icon-file-text"/></svg>
                <p>Заметка пуста</p>
            </div>`;
        }
        
        // Дата создания
        if (item.created_at) {
            const date = new Date(item.created_at).toLocaleDateString('ru', {
                day: 'numeric', month: 'long', year: 'numeric'
            });
            html += `<div class="panel-note-meta">
                <span>Создано: ${date}</span>
            </div>`;
        }
        
        return html;
    },
    
    formatText(text) {
        // Простое форматирование: переносы строк в <br>
        return esc(text).replace(/\n/g, '<br>');
    },
    
    afterRender(item) {},
    cleanup() {}
};
</script>
