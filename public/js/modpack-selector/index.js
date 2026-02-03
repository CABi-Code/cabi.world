/**
 * Modpack Selector Module
 * Глобальный компонент для выбора модпаков
 */

import { loadModpacks } from './api.js';
import { renderModpackList, renderLoading, renderEmpty } from './render.js';

class ModpackSelector {
    constructor() {
        this.modal = null;
        this.selectedModpack = null;
        this.modpacks = [];
        this.sortBy = 'downloads';
        this.searchQuery = '';
        this.searchTimeout = null;
        this.onSelect = null;
        this.isLoaded = false;
    }

    async open(callback) {
        this.onSelect = callback;
        this.selectedModpack = null;
        
        // Сначала загружаем модалку и ждём
        if (!this.modal) {
            await this.loadModal();
        }
        
        // Показываем модалку
        this.modal.style.display = 'flex';
        setTimeout(() => this.modal.classList.add('show'), 10);
        document.body.classList.add('modal-open');
        
        this.updateConfirmButton();
        
        // Загружаем данные ПОСЛЕ того как модалка в DOM
        if (!this.isLoaded) {
            await this.loadData();
        }
    }

    close() {
        if (!this.modal) return;
        
        this.modal.classList.remove('show');
        setTimeout(() => {
            this.modal.style.display = 'none';
            document.body.classList.remove('modal-open');
        }, 200);
    }

    async loadModal() {
        const response = await fetch('/api/modpack-selector/modal');
        const html = await response.text();
        
        const container = document.createElement('div');
        container.innerHTML = html;
        this.modal = container.firstElementChild;
        document.body.appendChild(this.modal);
        
        this.bindEvents();
    }

    bindEvents() {
        if (!this.modal) return;
        
        const closeButtons = this.modal.querySelectorAll('[data-modal-close]');
        closeButtons.forEach(btn => btn.addEventListener('click', () => this.close()));
        
        const searchInput = this.modal.querySelector('#modpackSelectorSearch');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => this.handleSearch(e.target.value));
        }
        
        const sortSelect = this.modal.querySelector('#modpackSelectorSort');
        if (sortSelect) {
            sortSelect.addEventListener('change', (e) => this.handleSort(e.target.value));
        }
        
        const confirmBtn = this.modal.querySelector('#modpackSelectorConfirm');
        if (confirmBtn) {
            confirmBtn.addEventListener('click', () => this.confirm());
        }
        
        // Закрытие по Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.modal?.classList.contains('show')) {
                this.close();
            }
        });
    }

    async loadData() {
        const listEl = this.modal?.querySelector('#modpackSelectorList');
        if (!listEl) {
            console.error('ModpackSelector: list element not found');
            return;
        }
        
        listEl.innerHTML = renderLoading();
        
        try {
            this.modpacks = await loadModpacks(this.sortBy);
            this.renderList();
            this.isLoaded = true;
        } catch (err) {
            console.error('Failed to load modpacks:', err);
            listEl.innerHTML = renderEmpty('Ошибка загрузки');
        }
    }

    renderList() {
        const listEl = this.modal?.querySelector('#modpackSelectorList');
        if (!listEl) return;
        
        const filtered = this.filterModpacks();
        
        if (filtered.length === 0) {
            listEl.innerHTML = renderEmpty('Модпаки не найдены');
            return;
        }
        
        listEl.innerHTML = renderModpackList(filtered, this.selectedModpack);
        this.bindCardEvents();
    }

    filterModpacks() {
        if (!this.searchQuery) return this.modpacks;
        
        const query = this.searchQuery.toLowerCase();
        return this.modpacks.filter(mp => 
            mp.name.toLowerCase().includes(query)
        );
    }

    bindCardEvents() {
        const cards = this.modal?.querySelectorAll('.modpack-selector-card');
        if (!cards) return;
        
        cards.forEach(card => {
            card.addEventListener('click', () => {
                const id = card.dataset.id;
                const platform = card.dataset.platform;
                
                cards.forEach(c => c.classList.remove('selected'));
                card.classList.add('selected');
                
                this.selectedModpack = this.modpacks.find(
                    mp => mp.id === id && mp.platform === platform
                );
                this.updateConfirmButton();
            });
        });
    }

    handleSearch(query) {
        this.searchQuery = query;
        clearTimeout(this.searchTimeout);
        this.searchTimeout = setTimeout(() => this.renderList(), 300);
    }

    async handleSort(sortBy) {
        this.sortBy = sortBy;
        this.isLoaded = false;
        await this.loadData();
    }

    updateConfirmButton() {
        const btn = this.modal?.querySelector('#modpackSelectorConfirm');
        if (btn) {
            btn.disabled = !this.selectedModpack;
        }
    }

    confirm() {
        if (this.selectedModpack && this.onSelect) {
            this.onSelect(this.selectedModpack);
        }
        this.close();
    }
}

// Singleton instance
let instance = null;

export function getModpackSelector() {
    if (!instance) {
        instance = new ModpackSelector();
    }
    return instance;
}

export function openModpackSelector(callback) {
    getModpackSelector().open(callback);
}
