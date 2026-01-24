// feed-sort.js

import { initLightbox } from './lightbox.js';

export function initFeedSort() {
    const feedSortSelect = document.getElementById('feedSortSelect');
    const feedContainer = document.getElementById('feedContainer');
    
    if (feedSortSelect && feedContainer) {
        feedSortSelect.addEventListener('change', async function() {
            const sort = this.value;
            const url = new URL(window.location);
            url.searchParams.set('sort', sort);
            url.searchParams.set('page', '1');
            window.history.pushState({}, '', url);
            
            feedContainer.style.opacity = '0.5';
            
            try {
                const response = await fetch(`/api/feed?sort=${sort}&page=1`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await response.json();
                
                if (data.success && data.html) {
                    feedContainer.innerHTML = data.html;
                    initLightbox(); // Переинициализация lightbox для новых элементов
                }
            } catch (err) {
                console.error('Feed load error:', err);
            }
            
            feedContainer.style.opacity = '1';
        });
    }
}