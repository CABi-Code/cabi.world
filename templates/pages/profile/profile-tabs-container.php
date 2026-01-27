<div class="profile-tabs-container">
    <!-- Навигация вкладок -->
    <div class="profile-tabs">
        <button 
            class="profile-tab <?= $activeTab === 'community' ? 'active' : '' ?> <?= !$canViewCommunity ? 'disabled' : '' ?>" 
            data-tab="community"
            <?= !$canViewCommunity ? 'disabled title="Сообщество пусто"' : '' ?>
        >
            <svg width="16" height="16"><use href="#icon-message-circle"/></svg>
            Моё сообщество
        </button>
        
        <button 
            class="profile-tab <?= $activeTab === 'applications' ? 'active' : '' ?>" 
            data-tab="applications"
        >
            <svg width="16" height="16"><use href="#icon-send"/></svg>
            <?= $isOwner ? 'Мои заявки' : 'Заявки' ?>
            <?php if (!empty($applications)): ?>
                <span class="tab-count"><?= count($applications) ?></span>
            <?php endif; ?>
        </button>
        
        <?php if ($showSubscriptions): ?>
        <button 
            class="profile-tab <?= $activeTab === 'subscriptions' ? 'active' : '' ?>" 
            data-tab="subscriptions"
        >
            <svg width="16" height="16"><use href="#icon-star"/></svg>
            Подписки
            <?php if ($subscriptionsCount > 0): ?>
                <span class="tab-count"><?= $subscriptionsCount ?></span>
            <?php endif; ?>
        </button>
        <?php endif; ?>
    </div>
    
    <!-- Контент вкладок -->
    <div class="profile-tab-content">
        <!-- Вкладка: Заявки -->
        <div class="tab-pane <?= $activeTab === 'applications' ? 'active' : '' ?>" id="tab-applications">
            <?php include __DIR__ . '/tabs/applications-tab.php'; ?>
        </div>
        
        <!-- Вкладка: Сообщество -->
        <div class="tab-pane <?= $activeTab === 'community' ? 'active' : '' ?>" id="tab-community">
            <?php include __DIR__ . '/tabs/community-tab.php'; ?>
        </div>
        
        <!-- Вкладка: Подписки -->
        <?php if ($showSubscriptions): ?>
        <div class="tab-pane <?= $activeTab === 'subscriptions' ? 'active' : '' ?>" id="tab-subscriptions">
            <?php include __DIR__ . '/tabs/subscriptions-tab.php'; ?>
        </div>
        <?php endif; ?>
    </div>
</div>