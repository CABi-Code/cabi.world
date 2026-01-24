// forms/handleForm.js

export function handleForm(formId, endpoint, opts = {}, csrf) {
    const form = document.getElementById(formId);
    if (!form) return;

    form.querySelectorAll('.form-input').forEach(input => {
        input.addEventListener('input', () => {
            input.classList.remove('error');
            input.closest('.form-group')?.querySelector('.form-error')?.remove();
        });
    });

    form.addEventListener('submit', async e => {
        e.preventDefault();
        const btn = form.querySelector('[type="submit"]');
        const originalText = btn?.innerHTML;
        if (btn) { btn.disabled = true; btn.textContent = '...'; }

        const data = Object.fromEntries(new FormData(form));
        
        if (opts.validate) {
            const errors = opts.validate(data);
            if (Object.keys(errors).length) {
                showErrors(form, errors);
                if (btn) { btn.disabled = false; btn.innerHTML = originalText; }
                return;
            }
        }

        try {
            const res = await fetch(endpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
                body: JSON.stringify(data)
            });
            const result = await res.json();

            if (res.ok && result.success) {
                if (result.redirect) location.href = result.redirect;
                else { showAlert(form, 'success', result.message || 'Сохранено!'); opts.onSuccess?.(result); }
            } else {
                if (result.errors) showErrors(form, result.errors);
                else showAlert(form, 'error', result.error || 'Ошибка');
            }
        } catch (err) {
            showAlert(form, 'error', 'Ошибка сети');
        }

        if (btn) { btn.disabled = false; btn.innerHTML = originalText; }
    });
}

export function showAlert(form, type, msg) {
    form.querySelector('.alert')?.remove();
    const alert = document.createElement('div');
    alert.className = `alert alert-${type}`;
    alert.textContent = msg;
    form.prepend(alert);
    setTimeout(() => alert.remove(), 5000);
}

export function showErrors(form, errors) {
    Object.entries(errors).forEach(([field, msg]) => {
        const input = form.querySelector(`[name="${field}"]`);
        if (input) {
            input.classList.add('error');
            const group = input.closest('.form-group');
            if (group && !group.querySelector('.form-error')) {
                const el = document.createElement('div');
                el.className = 'form-error';
                el.textContent = msg;
                group.appendChild(el);
            }
        } else showAlert(form, 'error', msg);
    });
}