// password-toggle.js
export function initPasswordToggle() {
    document.querySelectorAll('.password-toggle-btn, [data-toggle="password"]').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const wrapper = this.closest('.password-toggle');
            const input = wrapper?.querySelector('input');
            if (!input) return;
            
            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            
            const useEl = this.querySelector('use');
            if (useEl) useEl.setAttribute('href', isPassword ? '#icon-eye-off' : '#icon-eye');
        });
    });
}