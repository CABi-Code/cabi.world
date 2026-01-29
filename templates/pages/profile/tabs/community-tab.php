<?php
/**
 * Вкладка "Моё сообщество" в профиле
 * 
 * @var array $profileUser
 * @var array|null $user
 * @var bool $isOwner
 * @var array|null $community
 * @var bool $hasCommunity
 * @var bool $communityIsEmpty
 * @var bool $isSubscribed
 * @var CommunityRepository $communityRepo
 */

// Получаем структуру сообщества если оно есть
$structure = [];
if ($hasCommunity) {
    $structure = $communityRepo->getStructure($community['id']);
}
?>

<?php if ($isOwner): ?>
    <!-- Владелец видит кнопку создания или структуру -->
    <?php if (!$hasCommunity || $communityIsEmpty): ?>
        <!-- Пустое сообщество - показываем кнопку создания -->
        <div class="community-empty-owner">
            <button class="community-create-btn" id="communityCreateBtn" data-community-id="<?= $community['id'] ?? '' ?>">
                <svg width="32" height="32"><use href="#icon-plus"/></svg>
            </button>
            <p class="community-create-hint">Нажмите, чтобы создать чат или папку</p>
        </div>
        
        <?php if ($hasCommunity): ?>
            <div class="community-settings-link">
                <a href="#" class="btn btn-ghost btn-sm" onclick="openCommunitySettings(<?= $community['id'] ?>)">
                    <svg width="14" height="14"><use href="#icon-settings"/></svg>
                    Настройки сообщества
                </a>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <!-- Есть контент - показываем структуру -->
        <div class="community-structure" data-community-id="<?= $community['id'] ?>">
            <?php include __DIR__ . '/community-structure.php'; ?>
        </div>
        
        <!-- Кнопка добавления в корень -->
        <div class="community-add-root">
            <button class="btn btn-ghost btn-sm" onclick="showCreateModal(<?= $community['id'] ?>, null)">
                <svg width="14" height="14"><use href="#icon-plus"/></svg>
                Добавить
            </button>
        </div>
        
        <div class="community-settings-link">
            <a href="#" class="btn btn-ghost btn-sm" onclick="openCommunitySettings(<?= $community['id'] ?>)">
                <svg width="14" height="14"><use href="#icon-settings"/></svg>
                Настройки сообщества
            </a>
        </div>
    <?php endif; ?>
    
<?php else: ?>
    <!-- Гость или другой пользователь -->
    <?php if ($hasCommunity && !$communityIsEmpty): ?>
        <!-- Кнопка подписки -->
        <?php if ($user): ?>
            <div class="community-subscribe-wrap">
                <?php if ($isSubscribed): ?>
                    <button class="btn btn-secondary btn-sm" onclick="toggleSubscription(<?= $community['id'] ?>, false)">
                        <svg width="14" height="14"><use href="#icon-check"/></svg>
                        Вы подписаны
                    </button>
                <?php else: ?>
                    <button class="btn btn-primary btn-sm" onclick="toggleSubscription(<?= $community['id'] ?>, true)">
                        <svg width="14" height="14"><use href="#icon-plus"/></svg>
                        Подписаться
                    </button>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <!-- Структура сообщества для просмотра -->
        <div class="community-structure" data-community-id="<?= $community['id'] ?>">
            <?php include __DIR__ . '/community-structure.php'; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <svg width="48" height="48" class="empty-icon"><use href="#icon-message-circle"/></svg>
            <p>Сообщество пусто</p>
        </div>
    <?php endif; ?>
<?php endif; ?>


<?php include 'community-tab/modals.php'; ?>

<?php include 'community-tab/js-script.php'; ?>
