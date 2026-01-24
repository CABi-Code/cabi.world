// lightbox.js
export function initLightbox() {
    const lightbox = document.getElementById('lightbox');
    const lightboxImg = document.getElementById('lightboxImg');
    
    document.querySelectorAll('[data-lightbox]').forEach(link => {
        link.addEventListener('click', e => {
            e.preventDefault();
            if (lightbox && lightboxImg) {
                lightboxImg.src = link.href || link.querySelector('img')?.src;
                lightbox.style.display = 'flex';
            }
        });
    });
    
    lightbox?.querySelector('[data-close]')?.addEventListener('click', () => lightbox.style.display = 'none');
    lightbox?.addEventListener('click', e => { if (e.target === lightbox) lightbox.style.display = 'none'; });
}