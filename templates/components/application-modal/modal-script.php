(function() {
    const modalId = '<?= e($modalId) ?>';
    const modal = document.getElementById(modalId);
    if (!modal) return;
    
    const form = modal.querySelector('form');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    const isEdit = <?= $isEdit ? 'true' : 'false' ?>;
    const maxImages = 2;
    
    // Профильные контакты
    const profileContacts = {
        discord: '<?= e($userDiscord) ?>',
        telegram: '<?= e($userTelegram) ?>',
        vk: '<?= e($userVk) ?>'
    };
    
    // Хранилище выбранных файлов (новые)
    let selectedFiles = [];
    // Хранилище существующих изображений (уже загруженные)
    let existingImages = [];
    
    // === Закрытие модального окна ===
    modal.querySelectorAll('[data-close]').forEach(el => {
        el.addEventListener('click', () => {
            modal.style.display = 'none';
            resetForm();
        });
    });
    
    // === Счётчик символов ===
    const messageField = form.querySelector('.app-field-message');
    const charCounter = form.querySelector('.char-counter');
    if (messageField && charCounter) {
        const updateCounter = () => {
            charCounter.textContent = messageField.value.length;
        };
        messageField.addEventListener('input', updateCounter);
        updateCounter();
    }
    
    // === Переключение режима контактов ===
    const contactsModeRadios = form.querySelectorAll('.contacts-mode-radio');
    const defaultInfo = form.querySelector('.contacts-default-info');
    const customFields = form.querySelector('.contacts-custom-fields');
    
    contactsModeRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            const isDefault = this.value === 'default';
            if (defaultInfo) defaultInfo.style.display = isDefault ? '' : 'none';
            if (customFields) customFields.style.display = isDefault ? 'none' : '';
            
            // При переключении на "На выбор" - заполняем поля из профиля
            if (!isDefault) {
                const discordField = form.querySelector('.app-field-discord');
                const telegramField = form.querySelector('.app-field-telegram');
                const vkField = form.querySelector('.app-field-vk');
                
                if (discordField && !discordField.value) discordField.value = profileContacts.discord;
                if (telegramField && !telegramField.value) telegramField.value = profileContacts.telegram;
                if (vkField && !vkField.value) vkField.value = profileContacts.vk;
            }
        });
    });
    
    // === Загрузка изображений ===
    const imageInput = form.querySelector('.app-field-images');
    const previewContainer = document.getElementById(modalId + 'ImagesPreview');
    const uploadBtn = document.getElementById(modalId + 'ImageUploadBtn');
    
    if (imageInput) {
        imageInput.addEventListener('change', handleImageSelect);
    }
    
    function getTotalImagesCount() {
        return existingImages.length + selectedFiles.length;
    }
    
    function handleImageSelect(e) {
        const files = Array.from(e.target.files);
        
        files.forEach(file => {
            // Проверка лимита (учитываем существующие + новые)
            if (getTotalImagesCount() >= maxImages) {
                alert('Максимум ' + maxImages + ' изображения');
                return;
            }
            
            // Проверка типа
            if (!file.type.startsWith('image/')) {
                alert('Файл ' + file.name + ' не является изображением');
                return;
            }
            
            // Проверка размера (5 МБ)
            if (file.size > 5 * 1024 * 1024) {
                alert('Файл ' + file.name + ' слишком большой (максимум 5 МБ)');
                return;
            }
            
            selectedFiles.push(file);
            addNewImagePreview(file, selectedFiles.length - 1);
        });
        
        updateUploadButtonVisibility();
        imageInput.value = '';
    }
    
    // Превью для НОВЫХ файлов
    function addNewImagePreview(file, index) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const div = document.createElement('div');
            div.className = 'image-preview-item new-image';
            div.dataset.index = index;
            div.innerHTML = `
                <img src="${e.target.result}" alt="">
                <button type="button" class="image-preview-remove" title="Удалить">
                    <svg width="14" height="14"><use href="#icon-x"/></svg>
                </button>
            `;
            
            div.querySelector('.image-preview-remove').addEventListener('click', function() {
                const idx = parseInt(div.dataset.index);
                selectedFiles = selectedFiles.filter((_, i) => i !== idx);
                div.remove();
                // Обновляем индексы оставшихся новых изображений
                previewContainer.querySelectorAll('.image-preview-item.new-image').forEach((item, i) => {
                    item.dataset.index = i;
                });
                updateUploadButtonVisibility();
            });
            
            previewContainer.appendChild(div);
        };
        reader.readAsDataURL(file);
    }
    
    // Превью для СУЩЕСТВУЮЩИХ изображений (из БД)
    function addExistingImagePreview(imageData) {
        const div = document.createElement('div');
        div.className = 'image-preview-item existing-image';
        div.dataset.imageId = imageData.id;
        div.innerHTML = `
            <img src="${imageData.image_path}" alt="">
            <button type="button" class="image-preview-remove" title="Удалить">
                <svg width="14" height="14"><use href="#icon-x"/></svg>
            </button>
        `;
        
        div.querySelector('.image-preview-remove').addEventListener('click', async function() {
            const imageId = imageData.id;
            
            // Удаляем с сервера
            try {
                const res = await fetch('/api/application/delete-image', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': csrf
                    },
                    body: JSON.stringify({ image_id: imageId })
                });
                
                if (res.ok) {
                    existingImages = existingImages.filter(img => img.id !== imageId);
                    div.remove();
                    updateUploadButtonVisibility();
                } else {
                    alert('Не удалось удалить изображение');
                }
            } catch (err) {
                alert('Ошибка сети');
            }
        });
        
        previewContainer.appendChild(div);
    }
    
    function updateUploadButtonVisibility() {
        if (uploadBtn) {
            uploadBtn.style.display = getTotalImagesCount() >= maxImages ? 'none' : '';
        }
    }
    
    // === Очистка ошибок при вводе ===
    form.querySelectorAll('.form-input').forEach(input => {
        input.addEventListener('input', () => {
            input.classList.remove('error');
            input.closest('.form-group')?.querySelector('.form-error')?.remove();
        });
    });
    
    // === Отправка формы ===
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const btn = form.querySelector('[type="submit"]');
        const originalText = btn.textContent;
        btn.disabled = true;
        btn.textContent = '...';
        
        // Собираем данные формы
        const formData = new FormData(form);
        
        // Определяем режим контактов
        const contactsMode = form.querySelector('.contacts-mode-radio:checked')?.value || 'default';
        formData.set('contacts_mode', contactsMode);
        
        // Если режим "default" - не отправляем поля контактов
        if (contactsMode === 'default') {
            formData.delete('discord');
            formData.delete('telegram');
            formData.delete('vk');
        }
        
        // Удаляем старые файлы и добавляем только НОВЫЕ выбранные
        formData.delete('images[]');
        selectedFiles.forEach((file, i) => {
            formData.append('images[]', file);
        });
        
        // Валидация на клиенте
        const message = formData.get('message')?.trim();
        if (!message) {
            showFormError('message', 'Введите сообщение');
            btn.disabled = false;
            btn.textContent = originalText;
            return;
        }
        
        const relevantUntil = formData.get('relevant_until');
        if (!relevantUntil) {
            showFormError('relevant_until', 'Укажите дату актуальности');
            btn.disabled = false;
            btn.textContent = originalText;
            return;
        }
        
        // Проверка контактов при режиме "custom"
        if (contactsMode === 'custom') {
            const discord = formData.get('discord')?.trim();
            const telegram = formData.get('telegram')?.trim();
            const vk = formData.get('vk')?.trim();
            
            if (!discord && !telegram && !vk) {
                showFormError('discord', 'Укажите хотя бы один контакт');
                btn.disabled = false;
                btn.textContent = originalText;
                return;
            }
        }
        
        const endpoint = isEdit ? '/api/application/update' : '/api/modpack/apply';
        
        try {
            const res = await fetch(endpoint, {
                method: 'POST',
                headers: { 'X-CSRF-Token': csrf },
                body: formData
            });
            const result = await res.json();
            
            if (res.ok && result.success) {
                location.reload();
            } else {
                if (result.errors) {
                    Object.entries(result.errors).forEach(([field, msg]) => {
                        showFormError(field, msg);
                    });
                } else {
                    alert(result.error || 'Ошибка сохранения');
                }
                btn.disabled = false;
                btn.textContent = originalText;
            }
        } catch (err) {
            alert('Ошибка сети');
            btn.disabled = false;
            btn.textContent = originalText;
        }
    });
    
    function showFormError(field, msg) {
        const input = form.querySelector(`[name="${field}"]`);
        if (input) {
            input.classList.add('error');
            const group = input.closest('.form-group');
            if (group) {
                group.querySelector('.form-error')?.remove();
                const el = document.createElement('div');
                el.className = 'form-error';
                el.textContent = msg;
                group.appendChild(el);
            }
        }
    }
    
    function resetForm() {
        selectedFiles = [];
        existingImages = [];
        if (previewContainer) previewContainer.innerHTML = '';
        updateUploadButtonVisibility();
        form.querySelectorAll('.form-input').forEach(input => input.classList.remove('error'));
        form.querySelectorAll('.form-error').forEach(el => el.remove());
    }
    
    // === Глобальная функция для открытия модалки ===
    window.openApplicationModal = window.openApplicationModal || {};
    window.openApplicationModal[modalId] = function(appData = null) {
        resetForm();
        
        if (appData) {
            // Режим редактирования - заполняем поля
            form.querySelector('.app-field-id').value = appData.id || '';
            form.querySelector('.app-field-message').value = appData.message || '';
            form.querySelector('.app-field-relevant').value = appData.relevant_until || '<?= $defaultRelevantDate ?>';
            
            // Определяем режим контактов
            // Если contact_discord/telegram/vk равны null - значит режим "по умолчанию"
            const hasCustomContacts = appData.contact_discord !== null || 
                                      appData.contact_telegram !== null || 
                                      appData.contact_vk !== null;
            
            const modeRadio = form.querySelector(`.contacts-mode-radio[value="${hasCustomContacts ? 'custom' : 'default'}"]`);
            if (modeRadio) {
                modeRadio.checked = true;
                modeRadio.dispatchEvent(new Event('change'));
            }
            
            if (hasCustomContacts) {
                const discordField = form.querySelector('.app-field-discord');
                const telegramField = form.querySelector('.app-field-telegram');
                const vkField = form.querySelector('.app-field-vk');
                
                if (discordField) discordField.value = appData.contact_discord || '';
                if (telegramField) telegramField.value = appData.contact_telegram || '';
                if (vkField) vkField.value = appData.contact_vk || '';
            }
            
            // Загружаем существующие изображения
            if (appData.images && Array.isArray(appData.images)) {
                existingImages = appData.images;
                appData.images.forEach(img => {
                    addExistingImagePreview(img);
                });
                updateUploadButtonVisibility();
            }
        }
        
        // Обновляем счётчик символов
        if (messageField && charCounter) {
            charCounter.textContent = messageField.value.length;
        }
        
        modal.style.display = 'flex';
    };
})();
