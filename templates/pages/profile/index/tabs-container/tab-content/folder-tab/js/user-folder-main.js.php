<script>

(async function() {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    const isOwner = <?= $isOwner ? 'true' : 'false' ?>;
    const userId = <?= $profileUser['id'] ?>;

    window.csrf = csrf;
    window.isOwner = isOwner;
    window.userId = userId;

    // Глобальные переменные
    window.currentParentId = null;
    window.currentItemId = null;

    // Загружаем iconMap
    await loadIconMap();

    // Инициализация модалок
    if (typeof initModals === 'function') initModals();

    // Drag & Drop
    if (isOwner) {
        const structure = document.querySelector('.community-structure');
        if (structure) {
            structure.addEventListener('dragstart', handleDragStart, true);
            structure.addEventListener('dragend', handleDragEnd, true);
            structure.addEventListener('dragover', handleDragOver);
            structure.addEventListener('dragleave', handleDragLeave);
            structure.addEventListener('drop', handleDrop);

            structure.addEventListener('dragover', handleRootDragOver);
            structure.addEventListener('drop', handleRootDrop);
        }
    }
})();

</script>