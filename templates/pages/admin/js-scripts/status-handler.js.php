const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

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
            updateRowStatus(id, status);
            
            // Закрыть модалку если открыта
            const modal = document.querySelector('.modal[data-app-id="' + id + '"]');
            if (modal) closeModal(modal);
        } else {
            alert(data.error || 'Ошибка');
        }
    } catch (err) {
        alert('Ошибка сети');
    }
}

function updateRowStatus(id, status) {
    const row = document.querySelector(`tr[data-app-id="${id}"]`);
    if (!row) return;
    
    const statusEl = row.querySelector('[data-status]');
    if (statusEl) {
        statusEl.className = `app-status status-${status}`;
        statusEl.textContent = status === 'accepted' ? 'Одобрена' : 'Отклонена';
    }
    
    const actionsEl = row.querySelector('.admin-actions');
    if (actionsEl) {
        let html = '';
        if (status !== 'accepted') {
            html += `<button class="btn btn-sm admin-btn-accept" onclick="event.stopPropagation();setAppStatus(${id}, 'accepted')" title="Одобрить"><svg width="14" height="14"><use href="#icon-check"/></svg></button>`;
        }
        if (status !== 'rejected') {
            html += `<button class="btn btn-sm admin-btn-reject" onclick="event.stopPropagation();setAppStatus(${id}, 'rejected')" title="Отклонить"><svg width="14" height="14"><use href="#icon-x"/></svg></button>`;
        }
        actionsEl.innerHTML = html;
    }
}
