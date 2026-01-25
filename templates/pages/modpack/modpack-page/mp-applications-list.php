<?php if (!empty($applications)): ?>
    <div class="modpack-section">
        <h2 class="section-title">
            Заявки
            <span class="section-count">(<?= $applicationCount ?>)</span>
        </h2>
        <div class="app-list">
            <?php foreach ($applications as $app): ?>
                <?php 
                // На странице модпака не показываем модпак, но показываем пользователя
                $showModpack = false;
                $showUser = true;
                include TEMPLATES_PATH . '/components/application-card/card.php'; 
                ?>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>
