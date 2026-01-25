<?php if (!empty($applications)): ?>
    <div class="feed-section">
		<div class="toolbar">
			<div class="toolbar-left">
				<h2 class="section-title">
					Заявки
					<span class="section-count">(<?= $applicationCount ?? count($applications) ?>)</span>
				</h2>
			</div>
			<div class="toolbar-right">
				<select class="sort-select" id="feedSortSelect">
					<option value="date" <?= $sort === 'date' ? 'selected' : '' ?>>По дате</option>
					<option value="popular" <?= $sort === 'popular' ? 'selected' : '' ?>>По популярности</option>
					<option value="relevance" <?= $sort === 'relevance' ? 'selected' : '' ?>>По актуальности</option>
				</select>
			</div>
		</div>
        <div class="app-list">
            <?php foreach ($applications as $app): ?>
			<div class="feed-card">
                <?php 
                $showModpack = true;
                $showUser = true;
                include TEMPLATES_PATH . '/components/application-card/card.php'; 
                ?>
			</div>
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
