// view-mode.js

import { getCookie, setCookie } from './utils/common.js';

export function initViewMode() {
    const savedView = getCookie('view_mode') || 'grid';
    document.querySelectorAll('[data-view]').forEach(el => {
        if (!el.classList.contains('view-btn')) el.setAttribute('data-view', savedView);
    });
    
    document.querySelectorAll('.view-btn').forEach(btn => {
        if (btn.dataset.view === savedView) btn.classList.add('active');
        btn.addEventListener('click', () => {
            const view = btn.dataset.view;
            setCookie('view_mode', view, 365);
            document.querySelectorAll('.view-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            document.querySelectorAll('[data-view]:not(.view-btn)').forEach(el => el.setAttribute('data-view', view));
        });
    });
}