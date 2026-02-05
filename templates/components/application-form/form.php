<?php
/**
 * Форма подачи заявки на главной странице
 *
 * @var array|null $user - текущий пользователь
 */
?>

<?php if ($user): ?>
<div class="app-form-wrapper" id="appFormWrapper">
    <form id="homeApplicationForm" enctype="multipart/form-data">
        <!-- Верхняя часть: текст слева, картинка справа -->
        <div class="app-form-top">
            <div class="app-form-text">
                <textarea
                    name="message"
                    class="form-input app-form-message"
                    placeholder="Опиши кого ищешь..."
                    maxlength="128"
                    rows="3"
                ></textarea>
                <div class="app-form-charcount">
                    <span id="appFormCharCount">0</span>/128
                </div>
            </div>
            <div class="app-form-image-preview" id="appFormImagePreview">
                <div class="app-form-image-placeholder" id="appFormImagePlaceholder">
                    <svg width="24" height="24"><use href="#icon-image"/></svg>
                </div>
                <img src="" alt="" class="app-form-image-img" id="appFormImageImg" style="display:none;">
                <button type="button" class="app-form-image-remove" id="appFormImageRemove" style="display:none;">
                    <svg width="14" height="14"><use href="#icon-x"/></svg>
                </button>
            </div>
        </div>

        <!-- Контакты -->
        <div class="app-form-contacts">
            <div class="app-form-contacts-info">
                <?php
                $hasContacts = !empty($user['discord']) || !empty($user['telegram']) || !empty($user['vk']);
                if ($hasContacts): ?>
                    <span class="app-form-contacts-label">Контакты из профиля:</span>
                    <?php if (!empty($user['discord'])): ?>
                        <span class="contact-tag">
                            <svg width="12" height="12"><use href="#icon-discord"/></svg>
                            <?= e($user['discord']) ?>
                        </span>
                    <?php endif; ?>
                    <?php if (!empty($user['telegram'])): ?>
                        <span class="contact-tag">
                            <svg width="12" height="12"><use href="#icon-telegram"/></svg>
                            <?= e($user['telegram']) ?>
                        </span>
                    <?php endif; ?>
                    <?php if (!empty($user['vk'])): ?>
                        <span class="contact-tag">
                            <svg width="12" height="12"><use href="#icon-vk"/></svg>
                            <?= e($user['vk']) ?>
                        </span>
                    <?php endif; ?>
                <?php else: ?>
                    <span class="app-form-contacts-warn">
                        Добавьте контакты в <a href="/settings">настройках профиля</a>
                    </span>
                <?php endif; ?>
            </div>
            <input type="hidden" name="contacts_mode" value="default">
        </div>

        <!-- Нижняя панель: скрепка слева, модпак + дата справа -->
        <div class="app-form-bottom">
            <div class="app-form-bottom-left">
                <button type="button" class="app-form-attach-btn" id="appFormAttachBtn" title="Прикрепить">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/>
                    </svg>
                </button>
            </div>
            <div class="app-form-bottom-right">
                <!-- Выбранный модпак -->
                <button type="button" class="app-form-modpack-btn" id="appFormModpackBtn">
                    <svg width="14" height="14"><use href="#icon-package"/></svg>
                    <span id="appFormModpackLabel">Модпак</span>
                </button>
                <input type="hidden" name="modpack_id" id="appFormModpackId" value="">

                <!-- Дата актуальности -->
                <div class="app-form-date-wrapper">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
                    </svg>
                    <input type="date" name="relevant_until" class="app-form-date-input" id="appFormDate" title="Дата актуальности">
                </div>

                <!-- Кнопка отправки -->
                <button type="submit" class="btn btn-primary btn-sm app-form-submit" id="appFormSubmit">
                    Отправить
                </button>
            </div>
        </div>

        <!-- Выдвигающаяся панель вложений (картинка + папка) -->
        <div class="app-form-attachments" id="appFormAttachments" style="display:none;">
            <div class="app-form-attachments-inner">
                <!-- Загрузка картинки -->
                <div class="app-form-attachment-section">
                    <label class="app-form-attachment-label">
                        <svg width="16" height="16"><use href="#icon-image"/></svg>
                        Картинка
                    </label>
                    <div class="app-form-file-drop" id="appFormFileDrop">
                        <input type="file" name="images" accept="image/jpeg,image/png,image/gif,image/webp" id="appFormFileInput" style="display:none;">
                        <button type="button" class="btn btn-secondary btn-sm" id="appFormFileBtn">Выбрать файл</button>
                        <span class="app-form-file-hint">JPG, PNG, GIF, WebP (макс. 5 МБ)</span>
                    </div>
                </div>

                <!-- Прикрепление папки из профиля -->
                <div class="app-form-attachment-section">
                    <label class="app-form-attachment-label">
                        <svg width="16" height="16"><use href="#icon-folder"/></svg>
                        Папка из профиля
                    </label>
                    <div class="app-form-folder-select">
                        <select name="folder_item_id" id="appFormFolderSelect" class="form-input app-form-folder-input">
                            <option value="">Не прикреплять</option>
                        </select>
                        <span class="app-form-file-hint">Выберите папку из «Моей папки»</span>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
<?php else: ?>
<div class="app-form-wrapper app-form-guest">
    <div class="app-form-guest-text">
        <svg width="20" height="20"><use href="#icon-user"/></svg>
        <span><a href="/login">Войдите</a> или <a href="/register">зарегистрируйтесь</a>, чтобы оставить заявку</span>
    </div>
</div>
<?php endif; ?>
