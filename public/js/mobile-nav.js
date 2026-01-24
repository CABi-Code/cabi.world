// mobile-nav.js
export function initMobileNav() {
    const mobileNavToggle = document.getElementById('mobileNavToggle');
    const mobileNav = document.getElementById('mobileNav');
    
    if (mobileNavToggle && mobileNav) {
        mobileNavToggle.addEventListener('click', () => {
            mobileNav.classList.toggle('open');
        });
        
        document.addEventListener('click', e => {
            if (!mobileNavToggle.contains(e.target) && !mobileNav.contains(e.target)) {
                mobileNav.classList.remove('open');
            }
        });
        
        mobileNav.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', () => mobileNav.classList.remove('open'));
        });
    }
}