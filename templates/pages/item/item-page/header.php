<?php
/**
 * Заголовок элемента
 * @var array $item
 * @var string $icon
 * @var string $color
 * @var bool $isOwner
 */
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
        <button class="btn btn-ghost btn-sm" onclick="copyItemLink()" id="copyLinkBtn">
            <svg width="14" height="14"><use href="#icon-link"/></svg>
            <span>Скопировать ссылку</span>
        </button>
        
        <?php if ($isOwner): ?>
            <button class="btn btn-ghost btn-sm" onclick="openSettings()">
                <svg width="14" height="14"><use href="#icon-settings"/></svg>
            </button>
        <?php endif; ?>
    </div>
</div>
