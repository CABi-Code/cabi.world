/**
 * Modal - единый обработчик модальных окон
 */

(function() {
    'use strict';
    
    let activeModal = null;
    let scrollbarWidth = 0;
    
    // Вычисляем ширину скроллбара
    function getScrollbarWidth() {
        const outer = document.createElement('div');
        outer.style.cssText = 'visibility:hidden;overflow:scroll;position:absolute;top:-9999px;width:100px';
        document.body.appendChild(outer);
        const width = outer.offsetWidth - outer.clientWidth;
        document.body.removeChild(outer);
        return width;
    }
    
    // Открыть модалку
    function openModal(modal) {
        if (typeof modal === 'string') {
            modal = document.getElementById(modal);
        }
        if (!modal) return;
        
        // Закрыть предыдущую
        if (activeModal && activeModal !== modal) {
            closeModal(activeModal, false);
        }
        
        // Показать модалку
        modal.style.display = 'flex';
        document.body.classList.add('no-scroll', 'modal-open');
        document.documentElement.classList.add('no-scroll');
        
        // Анимация
        requestAnimationFrame(() => {
            modal.classList.add('show');
        });
        
        activeModal = modal;
        
        // Фокус на первый элемент
        setTimeout(() => {
            const focusable = modal.querySelector('input:not([type="hidden"]), textarea, select, button:not([data-modal-close]):not([data-close])');
            if (focusable) focusable.focus();
        }, 100);
    }
    
    // Закрыть модалку
    function closeModal(modal, animate = true) {
        if (typeof modal === 'string') {
            modal = document.getElementById(modal);
        }
        if (!modal) return;
        
        modal.classList.remove('show');
        
        const finish = () => {
            modal.style.display = 'none';
            document.body.classList.remove('no-scroll', 'modal-open');
            document.documentElement.classList.remove('no-scroll');
            if (activeModal === modal) activeModal = null;
        };
        
        if (animate) {
            setTimeout(finish, 200);
        } else {
            finish();
        }
    }
    
    // Блокировка скролла
    function lockBodyScroll() {
        document.body.classList.add('no-scroll');
        document.documentElement.classList.add('no-scroll');
    }
    
    function unlockBodyScroll() {
        document.body.classList.remove('no-scroll');
        document.documentElement.classList.remove('no-scroll');
    }
    
    // Инициализация
    function init() {
        scrollbarWidth = getScrollbarWidth();
        document.documentElement.style.setProperty('--scrollbar-width', scrollbarWidth + 'px');
        
        // Делегирование событий
        document.addEventListener('click', (e) => {
            // Закрытие по data-modal-close или data-close
            const closeBtn = e.target.closest('[data-modal-close], [data-close]');
            if (closeBtn) {
                const modal = closeBtn.closest('.modal');
                if (modal) closeModal(modal);
                return;
            }
            
            // Закрытие по клику на backdrop
            if (e.target.classList.contains('modal-backdrop')) {
                const modal = e.target.closest('.modal');
                if (modal) closeModal(modal);
                return;
            }
            
            // Открытие по data-modal-open
            const opener = e.target.closest('[data-modal-open]');
            if (opener) {
                const modalId = opener.dataset.modalOpen;
                openModal(modalId);
            }
        });
        
        // Закрытие по Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && activeModal) {
                closeModal(activeModal);
            }
        });
    }
    
    // Запуск при загрузке DOM
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
    // Глобальные функции
    window.openModal = openModal;
    window.closeModal = closeModal;
    window.lockBodyScroll = lockBodyScroll;
    window.unlockBodyScroll = unlockBodyScroll;
})();
