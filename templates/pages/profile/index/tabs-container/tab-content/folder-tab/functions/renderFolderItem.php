<?php

function renderFolderItem(array $node, bool $isOwner, int $depth, \App\Repository\UserFolderRepository $folderRepository): void {
    
    $iconMap = $folderRepository->getItemsMap();
    
    $item = $node['data'];
    $type = $node['type'];
    $children = $node['children'] ?? [];
    $isEntity = $folderRepository->isEntity($type);
    $hasChildren = !empty($children);

    $iconData = $iconMap[$type] ?? ['icon' => 'file', 'color' => '#94a3b8'];
    $icon = $item['icon'] ?? $iconData['icon'];
    $color = $item['color'] ?? $iconData['color'];

    // ==================== СОРТИРОВКА ДЕТЕЙ ====================
    if (!empty($children)) {
        usort($children, function(array $a, array $b) {
            $soA = (float)($a['data']['sort_order'] ?? 9999999);
            $soB = (float)($b['data']['sort_order'] ?? 9999999);

            if ($soA !== $soB) {
                return $soA <=> $soB;
            }

            $nameA = $a['data']['name'] ?? '';
            $nameB = $b['data']['name'] ?? '';
            return strcasecmp($nameA, $nameB);
        });
    }
    // =====================================================================
    ?>
    <div class="folder-item <?= $isEntity ? 'is-entity' : 'is-element' ?> type-<?= $type ?> <?= !empty($item['is_collapsed']) ? 'collapsed' : '' ?>"
         data-id="<?= $item['id'] ?>"
         data-type="<?= $type ?>"
         data-parent="<?= $item['parent_id'] ?? 'root' ?>"
         data-is-entity="<?= $isEntity ? '1' : '0' ?>"
		 data-sort-order="<?= (float)($item['sort_order'] ?? 0) ?>"
         draggable="<?= $isOwner ? 'true' : 'false' ?>">
        
        <div class="folder-item-row" onclick="window.openItemPanel(<?= $item['id'] ?>, '<?= $type ?>')" style="padding-left: <?= $depth * 16 ?>px;">
            <?php if ($isEntity && $hasChildren): ?>
                <button class="folder-toggle" onclick="window.toggleItem(<?= $item['id'] ?>); event.stopPropagation();">
                    <svg width="12" height="12" class="toggle-arrow"><use href="#icon-chevron-down"/></svg>
                </button>
            <?php else: ?>
                <span class="folder-spacer"></span>
            <?php endif; ?>
            
            <div class="span-move">
				<span class="folder-icon" style="color: <?= e($color) ?>;">
					<svg width="16" height="16"><use href="#icon-<?= e($icon) ?>"/></svg>
				</span>
            
				<?php if ($type === 'chat'): ?>
					<a href="/chat/<?= $item['id'] ?>" class="folder-name"><?= e($item['name']) ?></a>
				<?php else: ?>
					<span class="folder-name"><?= e($item['name']) ?></span>
				<?php endif; ?>
				
				<?php if (!empty($item['is_hidden'])): ?>
					<span class="folder-badge hidden">скрыта</span>
				<?php endif; ?>
            </div>
            
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
                    <?php renderFolderItem($child, $isOwner, $depth + 1, $folderRepository); ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
<?php }

?>