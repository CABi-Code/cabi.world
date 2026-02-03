<?php
/**
 * Рендер структуры "Моей папки"
 * @var array $structure
 * @var bool $isOwner
 */

$userFolderRepository  = new \App\Repository\UserFolderRepository();

$iconMap = $userFolderRepository->getItemsMap();

require_once __DIR__ . '/functions/renderFolderItem.php';

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
        <?php renderFolderItem($node, $isOwner, 0, $userFolderRepository); ?>
    <?php endforeach; ?>
<?php endif; ?>
