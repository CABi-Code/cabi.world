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
            const row = document.querySelector(`tr[data-app-id="${id}"]`);
            if (row) {
                const statusEl = row.querySelector('[data-status]');
                statusEl.className = `app-status status-${status}`;
                statusEl.textContent = status === 'accepted' ? 'Одобрена' : 'Отклонена';
                
                const actionsEl = row.querySelector('.admin-actions');
                actionsEl.innerHTML = `
                    ${status !== 'accepted' ? `<button class="btn btn-sm admin-btn-accept" onclick="setAppStatus(${id}, 'accepted')" title="Одобрить"><svg width="14" height="14"><use href="#icon-check"/></svg></button>` : ''}
                    ${status !== 'rejected' ? `<button class="btn btn-sm admin-btn-reject" onclick="setAppStatus(${id}, 'rejected')" title="Отклонить"><svg width="14" height="14"><use href="#icon-x"/></svg></button>` : ''}
                    <button class="btn btn-ghost btn-sm" onclick="viewAppDetails(${id})" title="Подробнее"><svg width="14" height="14"><use href="#icon-eye"/></svg></button>
                `;
            }
            if (currentAppId === id) closeModal('appDetailsModal');
        } else {
            alert(data.error || 'Ошибка');
        }
    } catch (err) {
        alert('Ошибка сети');
    }
}

async function viewAppDetails(id) {
    currentAppId = id;
    const content = document.getElementById('appDetailsContent');
    content.innerHTML = '<div style="text-align:center;padding:2rem;">Загрузка...</div>';
    openModal('appDetailsModal');
    
    try {
        const res = await fetch(`/api/admin/application/${id}`, { headers: { 'X-CSRF-Token': csrf } });
        const data = await res.json();
        
        if (data.success && data.application) {
            const app = data.application;
            const colors = (app.avatar_bg_value || '#3b82f6,#8b5cf6').split(',');
            const style = `background:linear-gradient(135deg,${colors[0]},${colors[1] || colors[0]})`;
            
            content.innerHTML = `
                <div class="app-detail">
                    <div class="app-detail-header">
                        <a href="/@${app.login}" class="admin-user-link">
                            <div class="admin-avatar" style="${!app.avatar ? style : ''}">
                                ${app.avatar ? `<img src="${app.avatar}" alt="">` : app.username.charAt(0).toUpperCase()}
                            </div>
                            <div>
                                <div class="admin-username">${app.username}</div>
                                <div class="admin-login">@${app.login}</div>
                            </div>
                        </a>
                        <span class="app-status status-${app.status}">${{pending:'Ожидает',accepted:'Одобрена',rejected:'Отклонена'}[app.status]}</span>
                    </div>
                    <div class="app-detail-body"><p class="app-detail-message">${app.message}</p></div>
                    ${app.images?.length ? `<div style="display:flex;gap:0.5rem;flex-wrap:wrap;margin-bottom:1rem;">${app.images.map(i=>`<img src="${i.image_path}" style="max-width:150px;border-radius:6px;">`).join('')}</div>` : ''}
                    <div class="app-detail-meta"><span>Создана: ${new Date(app.created_at).toLocaleString('ru')}</span></div>
                </div>`;
            
            document.getElementById('modalAcceptBtn').onclick = () => setAppStatus(id, 'accepted');
            document.getElementById('modalRejectBtn').onclick = () => setAppStatus(id, 'rejected');
            document.getElementById('modalAcceptBtn').style.display = app.status !== 'accepted' ? '' : 'none';
            document.getElementById('modalRejectBtn').style.display = app.status !== 'rejected' ? '' : 'none';
        } else {
            content.innerHTML = '<p style="color:var(--danger)">Ошибка загрузки</p>';
        }
    } catch (err) {
        content.innerHTML = '<p style="color:var(--danger)">Ошибка сети</p>';
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