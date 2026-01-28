/**
 * Modal Manager - единый обработчик модальных окон
 */

class ModalManager {
    constructor() {
        this.activeModal = null;
        this.scrollbarWidth = this.getScrollbarWidth();
        this.init();
    }

    init() {
        // Установка CSS переменной для ширины скроллбара
        document.documentElement.style.setProperty('--scrollbar-width', this.scrollbarWidth + 'px');
        
        // Делегирование событий для закрытия
        document.addEventListener('click', (e) => {
            // Закрытие по кнопке или бэкдропу
            if (e.target.matches('[data-modal-close], [data-close]') || 
                e.target.closest('[data-modal-close], [data-close]')) {
                const modal = e.target.closest('.modal');
                if (modal) this.close(modal);
            }
            
            // Открытие по data-modal-open
            const opener = e.target.closest('[data-modal-open]');
            if (opener) {
                const modalId = opener.dataset.modalOpen;
                const modal = document.getElementById(modalId);
                if (modal) this.open(modal);
            }
        });

        // Закрытие по Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.activeModal) {
                this.close(this.activeModal);
            }
        });
    }

    getScrollbarWidth() {
        const outer = document.createElement('div');
        outer.style.cssText = 'visibility:hidden;overflow:scroll;position:absolute;top:-9999px;width:100px';
        document.body.appendChild(outer);
        const width = outer.offsetWidth - outer.clientWidth;
        document.body.removeChild(outer);
        return width;
    }

    open(modal) {
        if (typeof modal === 'string') {
            modal = document.getElementById(modal);
        }
        if (!modal) return;

        // Закрыть предыдущую модалку если есть
        if (this.activeModal && this.activeModal !== modal) {
            this.close(this.activeModal, false);
        }

        modal.style.display = 'flex';
        document.body.classList.add('modal-open', 'no-scroll');
        document.documentElement.classList.add('no-scroll');
        
        // Анимация появления
        requestAnimationFrame(() => {
            modal.classList.add('show');
        });

        this.activeModal = modal;
        
        // Фокус на первый интерактивный элемент
        const focusable = modal.querySelector('input:not([type="hidden"]), textarea, select, button:not([data-modal-close])');
        if (focusable) {
            setTimeout(() => focusable.focus(), 100);
        }
    }

    close(modal, animate = true) {
        if (typeof modal === 'string') {
            modal = document.getElementById(modal);
        }
        if (!modal) return;

        modal.classList.remove('show');
        
        const finish = () => {
            modal.style.display = 'none';
            document.body.classList.remove('modal-open', 'no-scroll');
            document.documentElement.classList.remove('no-scroll');
            if (this.activeModal === modal) {
                this.activeModal = null;
            }
        };

        if (animate) {
            setTimeout(finish, 200);
        } else {
            finish();
        }
    }

    // Статические методы для глобального доступа
    static instance = null;

    static getInstance() {
        if (!ModalManager.instance) {
            ModalManager.instance = new ModalManager();
        }
        return ModalManager.instance;
    }
}

// Глобальные функции для совместимости
window.openModal = (modal) => ModalManager.getInstance().open(modal);
window.closeModal = (modal) => ModalManager.getInstance().close(modal);
window.lockBodyScroll = () => {
    document.body.classList.add('no-scroll');
    document.documentElement.classList.add('no-scroll');
};
window.unlockBodyScroll = () => {
    document.body.classList.remove('no-scroll');
    document.documentElement.classList.remove('no-scroll');
};

// Инициализация при загрузке
document.addEventListener('DOMContentLoaded', () => {
    ModalManager.getInstance();
});

export { ModalManager };
