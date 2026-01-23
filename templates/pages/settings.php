<?php /** @var array $user */ ?>

<div class="container-sm">
    <h1 style="font-size:1.25rem;margin-bottom:1.25rem;">Настройки</h1>
    
    <!-- Аватар и баннер -->
    <div class="settings-card">
        <h3>Изображения профиля</h3>
        
        <div class="form-group">
            <label class="form-label">Аватар</label>
            <div class="upload-box" id="avatarUpload">
                <div class="upload-preview circle">
                    <?php if ($user['avatar']): ?>
                        <img src="<?= e($user['avatar']) ?>" alt="">
                    <?php else: ?>
                        <svg width="24" height="24"><use href="#icon-camera"/></svg>
                    <?php endif; ?>
                </div>
                <div class="upload-text">
                    <p><?= $user['avatar'] ? 'Изменить аватар' : 'Загрузить аватар' ?></p>
                    <span>JPG, PNG до 5MB</span>
                </div>
            </div>
            <input type="file" id="avatarInput" accept="image/*" hidden>
            <?php if ($user['avatar']): ?>
                <div class="upload-actions">
                    <button type="button" class="btn btn-ghost btn-sm" style="color:var(--danger)" id="deleteAvatar">
                        <svg width="14" height="14"><use href="#icon-trash"/></svg>
                        Удалить аватар
                    </button>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="form-group" style="margin-bottom:0;">
            <label class="form-label">Баннер</label>
            <div class="upload-box" id="bannerUpload">
                <div class="upload-preview" style="width:100px;height:40px;">
                    <?php if ($user['banner']): ?>
                        <img src="<?= e($user['banner']) ?>" alt="">
                    <?php else: ?>
                        <svg width="24" height="24"><use href="#icon-image"/></svg>
                    <?php endif; ?>
                </div>
                <div class="upload-text">
                    <p><?= $user['banner'] ? 'Изменить баннер' : 'Загрузить баннер' ?></p>
                    <span>Рекомендуется 1200×300</span>
                </div>
            </div>
            <input type="file" id="bannerInput" accept="image/*" hidden>
            <?php if ($user['banner']): ?>
                <div class="upload-actions">
                    <button type="button" class="btn btn-ghost btn-sm" style="color:var(--danger)" id="deleteBanner">
                        <svg width="14" height="14"><use href="#icon-trash"/></svg>
                        Удалить баннер
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Цвета профиля -->
    <div class="settings-card">
        <h3>Цвета профиля</h3>
        <p class="form-hint" style="margin-top:-0.5rem;margin-bottom:0.75rem;">Используются когда нет изображения</p>
        
        <div class="form-group">
            <label class="form-label">Фон баннера</label>
            <div class="color-row">
                <input type="color" class="color-input" name="banner_color1" value="<?= e(explode(',', $user['banner_bg_value'] ?? '#3b82f6')[0]) ?>">
                <input type="color" class="color-input" name="banner_color2" value="<?= e(explode(',', $user['banner_bg_value'] ?? '#3b82f6,#8b5cf6')[1] ?? '#8b5cf6') ?>">
                <span style="font-size:0.8125rem;color:var(--text-muted)">Градиент</span>
            </div>
        </div>
        
        <div class="form-group" style="margin-bottom:0;">
            <label class="form-label">Фон аватара</label>
            <div class="color-row">
                <input type="color" class="color-input" name="avatar_color1" value="<?= e(explode(',', $user['avatar_bg_value'] ?? '#3b82f6')[0]) ?>">
                <input type="color" class="color-input" name="avatar_color2" value="<?= e(explode(',', $user['avatar_bg_value'] ?? '#3b82f6,#8b5cf6')[1] ?? '#8b5cf6') ?>">
                <span style="font-size:0.8125rem;color:var(--text-muted)">Градиент</span>
            </div>
        </div>
        
        <button type="button" class="btn btn-primary btn-sm" id="saveColors" style="margin-top:1rem;">Сохранить цвета</button>
    </div>
    
    <!-- Профиль -->
    <div class="settings-card">
        <h3>Профиль</h3>
        <form id="profileForm">
            <div class="form-group">
                <label class="form-label">Логин</label>
                <div class="input-with-prefix">
                    <span class="input-prefix">@</span>
                    <input type="text" class="form-input" value="<?= e($user['login']) ?>" disabled style="cursor:not-allowed;">
                </div>
                <div class="form-hint">Логин нельзя изменить</div>
            </div>
            <div class="form-group">
                <label class="form-label">Имя</label>
                <input type="text" name="username" class="form-input" value="<?= e($user['username']) ?>">
            </div>
            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label">О себе</label>
                <textarea name="bio" class="form-input" rows="2"><?= e($user['bio'] ?? '') ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary btn-sm" style="margin-top:1rem;">Сохранить</button>
        </form>
    </div>
    
    <!-- Контакты -->
    <div class="settings-card">
        <h3>Контакты</h3>
        <form id="contactsForm">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Discord</label>
                    <input type="text" name="discord" class="form-input" value="<?= e($user['discord'] ?? '') ?>" placeholder="username">
                </div>
                <div class="form-group">
                    <label class="form-label">Telegram</label>
                    <input type="text" name="telegram" class="form-input" value="<?= e($user['telegram'] ?? '') ?>" placeholder="@username">
                </div>
                <div class="form-group">
                    <label class="form-label">VK</label>
                    <input type="text" name="vk" class="form-input" value="<?= e($user['vk'] ?? '') ?>" placeholder="id">
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-sm">Сохранить</button>
        </form>
    </div>
    
    <!-- Пароль -->
    <div class="settings-card">
        <h3>Смена пароля</h3>
        <form id="passwordForm">
            <div class="form-group">
                <label class="form-label">Текущий пароль</label>
                <div class="password-toggle">
                    <input type="password" name="current_password" class="form-input">
                    <button type="button" class="password-toggle-btn" data-toggle="password">
                        <svg width="18" height="18"><use href="#icon-eye"/></svg>
                    </button>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Новый пароль</label>
                    <div class="password-toggle">
                        <input type="password" name="new_password" class="form-input">
                        <button type="button" class="password-toggle-btn" data-toggle="password">
                            <svg width="18" height="18"><use href="#icon-eye"/></svg>
                        </button>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Подтверждение</label>
                    <div class="password-toggle">
                        <input type="password" name="new_password_confirm" class="form-input">
                        <button type="button" class="password-toggle-btn" data-toggle="password">
                            <svg width="18" height="18"><use href="#icon-eye"/></svg>
                        </button>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-sm">Изменить</button>
        </form>
    </div>
</div>

<!-- Модальное окно редактора изображений -->
<div id="imgEditorModal" class="modal" style="display:none;">
    <div class="modal-overlay" data-close></div>
    <div class="modal-content">
        <h3 id="editorTitle">Редактировать</h3>
        <div class="img-editor">
            <div class="img-preview" id="editorPreview"></div>
            <div class="img-controls">
                <div class="zoom-range">
                    <svg width="14" height="14"><use href="#icon-zoom-out"/></svg>
                    <input type="range" id="zoomRange" min="1" max="3" step="0.05" value="1">
                    <svg width="14" height="14"><use href="#icon-zoom-in"/></svg>
                </div>
            </div>
        </div>
        <div class="modal-actions">
            <button type="button" class="btn btn-secondary btn-sm" data-close>Отмена</button>
            <button type="button" class="btn btn-primary btn-sm" id="saveImgBtn">Сохранить</button>
        </div>
    </div>
</div>