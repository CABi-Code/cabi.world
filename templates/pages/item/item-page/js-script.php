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

// Удалить элемент со страницы
async function deleteItemFromPage() {
    if (!itemId || !confirm('Удалить элемент и всё содержимое?')) return;
    const res = await fetch('/api/user-folder/delete', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
        body: JSON.stringify({ id: itemId })
    });
    if (res.ok) {
        window.location.href = '/@<?= e($owner['login'] ?? '') ?>';
    } else {
        alert((await res.json()).error || 'Ошибка удаления');
    }
}

<?php if ($isOwner && ($item['id'] ?? 0) > 0): ?>
// Настройки элемента: единая форма (название, описание, цвет, slug)
(function() {
    const slugInput = document.getElementById('itemSlugInput');
    const slugPreview = document.getElementById('slugPreview');
    const slugError = document.getElementById('slugError');
    const typePrefix = '<?= e($prefixMap[$item['item_type']] ?? 'string-') ?>';
    const settingsForm = document.getElementById('itemSettingsForm');

    if (slugInput) {
        slugInput.addEventListener('input', () => {
            const val = slugInput.value.replace(/[^a-zA-Z0-9_-]/g, '');
            slugInput.value = val;
            slugPreview.textContent = '/item/' + typePrefix + val;
            slugError.style.display = 'none';
        });
    }

    if (settingsForm) {
        settingsForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const fd = new FormData(settingsForm);
            const name = fd.get('name');
            const description = fd.get('description');
            const color = fd.get('color');
            const slug = (slugInput?.value || '').trim();

            try {
                // Сохраняем основные данные
                const updateRes = await fetch('/api/user-folder/update', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
                    body: JSON.stringify({
                        id: itemId, name, description, color
                    })
                });

                if (!updateRes.ok) {
                    const err = await updateRes.json();
                    alert(err.error || 'Ошибка сохранения');
                    return;
                }

                // Сохраняем slug если он изменился
                const originalSlug = '<?= e($item['slug'] ?? '') ?>';
                if (slug && slug !== originalSlug) {
                    const slugRes = await fetch('/api/user-folder/update-slug', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
                        body: JSON.stringify({ id: itemId, slug: slug })
                    });
                    const slugResult = await slugRes.json();
                    if (!slugRes.ok) {
                        slugError.textContent = slugResult.error || 'Ошибка сохранения ссылки';
                        slugError.style.display = 'block';
                        return;
                    }
                    // Редирект на новый URL
                    window.location.href = '/item/' + typePrefix + slug;
                    return;
                }

                location.reload();
            } catch (err) {
                alert('Ошибка сети');
            }
        });
    }
})();
<?php endif; ?>
</script>


<?php if ($item['item_type'] === 'folder'): ?>
<?php require __DIR__ . '/js-scripts/folder.js.php'; ?>
<?php endif; ?>

<?php if ($item['item_type'] === 'server'): ?>
<?php require __DIR__ . '/js-scripts/server.js.php'; ?>
<?php endif; ?>

<?php if ($item['item_type'] === 'chat'): ?>
<?php require __DIR__ . '/js-scripts/chat.js.php'; ?>
<?php endif; ?>

<?php if ($item['item_type'] === 'server'): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<?php endif; ?>
