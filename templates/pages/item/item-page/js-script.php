<?php
/**
 * JS скрипты для страницы элемента
 */
?>
<script>
const itemId = <?= (int)$item['id'] ?>;
const itemType = '<?= e($item['item_type']) ?>';
const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

// Копирование прямой ссылки на элемент (slug-based)
function copyItemLink() {
    const btn = document.getElementById('copyLinkBtn');
    const link = btn.dataset.link;
    const url = window.location.origin + link;
    navigator.clipboard.writeText(url).then(() => {
        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<svg width="14" height="14"><use href="#icon-check"/></svg><span>Скопировано!</span>';
        setTimeout(() => { btn.innerHTML = originalHtml; }, 2000);
    });
}

// Скрыть/показать заявку
async function toggleHidden(id) {
    await fetch('/api/application/' + id + '/toggle-hidden', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
        body: JSON.stringify({ id })
    });
    location.reload();
}

// Удалить заявку
async function deleteApp(id) {
    if (!confirm('Удалить заявку?')) return;
    await fetch('/api/application/delete/' + id, {
        method: 'DELETE',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
        body: JSON.stringify({ id })
    });
    location.reload();
}

<?php if ($isOwner && ($item['id'] ?? 0) > 0): ?>
// Настройки элемента: сохранение slug
(function() {
    const slugInput = document.getElementById('itemSlugInput');
    const slugPreview = document.getElementById('slugPreview');
    const slugError = document.getElementById('slugError');
    const saveBtn = document.getElementById('saveSlugBtn');
    const typePrefix = '<?= e($prefixMap[$item['item_type']] ?? 'string-') ?>';

    if (slugInput) {
        slugInput.addEventListener('input', () => {
            const val = slugInput.value.replace(/[^a-zA-Z0-9_-]/g, '');
            slugInput.value = val;
            slugPreview.textContent = '/item/' + typePrefix + val;
            slugError.style.display = 'none';
        });
    }

    if (saveBtn) {
        saveBtn.addEventListener('click', async () => {
            const slug = slugInput.value.trim();
            saveBtn.disabled = true;
            saveBtn.textContent = '...';

            try {
                const res = await fetch('/api/user-folder/update-slug', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': csrf
                    },
                    body: JSON.stringify({ id: itemId, slug: slug })
                });
                const result = await res.json();

                if (res.ok && result.success) {
                    // Редирект на новый URL
                    window.location.href = '/item/' + typePrefix + slug;
                } else {
                    slugError.textContent = result.error || 'Ошибка сохранения';
                    slugError.style.display = 'block';
                }
            } catch (err) {
                slugError.textContent = 'Ошибка сети';
                slugError.style.display = 'block';
            } finally {
                saveBtn.disabled = false;
                saveBtn.textContent = 'Сохранить';
            }
        });
    }
})();
<?php endif; ?>
</script>


<?php if ($item['item_type'] === 'folder'): ?>
<?php // === ЛОГИКА ДЛЯ ПАПКИ === ?>
<?php require __DIR__ . '/js-scripts/folder.js.php'; ?>
<?php endif; ?>

<?php if ($item['item_type'] === 'server'): ?>
<?php // === ЛОГИКА ДЛЯ СЕРВЕРА === ?>
<?php require __DIR__ . '/js-scripts/server.js.php'; ?>
<?php endif; ?>

<?php if ($item['item_type'] === 'chat'): ?>
<?php // === ЛОГИКА ДЛЯ ЧАТА === ?>
<?php require __DIR__ . '/js-scripts/chat.js.php'; ?>
<?php endif; ?>

<?php if ($item['item_type'] === 'server'): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<?php endif; ?>
