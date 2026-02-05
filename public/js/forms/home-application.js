// forms/home-application.js
// Форма подачи заявки на главной странице

import { openModpackSelector } from '/js/modpack-selector/index.js';
import { showAlert } from './handleForm.js';

export function initHomeApplication(csrf) {
    const form = document.getElementById('homeApplicationForm');
    if (!form) return;

    const state = {
        selectedModpack: null,
        selectedImage: null,
        attachmentsOpen: false
    };

    // Элементы
    const messageInput = form.querySelector('[name="message"]');
    const charCount = document.getElementById('appFormCharCount');
    const modpackBtn = document.getElementById('appFormModpackBtn');
    const modpackIdInput = document.getElementById('appFormModpackId');
    const modpackLabel = document.getElementById('appFormModpackLabel');
    const attachBtn = document.getElementById('appFormAttachBtn');
    const attachPanel = document.getElementById('appFormAttachments');
    const fileInput = document.getElementById('appFormFileInput');
    const fileBtn = document.getElementById('appFormFileBtn');
    const imagePreview = document.getElementById('appFormImagePreview');
    const imagePlaceholder = document.getElementById('appFormImagePlaceholder');
    const imageImg = document.getElementById('appFormImageImg');
    const imageRemove = document.getElementById('appFormImageRemove');
    const dateInput = document.getElementById('appFormDate');
    const folderSelect = document.getElementById('appFormFolderSelect');

    // Установить минимальную и максимальную дату
    if (dateInput) {
        const today = new Date();
        const maxDate = new Date();
        maxDate.setDate(today.getDate() + 31);
        dateInput.min = formatDate(today);
        dateInput.max = formatDate(maxDate);
    }

    // === Счётчик символов ===
    if (messageInput && charCount) {
        messageInput.addEventListener('input', () => {
            const len = messageInput.value.length;
            charCount.textContent = len;
            const wrapper = charCount.parentElement;
            wrapper.classList.remove('near-limit', 'at-limit');
            if (len >= 128) wrapper.classList.add('at-limit');
            else if (len >= 100) wrapper.classList.add('near-limit');
        });
    }

    // === Выбор модпака ===
    if (modpackBtn) {
        modpackBtn.addEventListener('click', () => {
            openModpackSelector((modpack) => {
                state.selectedModpack = modpack;
                modpackIdInput.value = modpack.id;

                // Обновляем кнопку
                if (modpack.icon_url) {
                    modpackLabel.innerHTML = '';
                    const icon = document.createElement('img');
                    icon.src = modpack.icon_url;
                    icon.className = 'modpack-icon';
                    icon.alt = '';
                    modpackBtn.querySelector('svg')?.replaceWith(icon);
                }
                modpackLabel.textContent = modpack.name;
                modpackBtn.classList.add('selected');
            });
        });
    }

    // === Панель вложений (скрепка) ===
    if (attachBtn && attachPanel) {
        attachBtn.addEventListener('click', () => {
            state.attachmentsOpen = !state.attachmentsOpen;
            attachBtn.classList.toggle('active', state.attachmentsOpen);
            if (state.attachmentsOpen) {
                attachPanel.style.display = 'block';
                // Триггер reflow для анимации
                attachPanel.offsetHeight;
                attachPanel.classList.add('open');
                // Загружаем папки пользователя
                loadUserFolders();
            } else {
                attachPanel.classList.remove('open');
                setTimeout(() => {
                    attachPanel.style.display = 'none';
                }, 300);
            }
        });
    }

    // === Загрузка картинки ===
    if (fileBtn && fileInput) {
        fileBtn.addEventListener('click', () => fileInput.click());
    }

    // Клик на превью для быстрого выбора
    if (imagePreview && fileInput) {
        imagePreview.addEventListener('click', (e) => {
            if (e.target === imageRemove || imageRemove?.contains(e.target)) return;
            if (!state.selectedImage) {
                fileInput.click();
            }
        });
    }

    if (fileInput) {
        fileInput.addEventListener('change', () => {
            const file = fileInput.files[0];
            if (!file) return;

            // Валидация
            if (file.size > 5 * 1024 * 1024) {
                showAlert(form, 'error', 'Файл слишком большой (максимум 5 МБ)');
                fileInput.value = '';
                return;
            }

            const allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!allowed.includes(file.type)) {
                showAlert(form, 'error', 'Недопустимый тип файла');
                fileInput.value = '';
                return;
            }

            state.selectedImage = file;

            // Показываем превью
            const reader = new FileReader();
            reader.onload = (e) => {
                imageImg.src = e.target.result;
                imageImg.style.display = 'block';
                imagePlaceholder.style.display = 'none';
                imageRemove.style.display = 'flex';
                imagePreview.style.borderStyle = 'solid';
                imagePreview.style.borderColor = 'var(--primary)';
            };
            reader.readAsDataURL(file);
        });
    }

    // Удаление картинки
    if (imageRemove) {
        imageRemove.addEventListener('click', (e) => {
            e.stopPropagation();
            clearImage();
        });
    }

    function clearImage() {
        state.selectedImage = null;
        if (fileInput) fileInput.value = '';
        imageImg.style.display = 'none';
        imageImg.src = '';
        imagePlaceholder.style.display = 'flex';
        imageRemove.style.display = 'none';
        imagePreview.style.borderStyle = 'dashed';
        imagePreview.style.borderColor = '';
    }

    // === Загрузка папок пользователя ===
    async function loadUserFolders() {
        if (!folderSelect || folderSelect.options.length > 1) return;

        try {
            const res = await fetch('/api/user-folder/structure', {
                headers: { 'X-CSRF-Token': csrf }
            });
            const data = await res.json();
            if (data.structure) {
                renderFolderOptions(data.structure, folderSelect, 0);
            }
        } catch (err) {
            console.error('Failed to load folders:', err);
        }
    }

    function renderFolderOptions(items, select, depth) {
        for (const item of items) {
            if (item.item_type === 'folder') {
                const opt = document.createElement('option');
                opt.value = item.id;
                opt.textContent = '\u00A0\u00A0'.repeat(depth) + item.name;
                select.appendChild(opt);
                if (item.children?.length) {
                    renderFolderOptions(item.children, select, depth + 1);
                }
            }
        }
    }

    // === Отправка формы ===
    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const submitBtn = document.getElementById('appFormSubmit');
        const originalText = submitBtn?.textContent;
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = '...';
        }

        // Валидация
        const message = messageInput?.value?.trim();
        if (!message) {
            showAlert(form, 'error', 'Введите сообщение');
            restoreBtn();
            return;
        }

        if (!state.selectedModpack && !modpackIdInput.value) {
            showAlert(form, 'error', 'Выберите модпак');
            restoreBtn();
            return;
        }

        // Собираем FormData для multipart/form-data (поддержка файлов)
        const fd = new FormData();
        fd.append('message', message);
        fd.append('contacts_mode', 'default');

        // Отправляем полные данные модпака для разрешения в БД
        if (state.selectedModpack) {
            fd.append('modpack_platform', state.selectedModpack.platform || '');
            fd.append('modpack_external_id', state.selectedModpack.id || '');
            fd.append('modpack_slug', state.selectedModpack.slug || '');
            fd.append('modpack_name', state.selectedModpack.name || '');
            fd.append('modpack_icon_url', state.selectedModpack.icon_url || '');
            fd.append('modpack_downloads', state.selectedModpack.downloads || 0);
        } else {
            fd.append('modpack_id', modpackIdInput.value);
        }

        if (dateInput?.value) {
            fd.append('relevant_until', dateInput.value);
        }

        if (folderSelect?.value) {
            fd.append('folder_item_id', folderSelect.value);
        }

        if (state.selectedImage) {
            fd.append('images', state.selectedImage);
        }

        try {
            const res = await fetch('/api/modpack/apply', {
                method: 'POST',
                headers: { 'X-CSRF-Token': csrf },
                body: fd
            });

            const result = await res.json();

            if (res.ok && result.success) {
                showAlert(form, 'success', 'Заявка отправлена!');
                // Сброс формы
                form.reset();
                clearImage();
                state.selectedModpack = null;
                modpackIdInput.value = '';
                modpackLabel.textContent = 'Модпак';
                modpackBtn.classList.remove('selected');
                // Восстановить иконку модпака если заменили на img
                const existingIcon = modpackBtn.querySelector('.modpack-icon');
                if (existingIcon) {
                    const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
                    svg.setAttribute('width', '14');
                    svg.setAttribute('height', '14');
                    const use = document.createElementNS('http://www.w3.org/2000/svg', 'use');
                    use.setAttributeNS('http://www.w3.org/1999/xlink', 'href', '#icon-package');
                    svg.appendChild(use);
                    existingIcon.replaceWith(svg);
                }
                if (charCount) charCount.textContent = '0';

                setTimeout(() => location.reload(), 800);
            } else {
                if (result.errors) {
                    const firstError = Object.values(result.errors)[0];
                    showAlert(form, 'error', firstError);
                } else {
                    showAlert(form, 'error', result.error || 'Ошибка отправки');
                }
            }
        } catch (err) {
            showAlert(form, 'error', 'Ошибка сети');
        }

        restoreBtn();

        function restoreBtn() {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            }
        }
    });
}

function formatDate(d) {
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${y}-${m}-${day}`;
}
