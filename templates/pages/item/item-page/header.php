<?php
/**
 * Заголовок элемента
 * @var array $item
 * @var array $owner
 * @var string $icon
 * @var string $color
 * @var bool $isOwner
 */

use App\Repository\UserFolderRepository;

$fullSlug = '';
if (!empty($item['slug'])) {
    $fullSlug = UserFolderRepository::getFullSlug($item['item_type'], $item['slug']);
}
$itemDirectLink = $fullSlug ? '/item/' . $fullSlug : '/item/' . $item['id'];
?>
<div class="item-header">
    <div class="item-icon" style="color: <?= e($color) ?>">
        <svg width="32" height="32"><use href="#icon-<?= e($icon) ?>"/></svg>
    </div>

    <div class="item-title-block">
        <h1 class="item-title"><?= e($item['name']) ?></h1>

        <?php if ($item['description']): ?>
            <p class="item-description"><?= e($item['description']) ?></p>
        <?php endif; ?>
    </div>

    <div class="item-actions">
        <button class="btn btn-ghost btn-sm" onclick="copyItemLink()" id="copyLinkBtn"
                data-link="<?= e($itemDirectLink) ?>">
            <svg width="14" height="14"><use href="#icon-link"/></svg>
            <span>Скопировать ссылку</span>
        </button>

        <?php if ($isOwner && ($item['id'] ?? 0) > 0): ?>
            <button class="btn btn-ghost btn-sm" data-modal-open="itemSettingsModal">
                <svg width="14" height="14"><use href="#icon-settings"/></svg>
            </button>
        <?php endif; ?>
    </div>
</div>

<?php if ($isOwner && ($item['id'] ?? 0) > 0): ?>
<?php
    $prefixMap = \App\Repository\UserFolderRepository::SLUG_PREFIXES;
    $typePrefix = $prefixMap[$item['item_type']] ?? 'string-';
?>
<div class="modal" id="itemSettingsModal" style="display:none;">
    <div class="modal-backdrop">
        <div class="modal-content modal-sm">
            <div class="modal-header">
                <h3>Настройки элемента</h3>
                <button class="btn btn-ghost btn-icon btn-sm" data-modal-close>
                    <svg width="18" height="18"><use href="#icon-x"/></svg>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Ссылка на элемент</label>
                    <div class="slug-input-wrapper">
                        <span class="slug-prefix"><?= e($typePrefix) ?></span>
                        <input type="text" class="form-input slug-input" id="itemSlugInput"
                               value="<?= e($item['slug'] ?? '') ?>"
                               placeholder="уникальная-ссылка"
                               maxlength="80">
                    </div>
                    <div class="form-hint">
                        Полная ссылка: <code id="slugPreview">/item/<?= e($typePrefix . ($item['slug'] ?? '')) ?></code>
                    </div>
                    <div class="form-error" id="slugError" style="display:none;"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-ghost btn-sm" data-modal-close>Отмена</button>
                <button class="btn btn-primary btn-sm" id="saveSlugBtn">Сохранить</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
