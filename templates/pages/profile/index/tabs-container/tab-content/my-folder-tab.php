<?php
/**
 * Вкладка "Моя папка" в профиле
 * 
 * @var array $profileUser
 * @var array|null $user
 * @var bool $isOwner
 */

use App\Repository\UserFolderRepository;

$folderRepo = new UserFolderRepository();
$structure = [];
$isEmpty = true;

if ($isOwner || true) { // Временно показываем всем для отладки
    $structure = $folderRepo->getStructure($profileUser['id']);
    $isEmpty = empty($structure);
}
?>

<div class="tab-pane <?= $activeTab === 'folder' ? 'active' : '' ?>" id="tab-folder">
    <div class="my-folder-container" data-user-id="<?= $profileUser['id'] ?>">
        
        <!-- Выдвигающаяся панель слева -->
        <div class="folder-sidebar" id="folderSidebar">
            <div class="folder-sidebar-header">
                <span class="folder-sidebar-title" id="sidebarTitle">Выберите элемент</span>
                <button class="btn btn-ghost btn-icon btn-sm" onclick="closeSidebar()">
                    <svg width="16" height="16"><use href="#icon-x"/></svg>
                </button>
            </div>
            <div class="folder-sidebar-content" id="sidebarContent">
                <!-- Контент загружается динамически -->
            </div>
        </div>
        
        <!-- Основной контент с папками -->
        <div class="folder-main">
            <?php if ($isOwner): ?>
                <!-- Тулбар для владельца -->
                <div class="folder-toolbar">
                    <button class="btn btn-primary btn-sm" onclick="showCreateItemModal(null)">
                        <svg width="14" height="14"><use href="#icon-plus"/></svg>
                        Добавить
                    </button>
                </div>
            <?php endif; ?>
            
            <!-- Структура папки -->
            <div class="folder-structure" id="folderStructure">
                <?php if ($isEmpty && $isOwner): ?>
                    <div class="folder-empty">
                        <svg width="48" height="48" class="empty-icon"><use href="#icon-folder"/></svg>
                        <p>Ваша папка пуста</p>
                        <p class="empty-hint">Добавьте категории, модпаки, серверы и другие элементы</p>
                        <button class="btn btn-primary" onclick="showCreateItemModal(null)">
                            <svg width="14" height="14"><use href="#icon-plus"/></svg>
                            Добавить первый элемент
                        </button>
                    </div>
                <?php elseif ($isEmpty): ?>
                    <div class="folder-empty">
                        <svg width="48" height="48" class="empty-icon"><use href="#icon-folder"/></svg>
                        <p>Папка пуста</p>
                    </div>
                <?php else: ?>
                    <?php include __DIR__ . '/my-folder-tab/folder-tree.php'; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <?php if ($isOwner): ?>
        <?php include __DIR__ . '/my-folder-tab/modals.php'; ?>
    <?php endif; ?>
    
    <?php include __DIR__ . '/my-folder-tab/js-scripts/main.php'; ?>
</div>
