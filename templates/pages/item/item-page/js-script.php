<?php
/**
 * JS скрипты для страницы элемента
 */
?>
<script>
const itemId = <?= $item['id'] ?>;
const itemType = '<?= $item['item_type'] ?>';
const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

// Копирование ссылки на элемент
function copyItemLink() {
    const url = window.location.href;
    navigator.clipboard.writeText(url).then(() => {
        const btn = document.getElementById('copyLinkBtn');
        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<svg width="14" height="14"><use href="#icon-check"/></svg><span>Скопировано!</span>';
        setTimeout(() => { btn.innerHTML = originalHtml; }, 2000);
    });
}

<?php if ($item['item_type'] === 'server'): ?>
// === ЛОГИКА ДЛЯ СЕРВЕРА ===
<?php require __DIR__ . '/js-scripts/server.js.php'; ?>
<?php endif; ?>

<?php if ($item['item_type'] === 'chat'): ?>
// === ЛОГИКА ДЛЯ ЧАТА ===
<?php require __DIR__ . '/js-scripts/chat.js.php'; ?>
<?php endif; ?>
</script>

<?php if ($item['item_type'] === 'server'): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<?php endif; ?>
