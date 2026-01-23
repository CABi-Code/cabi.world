/**
 * CABI.WORLD - Main JS
 */
document.addEventListener('DOMContentLoaded', () => {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

    // === Theme Toggle ===
    // Тёмная тема по умолчанию
    const savedTheme = localStorage.getItem('theme') || 'dark';
    document.documentElement.setAttribute('data-theme', savedTheme);
    
    const themeToggle = document.getElementById('themeToggle');
    if (themeToggle) {
        const btn = themeToggle.querySelector('.theme-btn');
        const options = themeToggle.querySelectorAll('.theme-option');
        
        // Mark active
        options.forEach(opt => {
            opt.classList.toggle('active', opt.dataset.theme === savedTheme);
        });
        
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

    // === Password Toggle ===
    document.querySelectorAll('.password-toggle-btn, [data-toggle="password"]').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const wrapper = this.closest('.password-toggle');
            const input = wrapper?.querySelector('input');
            if (!input) return;
            
            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            
            // Update icon
            const useEl = this.querySelector('use');
            if (useEl) {
                useEl.setAttribute('href', isPassword ? '#icon-eye-off' : '#icon-eye');
            }
        });
    });

    // === View Toggle ===
    const savedView = getCookie('view_mode') || 'grid';
    document.querySelectorAll('[data-view]').forEach(el => {
        if (!el.classList.contains('view-btn')) el.setAttribute('data-view', savedView);
    });
    
    document.querySelectorAll('.view-btn').forEach(btn => {
        if (btn.dataset.view === savedView) btn.classList.add('active');
        btn.addEventListener('click', () => {
            const view = btn.dataset.view;
            setCookie('view_mode', view, 365);
            document.querySelectorAll('.view-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            document.querySelectorAll('[data-view]:not(.view-btn)').forEach(el => {
                el.setAttribute('data-view', view);
            });
        });
    });

    // === AJAX Sort for Home Page ===
    const feedSortSelect = document.getElementById('feedSortSelect');
    const feedContainer = document.getElementById('feedContainer');
    
    if (feedSortSelect && feedContainer) {
        feedSortSelect.addEventListener('change', async function() {
            const sort = this.value;
            const page = new URLSearchParams(window.location.search).get('page') || 1;
            
            // Update URL without reload
            const url = new URL(window.location);
            url.searchParams.set('sort', sort);
            url.searchParams.set('page', '1');
            window.history.pushState({}, '', url);
            
            // Show loading
            feedContainer.style.opacity = '0.5';
            
            try {
                const response = await fetch(`/api/feed?sort=${sort}&page=1`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await response.json();
                
                if (data.success && data.html) {
                    feedContainer.innerHTML = data.html;
                    // Reinitialize lightbox
                    initLightbox();
                }
            } catch (err) {
                console.error('Feed load error:', err);
            }
            
            feedContainer.style.opacity = '1';
        });
    }

    // === Notifications ===
    const notifMenu = document.getElementById('notifMenu');
    if (notifMenu) {
        const btn = notifMenu.querySelector('.notif-btn');
        const list = document.getElementById('notifList');
        
        btn.addEventListener('click', async () => {
            const isOpen = notifMenu.classList.toggle('open');
            if (isOpen && list) {
                try {
                    const res = await fetch('/api/notifications', {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    const data = await res.json();
                    if (data.notifications?.length) {
                        list.innerHTML = data.notifications.map(n => `
                            <a href="${n.link || '#'}" class="notif-item ${n.is_read ? '' : 'unread'}">
                                <div style="font-weight:500;font-size:0.875rem;margin-bottom:0.125rem;">${esc(n.title)}</div>
                                ${n.message ? `<div style="font-size:0.8125rem;color:var(--text-secondary)">${esc(n.message)}</div>` : ''}
                            </a>
                        `).join('');
                    } else {
                        list.innerHTML = '<div class="notif-empty">Нет уведомлений</div>';
                    }
                } catch (e) {
                    list.innerHTML = '<div class="notif-empty">Ошибка загрузки</div>';
                }
            }
        });
        
        document.getElementById('markAllRead')?.addEventListener('click', async () => {
            await fetch('/api/notifications/read', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
                body: JSON.stringify({})
            });
            document.querySelectorAll('.notif-item.unread').forEach(el => el.classList.remove('unread'));
            document.querySelector('.notif-badge')?.remove();
        });
        
        document.addEventListener('click', e => {
            if (!notifMenu.contains(e.target)) notifMenu.classList.remove('open');
        });
    }

    // === Forms ===
    const handleForm = (formId, endpoint, opts = {}) => {
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
    };

    const showErrors = (form, errors) => {
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
    };

    const showAlert = (form, type, msg) => {
        form.querySelector('.alert')?.remove();
        const alert = document.createElement('div');
        alert.className = `alert alert-${type}`;
        alert.textContent = msg;
        form.prepend(alert);
        setTimeout(() => alert.remove(), 5000);
    };

    handleForm('loginForm', '/api/auth/login', {
        validate: d => {
            const e = {};
            if (!d.login?.trim()) e.login = 'Введите логин';
            if (!d.password) e.password = 'Введите пароль';
            return e;
        }
    });

    handleForm('registerForm', '/api/auth/register', {
        validate: d => {
            const e = {};
            if (!d.login?.trim()) e.login = 'Введите логин';
            else if (d.login.length < 3) e.login = 'Минимум 3 символа';
            if (!d.email?.trim()) e.email = 'Введите email';
            else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(d.email)) e.email = 'Некорректный email';
            if (!d.password) e.password = 'Введите пароль';
            else if (d.password.length < 8) e.password = 'Минимум 8 символов';
            if (d.password !== d.password_confirm) e.password_confirm = 'Пароли не совпадают';
            if (!d.username?.trim()) e.username = 'Введите имя';
            return e;
        }
    });

    handleForm('profileForm', '/api/user/update');
    handleForm('contactsForm', '/api/user/update');
    handleForm('applicationForm', '/api/modpack/apply', {
        validate: d => {
            const e = {};
            if (!d.message?.trim()) e.message = 'Введите сообщение';
            if (!d.discord && !d.telegram && !d.vk) e.discord = 'Укажите контакт';
            return e;
        },
        onSuccess: () => setTimeout(() => location.reload(), 500)
    });
    handleForm('editAppForm', '/api/application/update', { onSuccess: () => location.reload() });
    handleForm('editMyAppForm', '/api/application/update', { onSuccess: () => location.reload() });

    // === Image Upload ===
    let currentUploadType = null;
    let currentFile = null;
    let cropData = { x: 0, y: 0, scale: 1 };
    
    const imgEditorModal = document.getElementById('imgEditorModal');
    const editorPreview = document.getElementById('editorPreview');
    const zoomRange = document.getElementById('zoomRange');
    
    const setupUpload = (triggerId, inputId, type) => {
        const trigger = document.getElementById(triggerId);
        const input = document.getElementById(inputId);
        if (!trigger || !input) return;
        
        trigger.addEventListener('click', () => input.click());
        
        input.addEventListener('change', e => {
            const file = e.target.files[0];
            if (!file || !file.type.startsWith('image/')) return;
            
            currentFile = file;
            currentUploadType = type;
            cropData = { x: 0, y: 0, scale: 1 };
            
            const reader = new FileReader();
            reader.onload = ev => {
                if (editorPreview && imgEditorModal) {
                    document.getElementById('editorTitle').textContent = 
                        type === 'avatar' ? 'Редактировать аватар' : 'Редактировать баннер';
                    
                    editorPreview.innerHTML = `<img src="${ev.target.result}" id="cropImg" style="transform-origin:center;cursor:move;">`;
                    if (zoomRange) zoomRange.value = 1;
                    imgEditorModal.style.display = 'flex';
                    
                    setupCrop();
                }
            };
            reader.readAsDataURL(file);
            input.value = '';
        });
    };
    
    const setupCrop = () => {
        const img = document.getElementById('cropImg');
        if (!img) return;
        
        let isDragging = false;
        let startX, startY;
        
        img.addEventListener('mousedown', e => {
            isDragging = true;
            startX = e.clientX - cropData.x;
            startY = e.clientY - cropData.y;
        });
        
        document.addEventListener('mousemove', e => {
            if (!isDragging) return;
            cropData.x = e.clientX - startX;
            cropData.y = e.clientY - startY;
            updateCropTransform();
        });
        
        document.addEventListener('mouseup', () => isDragging = false);
        
        if (zoomRange) {
            zoomRange.addEventListener('input', () => {
                cropData.scale = parseFloat(zoomRange.value);
                updateCropTransform();
            });
        }
    };
    
    const updateCropTransform = () => {
        const img = document.getElementById('cropImg');
        if (img) {
            img.style.transform = `translate(${cropData.x}px, ${cropData.y}px) scale(${cropData.scale})`;
        }
    };
    
    document.getElementById('saveImgBtn')?.addEventListener('click', async () => {
        if (!currentFile) return;
        
        const btn = document.getElementById('saveImgBtn');
        btn.disabled = true;
        btn.textContent = 'Загрузка...';
        
        const formData = new FormData();
        formData.append(currentUploadType, currentFile);
        formData.append('crop_x', cropData.x);
        formData.append('crop_y', cropData.y);
        formData.append('crop_scale', cropData.scale);
        
        try {
            const endpoint = currentUploadType === 'avatar' ? '/api/user/avatar' : '/api/user/banner';
            const res = await fetch(endpoint, {
                method: 'POST',
                headers: { 'X-CSRF-Token': csrf },
                body: formData
            });
            const data = await res.json();
            
            if (data.success) {
                location.reload();
            } else {
                alert(data.error || 'Ошибка загрузки');
                btn.disabled = false;
                btn.textContent = 'Сохранить';
            }
        } catch (err) {
            alert('Ошибка сети');
            btn.disabled = false;
            btn.textContent = 'Сохранить';
        }
    });
    
    setupUpload('avatarUpload', 'avatarInput', 'avatar');
    setupUpload('bannerUpload', 'bannerInput', 'banner');

    // === Delete Avatar/Banner ===
    document.getElementById('deleteAvatar')?.addEventListener('click', async () => {
        if (!confirm('Удалить аватар?')) return;
        try {
            const res = await fetch('/api/user/avatar/delete', {
                method: 'POST',
                headers: { 'X-CSRF-Token': csrf }
            });
            const data = await res.json();
            if (data.success) location.reload();
            else alert(data.error || 'Ошибка');
        } catch (err) {
            alert('Ошибка сети');
        }
    });

    document.getElementById('deleteBanner')?.addEventListener('click', async () => {
        if (!confirm('Удалить баннер?')) return;
        try {
            const res = await fetch('/api/user/banner/delete', {
                method: 'POST',
                headers: { 'X-CSRF-Token': csrf }
            });
            const data = await res.json();
            if (data.success) location.reload();
            else alert(data.error || 'Ошибка');
        } catch (err) {
            alert('Ошибка сети');
        }
    });

    // === Save Colors ===
    document.getElementById('saveColors')?.addEventListener('click', async () => {
        const btn = document.getElementById('saveColors');
        btn.disabled = true;
        btn.textContent = '...';
        
        const data = {
            banner_bg_value: document.querySelector('[name="banner_color1"]')?.value + ',' + 
                            document.querySelector('[name="banner_color2"]')?.value,
            avatar_bg_value: document.querySelector('[name="avatar_color1"]')?.value + ',' + 
                            document.querySelector('[name="avatar_color2"]')?.value
        };
        
        try {
            await fetch('/api/user/update', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
                body: JSON.stringify(data)
            });
            btn.textContent = 'Сохранено!';
            setTimeout(() => { btn.disabled = false; btn.textContent = 'Сохранить цвета'; }, 2000);
        } catch (err) {
            btn.disabled = false;
            btn.textContent = 'Ошибка';
        }
    });

    // === Modals ===
    document.querySelectorAll('[data-modal]').forEach(trigger => {
        trigger.addEventListener('click', () => {
            const modal = document.getElementById(trigger.dataset.modal);
            if (modal) modal.style.display = 'flex';
        });
    });

    document.querySelectorAll('.modal [data-close]').forEach(el => {
        el.addEventListener('click', () => el.closest('.modal').style.display = 'none');
    });

    // === Lightbox ===
    function initLightbox() {
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
        
        lightbox?.querySelector('[data-close]')?.addEventListener('click', () => {
            lightbox.style.display = 'none';
        });
        
        lightbox?.addEventListener('click', e => {
            if (e.target === lightbox) lightbox.style.display = 'none';
        });
    }
    
    initLightbox();

    // === Helpers ===
    function esc(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }
    
    function getCookie(name) {
        const match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
        return match ? match[2] : null;
    }
    
    function setCookie(name, value, days) {
        const date = new Date();
        date.setTime(date.getTime() + days * 24 * 60 * 60 * 1000);
        document.cookie = name + '=' + value + ';expires=' + date.toUTCString() + ';path=/';
    }
});