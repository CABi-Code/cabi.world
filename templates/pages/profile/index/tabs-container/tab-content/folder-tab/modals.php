<?php
/**
 * Модальные окна для "Моей папки"
 */
?>

<!-- Модалка создания -->
<div class="modal" id="folderCreateModal" style="display:none;">
    <div class="modal-backdrop" data-modal-close></div>
    <div class="modal-content modal-sm">
        <div class="modal-header">
            <h3 class="modal-title">Создать</h3>
            <button class="modal-close" data-modal-close>&times;</button>
        </div>
		<div class="modal-body">
			<div class="create-options">
				<?php foreach ($iconMap as $type => $data): ?>
					<button class="create-option" onclick="selectCreateType('<?= e($type) ?>')">
						<svg width="24" height="24" style="color: <?= e($data['color']) ?>">
							<use href="#icon-<?= e($data['icon']) ?>"/>
						</svg>
						<div class="create-option-content">
							<span><?= e($data['label']) ?></span>
							<p><?= e($data['descriptions'] ?? '') ?></p>
						</div>
					</button>
				<?php endforeach; ?>
			</div>
		</div>
    </div>
</div>

<!-- Модалка ввода названия -->
<div class="modal" id="folderNameModal" style="display:none;">
    <div class="modal-backdrop" data-modal-close></div>
    <div class="modal-content modal-sm">
        <div class="modal-header">
            <h3 class="modal-title" id="nameModalTitle">Название</h3>
            <button class="modal-close" data-modal-close>&times;</button>
        </div>
        <div class="modal-body">
            <form id="folderNameForm">
                <input type="hidden" name="parent_id" id="nameFormParentId">
                <input type="hidden" name="type" id="nameFormType">
                
                <div class="form-group">
                    <label class="form-label">Название</label>
                    <input type="text" name="name" class="form-input" id="nameFormInput" maxlength="100" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Описание <span class="text-muted">(необязательно)</span></label>
                    <textarea name="description" class="form-input" rows="2" id="nameFormDescription"></textarea>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-ghost" data-modal-close>Отмена</button>
                    <button type="submit" class="btn btn-primary">Создать</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Модалка настроек -->
<div class="modal" id="folderSettingsModal" style="display:none;">
    <div class="modal-backdrop" data-modal-close></div>
    <div class="modal-content modal-sm">
        <div class="modal-header">
            <h3 class="modal-title" id="settingsModalTitle">Настройки</h3>
            <button class="modal-close" data-modal-close>&times;</button>
        </div>
        <div class="modal-body">
            <form id="folderSettingsForm">
                <input type="hidden" name="id" id="settingsFormId">
                
                <div class="form-group">
                    <label class="form-label">Название</label>
                    <input type="text" name="name" class="form-input" id="settingsFormName" maxlength="100">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Описание</label>
                    <textarea name="description" class="form-input" rows="2" id="settingsFormDescription"></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Цвет иконки</label>
                    <input type="color" name="color" class="form-input form-color" id="settingsFormColor" value="#3b82f6">
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-danger btn-sm" onclick="deleteItem()">
                        <svg width="14" height="14"><use href="#icon-trash"/></svg>
                        Удалить
                    </button>
                    <div style="flex:1"></div>
                    <button type="button" class="btn btn-ghost" data-modal-close>Отмена</button>
                    <button type="submit" class="btn btn-primary">Сохранить</button>
                </div>
            </form>
        </div>
    </div>
</div>
