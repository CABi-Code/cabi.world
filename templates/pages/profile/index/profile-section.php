<?php
/**
 * Секция с вкладками в профиле
 */
?>

<?php if ($subscribersCount > 0): ?>
<div class="profile-stats">
    <span class="profile-stat">
        <svg width="14" height="14"><use href="#icon-users"/></svg>
        Подписчики: <strong><?= number_format($subscribersCount, 0, '', ' ') ?></strong>
    </span>
</div>
<?php endif; ?>

<?php include __DIR__ . '/profile-tabs-container.php'; ?>

<script>
document.querySelectorAll('.profile-tab:not([disabled])').forEach(tab => {
    tab.addEventListener('click', function() {
        const tabName = this.dataset.tab;
        document.querySelectorAll('.profile-tab').forEach(t => t.classList.remove('active'));
        this.classList.add('active');
        document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
        document.getElementById('tab-' + tabName)?.classList.add('active');
        const url = new URL(window.location);
        url.searchParams.set('tab', tabName);
        history.pushState({}, '', url);
    });
});
</script>
