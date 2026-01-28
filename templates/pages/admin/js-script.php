<?

// присоеденен в файле pages/admin/index.php через include

?>

<script>
const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
let currentAppId = null;

async function setAppStatus(id, status) {
    const action = status === 'accepted' ? 'одобрить' : 'отклонить';
    if (!confirm(`Вы уверены, что хотите ${action} заявку #${id}?`)) return;
    
    try {
        const res = await fetch('/api/admin/application/status', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
            body: JSON.stringify({ id, status })
        });
        const data = await res.json();
        
        if (data.success) {
            // Обновляем строку в таблице
            const row = document.querySelector(`tr[data-app-id="${id}"]`);
            if (row) {
                const statusEl = row.querySelector('[data-status]');
                statusEl.className = `app-status status-${status}`;
                statusEl.textContent = status === 'accepted' ? 'Одобрена' : 'Отклонена';
                
                // Обновляем кнопки
                const actionsEl = row.querySelector('.admin-actions');
                actionsEl.innerHTML = `
                    ${status !== 'accepted' ? `<button class="btn btn-sm admin-btn-accept" onclick="setAppStatus(${id}, 'accepted')" title="Одобрить"><svg width="14" height="14"><use href="#icon-check"/></svg></button>` : ''}
                    ${status !== 'rejected' ? `<button class="btn btn-sm admin-btn-reject" onclick="setAppStatus(${id}, 'rejected')" title="Отклонить"><svg width="14" height="14"><use href="#icon-x"/></svg></button>` : ''}
                    <button class="btn btn-ghost btn-sm" onclick="viewAppDetails(${id})" title="Подробнее"><svg width="14" height="14"><use href="#icon-eye"/></svg></button>
                `;
            }
        } else {
            alert(data.error || 'Ошибка');
        }
    } catch (err) {
        alert('Ошибка сети');
    }
}

async function viewAppDetails(id) {
    currentAppId = id;
    const modal = document.getElementById('appDetailsModal');
    const content = document.getElementById('appDetailsContent');
    
    content.innerHTML = '<div class="loading"><div class="spinner"></div></div>';
    modal.style.display = 'flex';
    
    try {
        const res = await fetch(`/api/admin/application/${id}`, {
            headers: { 'X-CSRF-Token': csrf }
        });
        const data = await res.json();
        
        if (data.success && data.application) {
            const app = data.application;
            content.innerHTML = `
                <div class="app-detail">
                    <div class="app-detail-header">
                        <a href="/@${app.login}" class="feed-user">
                            <div class="feed-avatar" ${app.avatar ? '' : `style="background:linear-gradient(135deg,${(app.avatar_bg_value || '#3b82f6,#8b5cf6').split(',')[0]},${(app.avatar_bg_value || '#3b82f6,#8b5cf6').split(',')[1] || '#8b5cf6'})"`}>
                                ${app.avatar ? `<img src="${app.avatar}" alt="">` : app.username.charAt(0).toUpperCase()}
                            </div>
                            <div>
                                <div class="feed-name">${app.username}</div>
                                <div class="feed-login">@${app.login}</div>
                            </div>
                        </a>
                        <a href="/modpack/${app.platform}/${app.slug}" class="feed-modpack">
                            ${app.icon_url ? `<img src="${app.icon_url}" alt="" class="feed-mp-icon">` : ''}
                            ${app.modpack_name}
                        </a>
                    </div>
                    <div class="app-detail-body">
                        <p class="app-detail-message">${app.message.replace(/\n/g, '<br>')}</p>
                        ${app.relevant_until ? `<p style="font-size:0.8125rem;color:var(--text-muted);margin-top:0.5rem;">Актуально до: ${new Date(app.relevant_until).toLocaleDateString('ru')}</p>` : ''}
                    </div>
                    <div class="app-detail-contacts">
                        ${app.contact_discord ? `<span class="contact-btn discord"><svg width="14" height="14"><use href="#icon-discord"/></svg>${app.contact_discord}</span>` : ''}
                        ${app.contact_telegram ? `<a href="https://t.me/${app.contact_telegram.replace('@', '')}" class="contact-btn telegram" target="_blank"><svg width="14" height="14"><use href="#icon-telegram"/></svg>${app.contact_telegram}</a>` : ''}
                        ${app.contact_vk ? `<a href="https://vk.com/${app.contact_vk}" class="contact-btn vk" target="_blank"><svg width="14" height="14"><use href="#icon-vk"/></svg>${app.contact_vk}</a>` : ''}
                    </div>
                    <div class="app-detail-meta">
                        <span>Создано: ${new Date(app.created_at).toLocaleString('ru')}</span>
                        <span class="app-status status-${app.status}">${app.status === 'pending' ? 'Ожидает' : app.status === 'accepted' ? 'Одобрена' : 'Отклонена'}</span>
                    </div>
                </div>
            `;
            
            // Обновляем кнопки модалки
            document.getElementById('modalAcceptBtn').style.display = app.status !== 'accepted' ? '' : 'none';
            document.getElementById('modalRejectBtn').style.display = app.status !== 'rejected' ? '' : 'none';
        } else {
            content.innerHTML = '<div class="alert alert-error">Заявка не найдена</div>';
        }
    } catch (err) {
        content.innerHTML = '<div class="alert alert-error">Ошибка загрузки</div>';
    }
}

document.getElementById('modalAcceptBtn')?.addEventListener('click', () => {
    if (currentAppId) {
        setAppStatus(currentAppId, 'accepted');
        document.getElementById('appDetailsModal').style.display = 'none';
    }
});

document.getElementById('modalRejectBtn')?.addEventListener('click', () => {
    if (currentAppId) {
        setAppStatus(currentAppId, 'rejected');
        document.getElementById('appDetailsModal').style.display = 'none';
    }
});

// Закрытие модалки
document.querySelectorAll('#appDetailsModal [data-close]').forEach(el => {
    el.addEventListener('click', () => {
        document.getElementById('appDetailsModal').style.display = 'none';
    });
});
</script>