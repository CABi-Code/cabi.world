<script type="module">
import { openModpackSelector } from '/js/modpack-selector/index.js';

document.getElementById('openModpackSelectorBtn')?.addEventListener('click', () => {
    openModpackSelector((modpack) => {
        displaySelectedModpack(modpack);
    });
});

function displaySelectedModpack(mp) {
    const preview = document.getElementById('selectedModpackPreview');
    const card = document.getElementById('selectedModpackCard');
    
    if (!preview || !card) return;
    
    const iconHtml = mp.icon_url 
        ? `<img src="${escapeHtml(mp.icon_url)}" alt="">`
        : `<svg width="20" height="20"><use href="#icon-package"/></svg>`;
    
    const platformName = mp.platform === 'modrinth' ? 'Modrinth' : 'CurseForge';
    
    card.innerHTML = `
        <div class="mp-icon">${iconHtml}</div>
        <div class="mp-info">
            <div class="mp-name">${escapeHtml(mp.name)}</div>
            <div class="mp-meta">${platformName} • ${formatNumber(mp.downloads)} скачиваний</div>
        </div>
        <button type="button" class="btn btn-ghost btn-sm" onclick="clearSelectedModpack()">
            <svg width="14" height="14"><use href="#icon-x"/></svg>
        </button>
    `;
    
    preview.style.display = 'block';
    
    // Сохраняем данные для использования
    window.selectedModpack = mp;
}

window.clearSelectedModpack = function() {
    document.getElementById('selectedModpackPreview').style.display = 'none';
    window.selectedModpack = null;
};

function escapeHtml(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

function formatNumber(num) {
    if (!num) return '0';
    if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M';
    if (num >= 1000) return (num / 1000).toFixed(1) + 'K';
    return num.toString();
}
</script>
