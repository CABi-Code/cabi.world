<?php
/**
 * Рендер структуры "Моей папки"
 * @var array $structure
 * @var bool $isOwner
 */

function renderFolderItem(array $node, bool $isOwner, int $depth = 0): void {
    $item = $node['data'];
    $type = $node['type'];
    $children = $node['children'] ?? [];
    $isEntity = in_array($type, ['folder', 'chat', 'modpack', 'mod']);
    $paddingLeft = $depth * 1.25;
    
    $icons = [
        'folder' => 'folder', 'chat' => 'message-circle', 'modpack' => 'package',
        'mod' => 'puzzle', 'server' => 'server', 'application' => 'file-text', 'shortcut' => 'link'
    ];
    $icon = $item['icon'] ?? $icons[$type] ?? 'file';
    ?>
    <div class="community-folder <?= $item['is_collapsed'] ? 'collapsed' : '' ?>" 
         data-item-id="<?= $item['id'] ?>" 
         data-type="<?= $type ?>"
         draggable="<?= $isOwner ? 'true' : 'false' ?>"
         style="padding-left: <?= $paddingLeft ?>rem;">
        
        <div class="folder-header">
            <?php if ($isEntity && !empty($children)): ?>
                <button class="folder-toggle" onclick="toggleItem(<?= $item['id'] ?>)">
                    <svg width="14" height="14" class="folder-arrow"><use href="#icon-chevron-down"/></svg>
                </button>
            <?php else: ?>
                <span class="folder-toggle-spacer"></span>
            <?php endif; ?>
            
            <span class="folder-icon" <?= $item['color'] ? 'style="color:'.$item['color'].'"' : '' ?>>
                <svg width="16" height="16"><use href="#icon-<?= e($icon) ?>"/></svg>
            </span>
            
            <?php if ($type === 'chat'): ?>
                <a href="/chat/<?= $item['id'] ?>" class="folder-name chat-link"><?= e($item['name']) ?></a>
            <?php elseif ($type === 'application'): ?>
                <span class="folder-name" onclick="openItemSidebar(<?= $item['id'] ?>, '<?= $type ?>')"><?= e($item['name']) ?></span>
                <?php if (!empty($item['is_hidden'])): ?>
                    <span class="badge badge-muted">скрыта</span>
                <?php endif; ?>
            <?php else: ?>
                <span class="folder-name" onclick="openItemSidebar(<?= $item['id'] ?>, '<?= $type ?>')"><?= e($item['name']) ?></span>
            <?php endif; ?>
            
            <?php if ($isOwner): ?>
            <div class="folder-actions">
                <?php if ($isEntity): ?>
                    <button class="btn btn-ghost btn-icon btn-xs" onclick="showCreateModal(<?= $item['id'] ?>)" title="Добавить">
                        <svg width="12" height="12"><use href="#icon-plus"/></svg>
                    </button>
                <?php endif; ?>
                <button class="btn btn-ghost btn-icon btn-xs" onclick="showSettingsModal(<?= $item['id'] ?>)" title="Настройки">
                    <svg width="12" height="12"><use href="#icon-settings"/></svg>
                </button>
            </div>
            <?php endif; ?>
        </div>
        
        <?php if ($isEntity && !empty($children)): ?>
            <div class="folder-content" id="folder-content-<?= $item['id'] ?>">
                <?php foreach ($children as $child): ?>
                    <?php renderFolderItem($child, $isOwner, $depth + 1); ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
<?php }

foreach ($structure as $node): ?>
    <?php renderFolderItem($node, $isOwner); ?>
<?php endforeach; ?>
