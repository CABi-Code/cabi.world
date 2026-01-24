// theme.js
export function initTheme() {
	
    // Тёмная тема по умолчанию, если пользователь ничего не выбирал
    const savedTheme = localStorage.getItem('theme') || 'dark';
    document.documentElement.setAttribute('data-theme', savedTheme);
    
    const themeToggle = document.getElementById('themeToggle');
    if (themeToggle) {
        const btn = themeToggle.querySelector('.theme-btn');
        const options = themeToggle.querySelectorAll('.theme-option');
        
        options.forEach(opt => opt.classList.toggle('active', opt.dataset.theme === savedTheme));
        
        btn.addEventListener('click', () => themeToggle.classList.toggle('open'));
        
        options.forEach(opt => {
            opt.addEventListener('click', () => {
                const theme = opt.dataset.theme;
                document.documentElement.setAttribute('data-theme', theme);
                localStorage.setItem('theme', theme);
                options.forEach(o => o.classList.remove('active'));
                opt.classList.add('active');
                themeToggle.classList.remove('open');
            });
        });
        
        document.addEventListener('click', e => {
            if (!themeToggle.contains(e.target)) themeToggle.classList.remove('open');
        });
    }
}