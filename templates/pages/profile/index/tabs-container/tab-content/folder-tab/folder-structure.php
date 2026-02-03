<?php
/**
 * Рендер структуры "Моей папки"
 * @var array $structure
 * @var bool $isOwner
 */

$iconMap = [
    'folder' => ['icon' => 'folder', 'color' => '#eab308'],
    'chat' => ['icon' => 'message-circle', 'color' => '#ec4899'],
    'modpack' => ['icon' => 'package', 'color' => '#8b5cf6'],
    'mod' => ['icon' => 'puzzle', 'color' => '#10b981'],
    'server' => ['icon' => 'server', 'color' => '#f59e0b'],
    'application' => ['icon' => 'file-text', 'color' => '#3b82f6'],
    'shortcut' => ['icon' => 'link', 'color' => '#6366f1'],
];

function renderFolderItem(array $node, bool $isOwner, int $depth, array $iconMap): void {
    $item = $node['data'];
    $type = $node['type'];
    $children = $node['children'] ?? [];
    $isEntity = in_array($type, ['folder', 'chat', 'modpack', 'mod']);
    $isElement = !$isEntity;
    
    $iconData = $iconMap[$type] ?? ['icon' => 'file', 'color' => '#94a3b8'];
    $icon = $item['icon'] ?? $iconData['icon'];
    $color = $item['color'] ?? $iconData['color'];
    $hasChildren = !empty($children);
    ?>
    <div class="folder-item <?= $isEntity ? 'is-entity' : 'is-element' ?> type-<?= $type ?> <?= !empty($item['is_collapsed']) ? 'collapsed' : '' ?>"
         data-id="<?= $item['id'] ?>"
         data-type="<?= $type ?>"
         data-parent="<?= $item['parent_id'] ?? 'root' ?>"
         data-is-entity="<?= $isEntity ? '1' : '0' ?>"
         draggable="<?= $isOwner ? 'true' : 'false' ?>">
        
        <div class="folder-item-row" style="padding-left: <?= $depth * 16 ?>px;">
            <?php if ($isEntity && $hasChildren): ?>
                <button class="folder-toggle" onclick="window.toggleItem(<?= $item['id'] ?>); event.stopPropagation();">
                    <svg width="12" height="12" class="toggle-arrow"><use href="#icon-chevron-down"/></svg>
                </button>
            <?php else: ?>
                <span class="folder-spacer"></span>
            <?php endif; ?>
            
            <span class="folder-icon" style="color: <?= e($color) ?>;">
                <svg width="16" height="16"><use href="#icon-<?= e($icon) ?>"/></svg>
            </span>
            
            <?php if ($type === 'chat'): ?>
                <a href="/chat/<?= $item['id'] ?>" class="folder-name"><?= e($item['name']) ?></a>
            <?php else: ?>
                <span class="folder-name" onclick="window.openItemPanel(<?= $item['id'] ?>, '<?= $type ?>')"><?= e($item['name']) ?></span>
            <?php endif; ?>
            
            <?php if (!empty($item['is_hidden'])): ?>
                <span class="folder-badge hidden">скрыта</span>
            <?php endif; ?>
            
            <?php if ($isOwner): ?>
            <div class="folder-actions">
                <?php if ($isEntity): ?>
                    <button class="folder-action-btn" onclick="window.showCreateModal(<?= $item['id'] ?>); event.stopPropagation();" title="Добавить">
                        <svg width="14" height="14"><use href="#icon-plus"/></svg>
                    </button>
                <?php endif; ?>
                <button class="folder-action-btn" onclick="window.showSettingsModal(<?= $item['id'] ?>); event.stopPropagation();" title="Настройки">
                    <svg width="14" height="14"><use href="#icon-settings"/></svg>
                </button>
            </div>
            <?php endif; ?>
        </div>
        
        <?php if ($isEntity && $hasChildren): ?>
            <div class="folder-children" id="children-<?= $item['id'] ?>">
                <?php foreach ($children as $child): ?>
                    <?php renderFolderItem($child, $isOwner, $depth + 1, $iconMap); ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
<?php }

if (empty($structure)): ?>
    <div class="folder-empty">
        <svg width="48" height="48"><use href="#icon-folder"/></svg>
        <p>Папка пуста</p>
        <?php if ($isOwner): ?>
            <p class="folder-empty-hint">Нажмите "Добавить" чтобы создать элемент</p>
        <?php endif; ?>
    </div>
<?php else: ?>
    <?php foreach ($structure as $node): ?>
        <?php renderFolderItem($node, $isOwner, 0, $iconMap); ?>
    <?php endforeach; ?>
<?php endif; ?>
