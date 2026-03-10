
<?php

$folderRepository = new \App\Repository\UserFolderRepository();
$item = $folderRepository->getItem($app['folder_item_id']);

$iconMap = $folderRepository->getItemsMap();
$type = $item['item_type'];
$children = $item['children'] ?? [];
$isEntity = $folderRepository->isEntity($type);
$hasChildren = !empty($children);


	
$iconData = $iconMap[$type];
$icon = $item['icon'] ?? $iconData['icon'];
$color = $item['color'] ?? $iconData['color'];

$serverData = null;
if ($type === 'server' && $item['settings']) {
	$serverData = is_string($item['settings']) 
		? json_decode($item['settings'], true) 
		: $item['settings'];
}

?>

<div class="app-footer <?= $isEntity ? 'is-entity' : 'is-element' ?> type-<?= $type ?> <?= !empty($item['is_collapsed']) ? 'collapsed' : '' ?>"
	data-id="<?= $item['id'] ?>"
	data-type="<?= $type ?>"
	data-parent="<?= $item['parent_id'] ?? 'root' ?>"
	data-is-entity="<?= $isEntity ? '1' : '0' ?>"
	data-sort-order="<?= (float)($item['sort_order'] ?? 0) ?>"
	draggable="<?= $isOwner ? 'true' : 'false' ?>">
    <span class="app-date"><?= date('d.m.Y H:i', strtotime($app['created_at'])) ?></span>
    <span class="app-date">
			<div onclick="window.open(window.location.origin + '/item/' + <?= $item['id'] ?>, '_blank')" style="padding-left: <?= $depth * 16 ?>px;">				
				<div class="span-move">
					<span class="folder-icon" style="color: <?= e($color) ?>;">
					  <svg width="16" height="16"><use href="#icon-<?= e($icon) ?>"/></svg>
					</span>
						
					<?php if ($type === 'chat'): ?>
					  <span class="folder-name"><?= e($item['name']) ?></span>
					<?php else: ?>
					  <span class="folder-name"><?= e($item['name']) ?></span>
					<?php endif; ?>
					
					<?php if (!empty($item['is_hidden'])): ?>
					  <span class="folder-badge hidden">скрыта</span>
					<?php endif; ?>
					
					<?php // Статус сервера ?>
					<?php if ($type === 'server' && $serverData): ?>
						<span class="server-status-wrapper">
							<span class="server-status-dot checking" 
								  data-ip="<?= e($serverData['ip'] ?? '') ?>"
								  data-port="<?= e($serverData['port'] ?? 25565) ?>"
								  title="Проверка..."></span>
							<span class="server-player-count"></span>
						</span>
					<?php endif; ?>
				</div>
			</div>
		</span>
    <?php if ($isOwner): ?>
        <div class="app-actions">
            <button class="btn btn-ghost btn-icon btn-sm" title="<?= $isHidden ? 'Показать' : 'Скрыть' ?>"
                    onclick="toggleHidden(<?= $app['id'] ?>)">
                <svg width="14" height="14"><use href="#icon-<?= $isHidden ? 'eye' : 'eye-off' ?>"/></svg>
            </button>
            <button class="btn btn-ghost btn-icon btn-sm" title="Редактировать"
                    onclick='openApplicationModal["editAppModal"](<?= e(json_encode($appForJs)) ?>)'>
                <svg width="14" height="14"><use href="#icon-edit"/></svg>
            </button>
            <button class="btn btn-ghost btn-icon btn-sm" style="color:var(--danger)" title="Удалить"
                    onclick="deleteApp(<?= $app['id'] ?>)">
                <svg width="14" height="14"><use href="#icon-trash"/></svg>
            </button>
        </div>
    <?php endif; ?>
</div>


<?php 

include __DIR__ . '/js/server-pinger.js.php';
include __DIR__ . '/js/user-folder-server-ping.js.php';

?>
