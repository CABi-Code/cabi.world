<?php
/**
 * Рендер структуры сообщества (папки и чаты)
 * 
 * @var array $structure
 * @var bool $isOwner
 * @var int $community['id']
 */

function renderStructureItem(array $item, bool $isOwner, int $communityId, int $depth = 1): void {
    $type = $item['type'];
    $data = $item['data'];
    $children = $item['children'] ?? [];
    $paddingLeft = 1;
    
    if ($type === 'folder'): ?>
        <div class="community-folder" data-folder-id="<?= $data['id'] ?>" style="padding-left: <?= $paddingLeft ?>rem;">
            <div class="folder-header">
                <button class="folder-toggle" onclick="toggleFolder(<?= $data['id'] ?>)">
                    <svg width="14" height="14" class="folder-arrow"><use href="#icon-chevron-down"/></svg>
                    <svg width="16" height="16" class="folder-icon"><use href="#icon-folder"/></svg>
                    <span class="folder-name"><?= e($data['name']) ?></span>
                </button>
                
                <?php if ($isOwner): ?>
                <div class="folder-actions">
                    <button class="btn btn-ghost btn-icon btn-xs" onclick="showCreateModal(<?= $communityId ?>, <?= $data['id'] ?>)" title="Добавить">
                        <svg width="12" height="12"><use href="#icon-plus"/></svg>
                    </button>
                    <button class="btn btn-ghost btn-icon btn-xs" onclick="editFolder(<?= $data['id'] ?>)" title="Настройки">
                        <svg width="12" height="12"><use href="#icon-settings"/></svg>
                    </button>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="folder-content" id="folder-content-<?= $data['id'] ?>">
                <?php foreach ($children as $child): ?>
                    <?php renderStructureItem($child, $isOwner, $communityId, $depth + 1); ?>
                <?php endforeach; ?>
            </div>
        </div>
    <?php else: // chat ?>
        <div class="community-chat" data-chat-id="<?= $data['id'] ?>" style="padding-left: <?= $paddingLeft ?>rem;">
            <a href="#" class="chat-link" onclick="openChat(<?= $data['id'] ?>); return false;">
                <svg width="16" height="16"><use href="#icon-message-circle"/></svg>
                <span class="chat-name"><?= e($data['name']) ?></span>
                <?php if ($data['messages_count'] > 0): ?>
                    <span class="chat-count"><?= $data['messages_count'] ?></span>
                <?php endif; ?>
            </a>
            
            <?php if ($isOwner): ?>
            <div class="chat-actions">
                <button class="btn btn-ghost btn-icon btn-xs" onclick="editChat(<?= $data['id'] ?>)" title="Настройки">
                    <svg width="12" height="12"><use href="#icon-settings"/></svg>
                </button>
            </div>
            <?php endif; ?>
        </div>
    <?php endif;
}

// Рендерим структуру
foreach ($structure as $item): ?>
    <?php renderStructureItem($item, $isOwner, $community['id']); ?>
<?php endforeach; ?>

<script>
function toggleFolder(folderId) {
    const content = document.getElementById('folder-content-' + folderId);
    const folder = content.closest('.community-folder');
    folder.classList.toggle('collapsed');
}

function openChat(chatId) {
    // Открываем чат в модальном окне или на отдельной странице
    window.location.href = '/chat/' + chatId;
}

function editFolder(folderId) {
    // TODO: Открыть модалку редактирования папки
    alert('Редактирование папки ' + folderId);
}

function editChat(chatId) {
    // TODO: Открыть модалку редактирования чата
    alert('Редактирование чата ' + chatId);
}
</script>
