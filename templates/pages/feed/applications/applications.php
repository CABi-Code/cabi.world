<?php if (empty($applications)): ?>
    <div class="empty">
        <svg width="48" height="48"><use href="#icon-users"/></svg>
        <h2>Пока нет заявок</h2>
        <p>Будь первым! Выбери модпак и оставь заявку.</p>
        <div class="empty-actions">
            <a href="/modrinth" class="btn btn-primary">Modrinth</a>
            <a href="/curseforge" class="btn btn-secondary">CurseForge</a>
        </div>
    </div>
<?php else: ?>
    
    <div class="feed" id="feedContainer">
		<?php include_once 'feed-list.php'; ?>
    </div>
    
    <?php if ($totalPages > 1): ?>
        <div class="pagination">			
			<?php include_once 'pagination.php'; ?>
        </div>
    <?php endif; ?>
<?php endif; ?>