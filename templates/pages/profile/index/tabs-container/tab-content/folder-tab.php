<?php
/**
 * Вкладка "Моя папка" в профиле
 */

$structure = [];
if (!$folderIsEmpty || $isOwner) {
    $structure = $folderRepo->getStructure($profileUser['id']);
}
?>

<div class="tab-pane <?= $activeTab === 'folder' ? 'active' : '' ?>" id="tab-folder">
    <div class="folder-layout">
        <!-- Основной контент -->
        <div class="folder-main">
            <?php if ($isOwner): ?>
                <?php if ($folderIsEmpty): ?>
                    <div class="folder-empty-owner">
                        <button class="folder-create-btn" id="folderCreateBtn">
                            <svg width="32" height="32"><use href="#icon-plus"/></svg>
                        </button>
                        <p class="folder-create-hint">Нажмите, чтобы создать папку или чат</p>
                    </div>
                <?php else: ?>
                    <div class="community-structure" id="folderStructure" data-user-id="<?= $profileUser['id'] ?>">
                        <?php include __DIR__ . '/folder-tab/folder-structure.php'; ?>
                    </div>
                    
                    <div class="folder-toolbar">
                        <button class="btn btn-ghost btn-sm" onclick="showCreateModal(null)">
                            <svg width="14" height="14"><use href="#icon-plus"/></svg>
                            Добавить
                        </button>
                    </div>
                <?php endif; ?>
                
            <?php else: ?>
                <?php if (!$folderIsEmpty): ?>
                    <?php if ($user): ?>
                        <div class="folder-subscribe-wrap">
                            <?php if ($isSubscribed): ?>
                                <button class="btn btn-secondary btn-sm" onclick="toggleSubscription(<?= $profileUser['id'] ?>, false)">
                                    <svg width="14" height="14"><use href="#icon-check"/></svg>
                                    Вы подписаны
                                </button>
                            <?php else: ?>
                                <button class="btn btn-primary btn-sm" onclick="toggleSubscription(<?= $profileUser['id'] ?>, true)">
                                    <svg width="14" height="14"><use href="#icon-plus"/></svg>
                                    Подписаться
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="community-structure" id="folderStructure">
                        <?php include __DIR__ . '/folder-tab/folder-structure.php'; ?>
                    </div>
                <?php else: ?>
                    <div class="folder-empty">
                        <svg width="48" height="48"><use href="#icon-folder"/></svg>
                        <p>Папка пуста</p>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        
        <!-- Панель элемента -->
        <div class="folder-panel" id="itemPanel">
            <div class="panel-placeholder">
                <svg width="24" height="24"><use href="#icon-info"/></svg>
                <p>Выберите элемент</p>
            </div>
        </div>
    </div>

    <?php if ($isOwner): ?>
        <?php include __DIR__ . '/folder-tab/modals.php'; ?>
    <?php endif; ?>
    
    <?php include __DIR__ . '/folder-tab/js-script.php'; ?>
</div>
