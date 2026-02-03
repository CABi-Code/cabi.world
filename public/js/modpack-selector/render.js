/**
 * Render модуль для модпак-селектора
 */

export function renderModpackList(modpacks, selectedModpack) {
    return modpacks.map(mp => renderModpackCard(mp, selectedModpack)).join('');
}

export function renderModpackCard(mp, selectedModpack) {
    const isSelected = selectedModpack && 
        selectedModpack.id === mp.id && 
        selectedModpack.platform === mp.platform;
    
    const iconHtml = mp.icon_url 
        ? `<img src="${escapeHtml(mp.icon_url)}" alt="" loading="lazy">`
        : `<svg width="24" height="24"><use href="#icon-package"/></svg>`;
    
    const platformClass = mp.platform === 'modrinth' ? 'modrinth' : 'curseforge';
    const platformName = mp.platform === 'modrinth' ? 'Modrinth' : 'CurseForge';
    
    return `
        <div class="modpack-selector-card ${isSelected ? 'selected' : ''}" 
             data-id="${escapeHtml(mp.id)}"
             data-platform="${escapeHtml(mp.platform)}"
             data-slug="${escapeHtml(mp.slug)}">
            <div class="modpack-selector-card-icon">${iconHtml}</div>
            <div class="modpack-selector-card-info">
                <div class="modpack-selector-card-name">${escapeHtml(mp.name)}</div>
                <div class="modpack-selector-card-meta">
                    <span class="modpack-selector-platform ${platformClass}">${platformName}</span>
                    <span>
                        <svg><use href="#icon-download"/></svg>
                        ${formatNumber(mp.downloads)}
                    </span>
                    <span>
                        <svg><use href="#icon-users"/></svg>
                        ${mp.app_count || 0} заявок
                    </span>
                </div>
            </div>
        </div>
    `;
}

export function renderLoading() {
    return `
        <div class="modpack-selector-loading">
            <div class="spinner"></div>
            <span>Загрузка модпаков...</span>
        </div>
    `;
}

export function renderEmpty(message = 'Модпаки не найдены') {
    return `
        <div class="modpack-selector-empty">
            <svg width="48" height="48"><use href="#icon-package"/></svg>
            <p>${escapeHtml(message)}</p>
        </div>
    `;
}

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
