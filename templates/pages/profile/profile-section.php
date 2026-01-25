<?php if (!empty($applications)): ?>
    <div class="profile-section">
        <h2 class="section-title">
            <?= $isOwner ? 'Мои заявки' : 'Заявки' ?>
            <span class="section-count">(<?= count($applications) ?>)</span>
        </h2>
        <div class="app-list">
            <?php foreach ($applications as $app): ?>
                <?php 
                $showModpack = true;
                $showUser = false;
                include TEMPLATES_PATH . '/components/application-card/card.php'; 
                ?>
            <?php endforeach; ?>
        </div>
    </div>
<?php elseif ($isOwner): ?>
    <div class="profile-section">
        <div style="text-align:center;padding:1.5rem;color:var(--text-secondary);">
            <p style="margin-bottom:0.75rem;">У вас пока нет заявок</p>
            <a href="/modrinth" class="btn btn-primary btn-sm">Найти модпак</a>
        </div>
    </div>
<?php endif; ?>
