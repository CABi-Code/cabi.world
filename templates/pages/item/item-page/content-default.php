<?php
/**
 * Контент элемента по умолчанию
 * @var array $item
 * @var array $children
 */
?>
<div class="item-content">
    <?php if (!empty($children)): ?>
        <div class="item-children">
            <h2 class="section-title">Содержимое</h2>
            <div class="children-grid">
                <?php foreach ($children as $child): ?>
                    <?php
                    $childIcon = $child['icon'] ?? ($itemsMap[$child['item_type']]['icon'] ?? 'file');
                    $childColor = $child['color'] ?? ($itemsMap[$child['item_type']]['color'] ?? '#94a3b8');
                    ?>
                    <a href="/item/<?= $child['id'] ?>" class="child-card">
                        <span class="child-icon" style="color: <?= e($childColor) ?>">
                            <svg width="20" height="20"><use href="#icon-<?= e($childIcon) ?>"/></svg>
                        </span>
                        <span class="child-name"><?= e($child['name']) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="item-empty">
            <svg width="48" height="48"><use href="#icon-folder-open"/></svg>
            <p>Нет содержимого</p>
        </div>
    <?php endif; ?>
</div>
