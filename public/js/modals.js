// modals.js
export function initModals() {
    document.querySelectorAll('[data-modal]').forEach(trigger => {
        trigger.addEventListener('click', () => {
            const modal = document.getElementById(trigger.dataset.modal);
            if (modal) modal.style.display = 'flex';
        });
    });

    document.querySelectorAll('.modal [data-close]').forEach(el => {
        el.addEventListener('click', () => el.closest('.modal').style.display = 'none');
    });
}