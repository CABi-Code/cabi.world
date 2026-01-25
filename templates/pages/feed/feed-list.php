<?php if (!empty($applications)): ?>
    <div class="feed-section">
        <h2 class="section-title">
            Заявки
            <span class="section-count">(<?= $applicationCount ?? count($applications) ?>)</span>
        </h2>
        <div class="app-list">
            <?php foreach ($applications as $app): ?>
                <?php 
                $showModpack = true;
                $showUser = true;
                include TEMPLATES_PATH . '/components/application-card/card.php'; 
                ?>
            <?php endforeach; ?>
        </div>
    </div>
<?php else: ?>
    <div class="feed-section">
        <div style="text-align:center;padding:2rem;color:var(--text-secondary);">
            <p>Пока нет заявок</p>
        </div>
    </div>
<?php endif; ?>
