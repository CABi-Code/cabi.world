// notifications.js

import { esc } from './utils/common.js';

export function initNotifications(csrf) {
    const notifMenu = document.getElementById('notifMenu');
    if (notifMenu) {
        const btn = notifMenu.querySelector('.notif-btn');
        const list = document.getElementById('notifList');
        
        btn.addEventListener('click', async () => {
            const isOpen = notifMenu.classList.toggle('open');
            if (isOpen && list) {
                try {
                    const res = await fetch('/api/notifications', { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                    const data = await res.json();
                    if (data.notifications?.length) {
                        list.innerHTML = data.notifications.map(n => `
                            <a href="${n.link || '#'}" class="notif-item ${n.is_read ? '' : 'unread'}">
                                <div style="font-weight:500;font-size:0.875rem;margin-bottom:0.125rem;">${esc(n.title)}</div>
                                ${n.message ? `<div style="font-size:0.8125rem;color:var(--text-secondary)">${esc(n.message)}</div>` : ''}
                            </a>
                        `).join('');
                    } else {
                        list.innerHTML = '<div class="notif-empty">Нет уведомлений</div>';
                    }
                } catch (e) {
                    list.innerHTML = '<div class="notif-empty">Ошибка загрузки</div>';
                }
            }
        });
        
        document.getElementById('markAllRead')?.addEventListener('click', async () => {
            await fetch('/api/notifications/read', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
                body: JSON.stringify({})
            });
            document.querySelectorAll('.notif-item.unread').forEach(el => el.classList.remove('unread'));
            document.querySelector('.notif-badge')?.remove();
        });
        
        document.addEventListener('click', e => {
            if (!notifMenu.contains(e.target)) notifMenu.classList.remove('open');
        });
    }
}