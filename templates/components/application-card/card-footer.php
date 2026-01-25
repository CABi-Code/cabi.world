<div class="app-footer">
    <span class="app-date"><?= date('d.m.Y H:i', strtotime($app['created_at'])) ?></span>
    
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
