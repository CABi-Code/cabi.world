<?php
/**
 * Рендер дерева структуры "Моей папки"
 * 
 * @var array $structure
 * @var bool $isOwner
 */

function renderFolderItem(array $node, bool $isOwner, int $depth = 0): void {
    $item = $node['item'];
    $children = $node['children'] ?? [];
    $isEntity = in_array($item['item_type'], ['category', 'modpack', 'mod']);
    $paddingLeft = $depth * 1.25;
    
    $typeIcons = [
        'category' => 'folder',
        'modpack' => 'package',
        'mod' => 'puzzle',
        'server' => 'server',
        'application' => 'file-text',
        'chat' => 'message-circle',
        'shortcut' => 'link'
    ];
    
    $icon = $item['icon'] ?? $typeIcons[$item['item_type']] ?? 'file';
    $color = $item['color'] ?? null;
    ?>
    <div class="folder-item <?= $isEntity ? 'is-entity' : 'is-element' ?> type-<?= $item['item_type'] ?> <?= $item['is_collapsed'] ? 'collapsed' : '' ?>"
         data-id="<?= $item['id'] ?>"
         data-type="<?= $item['item_type'] ?>"
         data-parent="<?= $item['parent_id'] ?? 'null' ?>"
         draggable="true"
         style="--depth: <?= $depth ?>; padding-left: <?= $paddingLeft ?>rem;">
        
        <div class="folder-item-row">
            <?php if ($isEntity && !empty($children)): ?>
                <button class="folder-item-toggle" onclick="toggleFolderItem(<?= $item['id'] ?>)">
                    <svg width="12" height="12" class="toggle-arrow"><use href="#icon-chevron-down"/></svg>
                </button>
            <?php else: ?>
                <span class="folder-item-spacer"></span>
            <?php endif; ?>
            
            <span class="folder-item-icon" <?= $color ? 'style="color: ' . e($color) . '"' : '' ?>>
                <svg width="16" height="16"><use href="#icon-<?= e($icon) ?>"/></svg>
            </span>
            
            <span class="folder-item-name" onclick="openFolderItem(<?= $item['id'] ?>, '<?= $item['item_type'] ?>')">
                <?= e($item['name']) ?>
            </span>
            
            <?php if ($isOwner): ?>
            <div class="folder-item-actions">
                <?php if ($isEntity): ?>
                    <button class="btn btn-ghost btn-icon btn-xs" onclick="showCreateItemModal(<?= $item['id'] ?>)" title="Добавить">
                        <svg width="12" height="12"><use href="#icon-plus"/></svg>
                    </button>
                <?php endif; ?>
                <button class="btn btn-ghost btn-icon btn-xs" onclick="showItemSettings(<?= $item['id'] ?>)" title="Настройки">
                    <svg width="12" height="12"><use href="#icon-settings"/></svg>
                </button>
            </div>
            <?php endif; ?>
        </div>
        
        <?php if ($isEntity && !empty($children)): ?>
            <div class="folder-item-children" id="children-<?= $item['id'] ?>">
                <?php foreach ($children as $child): ?>
                    <?php renderFolderItem($child, $isOwner, $depth + 1); ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
<?php
}

// Рендерим структуру
foreach ($structure as $node): ?>
    <?php renderFolderItem($node, $isOwner); ?>
<?php endforeach; ?>

<!-- Drop zone для корня -->
<?php if ($isOwner): ?>
<div class="folder-drop-zone" data-parent="null">
    <span>Перетащите сюда</span>
</div>
<?php endif; ?>
