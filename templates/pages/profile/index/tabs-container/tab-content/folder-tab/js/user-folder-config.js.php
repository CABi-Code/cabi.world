<script>
let iconMap = {};

async function loadIconMap() {
    try {
        const res = await fetch('/api/user-folder/icon-map');   // ← URL для получения 
        if (res.ok) {
            iconMap = await res.json();
        } else {
            console.warn('Не удалось загрузить iconMap, используется fallback');
            iconMap = {
                folder: { icon: 'folder', color: '#eab308' },
                chat: { icon: 'message-circle', color: '#ec4899' },
                modpack: { icon: 'package', color: '#8b5cf6' },
                mod: { icon: 'puzzle', color: '#10b981' },
                server: { icon: 'server', color: '#f59e0b' },
                application: { icon: 'file-text', color: '#3b82f6' },
                shortcut: { icon: 'link', color: '#6366f1' }
            };
        }
    } catch (e) {
        console.error('Ошибка загрузки iconMap', e);
    }
}

window.getIconMap = () => iconMap;

</script>