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
    <?php
    $headerFavicon = null;
    if ($item['item_type'] === 'server' && !empty($settings['server_id'])) {
        $headerGsRepo = new \App\Repository\GlobalServerRepository();
        $headerGs = $headerGsRepo->getById((int)$settings['server_id']);
        $headerFavicon = $headerGs['favicon'] ?? null;
    }
    ?>
    <?php if ($headerFavicon): ?>
    <div class="item-icon item-icon-favicon" id="itemHeaderIcon" style="position:relative;overflow:visible;">
        <img src="<?= e($headerFavicon) ?>" width="32" height="32" alt="" style="border-radius:6px;image-rendering:pixelated;">
        <span class="header-favicon-mini" style="color: <?= e($color) ?>;">
            <svg width="12" height="12"><use href="#icon-<?= e($icon) ?>"/></svg>
        </span>
    </div>
    <?php else: ?>
    <div class="item-icon<?= ($item['item_type'] === 'server') ? ' item-icon-server-default' : '' ?>" style="color: <?= e($color) ?>" id="itemHeaderIcon" data-icon="<?= e($icon) ?>">
        <svg width="32" height="32"><use href="#icon-<?= e($icon) ?>"/></svg>
    </div>
    <?php endif; ?>

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
    $itemsMap = UserFolderRepository::ITEMS_MAP;
    $iconData = $itemsMap[$item['item_type']] ?? ['color' => '#3b82f6'];
?>
<div class="modal" id="itemSettingsModal" style="display:none;">
    <div class="modal-backdrop" data-modal-close></div>
    <div class="modal-content modal-sm">
        <div class="modal-header">
            <h3 class="modal-title">Настройки</h3>
            <button class="modal-close" data-modal-close>&times;</button>
        </div>
        <div class="modal-body">
            <form id="itemSettingsForm">
                <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">

                <div class="form-group">
                    <label class="form-label">Название</label>
                    <input type="text" name="name" class="form-input" id="itemSettingsName"
                           value="<?= e($item['name'] ?? '') ?>" maxlength="100">
                </div>

                <div class="form-group">
                    <label class="form-label">Описание</label>
                    <textarea name="description" class="form-input" rows="2"
                              id="itemSettingsDescription"><?= e($item['description'] ?? '') ?></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Цвет иконки</label>
                    <input type="color" name="color" class="form-input form-color"
                           id="itemSettingsColor" value="<?= e($item['color'] ?? $iconData['color']) ?>">
                </div>

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

                <div class="form-actions">
                    <button type="button" class="btn btn-danger btn-sm" onclick="deleteItemFromPage()">
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
<?php endif; ?>
