<?php
/**
 * Секция с вкладками в профиле: Заявки, Сообщество, Подписки
 * 
 * @var array $profileUser
 * @var array|null $user
 * @var bool $isOwner
 * @var array $applications
 */

use App\Repository\CommunityRepository;

$communityRepo = new CommunityRepository();
$community = $communityRepo->findByUserId($profileUser['id']);
$hasCommunity = $community !== null;
$communityIsEmpty = $hasCommunity ? $communityRepo->isEmpty($community['id']) : true;
$subscribersCount = $community['subscribers_count'] ?? 0;

// Проверяем подписку текущего пользователя
$isSubscribed = false;
if ($user && $community) {
    $isSubscribed = $communityRepo->isSubscribed($community['id'], $user['id']);
}

// Подписки профиля (если видимость открыта или это владелец)
$showSubscriptions = $isOwner || ($profileUser['subscriptions_visible'] ?? true);
$subscriptions = [];
$subscriptionsCount = 0;
if ($showSubscriptions) {
    $subscriptions = $communityRepo->getUserSubscriptions($profileUser['id'], 20);
    $subscriptionsCount = $communityRepo->countUserSubscriptions($profileUser['id']);
}

// Определяем активную вкладку (по умолчанию - заявки)
$activeTab = $_GET['tab'] ?? 'community';
if (!in_array($activeTab, ['applications', 'community', 'subscriptions'])) {
    $activeTab = 'applications';
}

// Проверяем, может ли сторонний пользователь открыть сообщество
$canViewCommunity = $isOwner || ($hasCommunity && !$communityIsEmpty);
?>

<?php if ($subscribersCount > 0): ?>
<div class="profile-stats">
    <span class="profile-stat">
        <svg width="14" height="14"><use href="#icon-users"/></svg>
        Подписчики сообщества: <strong><?= number_format($subscribersCount, 0, '', ' ') ?></strong>
    </span>
</div>
<?php endif; ?>

<?php include __DIR__ . '/profile-tabs-container.php'; ?>

<script>
// Переключение вкладок без перезагрузки страницы
document.querySelectorAll('.profile-tab:not([disabled])').forEach(tab => {
    tab.addEventListener('click', function() {
        const tabName = this.dataset.tab;
        
        // Обновляем активную вкладку
        document.querySelectorAll('.profile-tab').forEach(t => t.classList.remove('active'));
        this.classList.add('active');
        
        // Показываем нужный контент
        document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
        document.getElementById('tab-' + tabName)?.classList.add('active');
        
        // Обновляем URL без перезагрузки
        const url = new URL(window.location);
        url.searchParams.set('tab', tabName);
        history.pushState({}, '', url);
    });
});
</script>
