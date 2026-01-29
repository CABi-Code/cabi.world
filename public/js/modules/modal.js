/**
 * Modal - единый обработчик модальных окон
 * Поддержка динамической загрузки модалок с сервера
 */
(function() {
    'use strict';
    
    let activeModal = null;
    let scrollbarWidth = 0;
    const loadedModals = new Map();

    function getScrollbarWidth() {
        const outer = document.createElement('div');
        outer.style.cssText = 'visibility:hidden;overflow:scroll;position:absolute;top:-9999px;width:100px';
        document.body.appendChild(outer);
        const width = outer.offsetWidth - outer.clientWidth;
        document.body.removeChild(outer);
        return width;
    }

    function lockBodyScroll() {
        document.body.classList.add('no-scroll', 'modal-open');
        document.documentElement.classList.add('no-scroll');
    }

    function unlockBodyScroll() {
        document.body.classList.remove('no-scroll', 'modal-open');
        document.documentElement.classList.remove('no-scroll');
    }

    function openModal(modal) {
        if (typeof modal === 'string') {
            modal = document.getElementById(modal);
        }
        if (!modal) return;
        
        if (activeModal && activeModal !== modal) {
            closeModal(activeModal, false);
        }
        
        modal.style.display = 'flex';
        lockBodyScroll();
        
        requestAnimationFrame(() => {
            modal.classList.add('show');
        });
        
        activeModal = modal;
        
        setTimeout(() => {
            const focusable = modal.querySelector(
                'input:not([type="hidden"]), textarea, select, ' +
                'button:not([data-modal-close]):not([data-close])'
            );
            if (focusable) focusable.focus();
        }, 100);
    }

    function closeModal(modal, animate = true) {
        if (typeof modal === 'string') {
            modal = document.getElementById(modal);
        }
        if (!modal) return;
        
        modal.classList.remove('show');
        
        const finish = () => {
            modal.style.display = 'none';
            unlockBodyScroll();
            if (activeModal === modal) activeModal = null;
            
            // Удаляем динамически загруженную модалку
            if (modal.dataset.dynamic === 'true') {
                modal.remove();
                loadedModals.delete(modal.id);
            }
        };
        
        if (animate) {
            setTimeout(finish, 200);
        } else {
            finish();
        }
    }

    /**
     * Загрузить и открыть модалку с сервера
     * @param {string} url - URL для загрузки HTML модалки
     * @param {object} options - Опции {id, data, onLoad}
     */
    async function loadModal(url, options = {}) {
        const { id, data = {}, onLoad } = options;
        const cacheKey = id || url;
        
        // Показываем лоадер если модалка не кэширована
        let loader = null;
        if (!loadedModals.has(cacheKey)) {
            loader = createLoader();
            document.body.appendChild(loader);
            lockBodyScroll();
        }
        
        try {
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
            const res = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrf || '',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(data)
            });
            
            if (!res.ok) throw new Error('Failed to load modal');
            
            const html = await res.text();
            
            // Удаляем старую версию если есть
            const existingModal = document.getElementById(id);
            if (existingModal) existingModal.remove();
            
            // Вставляем новую
            const container = document.createElement('div');
            container.innerHTML = html;
            const modal = container.firstElementChild;
            
            if (modal) {
                modal.dataset.dynamic = 'true';
                document.body.appendChild(modal);
                loadedModals.set(cacheKey, modal);
                
                if (onLoad) onLoad(modal);
                openModal(modal);
            }
        } catch (err) {
            console.error('Modal load error:', err);
            unlockBodyScroll();
        } finally {
            if (loader) loader.remove();
        }
    }

    function createLoader() {
        const loader = document.createElement('div');
        loader.className = 'modal-loader';
        loader.innerHTML = '<div class="modal-loader-spinner"></div>';
        return loader;
    }

    function init() {
        scrollbarWidth = getScrollbarWidth();
        document.documentElement.style.setProperty('--scrollbar-width', scrollbarWidth + 'px');
        
        document.addEventListener('click', (e) => {
            // Закрытие по data-modal-close или data-close
            const closeBtn = e.target.closest('[data-modal-close], [data-close]');
            if (closeBtn) {
                const modal = closeBtn.closest('.modal');
                if (modal) closeModal(modal);
                return;
            }
            
            // Закрытие по клику на backdrop или overlay
            if (e.target.classList.contains('modal-backdrop') || 
                e.target.classList.contains('modal-overlay')) {
                const modal = e.target.closest('.modal');
                if (modal) closeModal(modal);
                return;
            }
            
            // Открытие по data-modal-open
            const opener = e.target.closest('[data-modal-open]');
            if (opener) {
                const modalId = opener.dataset.modalOpen;
                openModal(modalId);
                return;
            }
            
            // Динамическая загрузка по data-modal-load
            const loader = e.target.closest('[data-modal-load]');
            if (loader) {
                e.preventDefault();
                const url = loader.dataset.modalLoad;
                const id = loader.dataset.modalId;
                const dataAttr = loader.dataset.modalData;
                const data = dataAttr ? JSON.parse(dataAttr) : {};
                loadModal(url, { id, data });
            }
        });
        
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && activeModal) {
                closeModal(activeModal);
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    window.openModal = openModal;
    window.closeModal = closeModal;
    window.loadModal = loadModal;
    window.lockBodyScroll = lockBodyScroll;
    window.unlockBodyScroll = unlockBodyScroll;
})();
