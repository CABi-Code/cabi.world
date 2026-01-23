<?php
use App\Repository\ApplicationRepository;

$appRepo = new ApplicationRepository();
$page = max(1, (int)($_GET['page'] ?? 1));
$sort = $_GET['sort'] ?? 'date';
$limit = 20;
$offset = ($page - 1) * $limit;

$applications = $appRepo->findAllAccepted($limit, $offset, $sort);
$totalCount = $appRepo->countAllAccepted();
$totalPages = max(1, (int)ceil($totalCount / $limit));

// Функция для получения стиля аватара
function getAvatarStyle($app) {
    if (!empty($app['avatar'])) return '';
    $colors = explode(',', $app['avatar_bg_value'] ?? '#3b82f6,#8b5cf6');
    return 'background:linear-gradient(135deg,' . $colors[0] . ',' . $colors[1] ?? $colors[0] . ')';
}
?>

<div class="hero">
    <h1 class="hero-title">Найди компанию для игры</h1>
    <p class="hero-subtitle">Смотри заявки игроков и находи тиммейтов</p>
</div>

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
    <div class="toolbar">
        <div class="toolbar-left">
            <span style="font-size:0.875rem;color:var(--text-secondary)">
                Заявок: <strong style="color:var(--text)"><?= number_format($totalCount) ?></strong>
            </span>
        </div>
        <div class="toolbar-right">
            <select class="sort-select" id="feedSortSelect">
                <option value="date" <?= $sort === 'date' ? 'selected' : '' ?>>По дате</option>
                <option value="popular" <?= $sort === 'popular' ? 'selected' : '' ?>>По популярности</option>
                <option value="relevance" <?= $sort === 'relevance' ? 'selected' : '' ?>>По актуальности</option>
            </select>
        </div>
    </div>
    
    <div class="feed" id="feedContainer">
        <?php foreach ($applications as $app): ?>
            <?php $images = $appRepo->getImages($app['id']); ?>
            <div class="feed-card">
                <div class="feed-header">
                    <a href="/profile/@<?= e($app['login']) ?>" class="feed-user">
                        <div class="feed-avatar" style="<?= getAvatarStyle($app) ?>">
                            <?php if (!empty($app['avatar'])): ?>
                                <img src="<?= e($app['avatar']) ?>" alt="">
                            <?php else: ?>
                                <?= mb_strtoupper(mb_substr($app['username'], 0, 1)) ?>
                            <?php endif; ?>
                        </div>
                        <div>
                            <div class="feed-name"><?= e($app['username']) ?></div>
                            <div class="feed-login">@<?= e($app['login']) ?></div>
                        </div>
                    </a>
                    <a href="/modpack/<?= e($app['platform']) ?>/<?= e($app['slug']) ?>" class="feed-modpack">
                        <?php if ($app['icon_url']): ?>
                            <img src="<?= e($app['icon_url']) ?>" alt="" class="feed-mp-icon">
                        <?php endif; ?>
                        <?= e($app['modpack_name']) ?>
                    </a>
                </div>
                
                <div class="feed-body">
                    <p class="feed-message"><?= nl2br(e($app['message'])) ?></p>
                    
                    <?php if (!empty($app['relevant_until'])): ?>
                        <?php $expired = strtotime($app['relevant_until']) < time(); ?>
                        <div style="font-size:0.8125rem;color:<?= $expired ? 'var(--danger)' : 'var(--text-muted)' ?>;margin-top:0.5rem;">
                            <svg width="12" height="12" style="vertical-align:-2px;"><use href="#icon-clock"/></svg>
                            <?= $expired ? 'Истёк:' : 'До:' ?> <?= date('d.m.Y', strtotime($app['relevant_until'])) ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($images)): ?>
                        <div class="feed-images">
                            <?php foreach ($images as $img): ?>
                                <a href="<?= e($img['image_path']) ?>" data-lightbox>
                                    <img src="<?= e($img['image_path']) ?>" alt="" class="feed-img">
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="feed-footer">
                    <div class="feed-contacts">
                        <?php if ($app['contact_discord']): ?>
                            <span class="contact-btn discord">
                                <svg width="14" height="14"><use href="#icon-discord"/></svg>
                                <?= e($app['contact_discord']) ?>
                            </span>
                        <?php endif; ?>
                        <?php if ($app['contact_telegram']): ?>
                            <a href="https://t.me/<?= e(ltrim($app['contact_telegram'], '@')) ?>" class="contact-btn telegram" target="_blank">
                                <svg width="14" height="14"><use href="#icon-telegram"/></svg>
                                <?= e($app['contact_telegram']) ?>
                            </a>
                        <?php endif; ?>
                        <?php if ($app['contact_vk']): ?>
                            <a href="https://vk.com/<?= e($app['contact_vk']) ?>" class="contact-btn vk" target="_blank">
                                <svg width="14" height="14"><use href="#icon-vk"/></svg>
                                <?= e($app['contact_vk']) ?>
                            </a>
                        <?php endif; ?>
                    </div>
                    <span class="feed-date"><?= date('d.m.Y', strtotime($app['created_at'])) ?></span>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=<?= $page - 1 ?>&sort=<?= e($sort) ?>" class="page-item">&laquo;</a>
            <?php endif; ?>
            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                <a href="?page=<?= $i ?>&sort=<?= e($sort) ?>" class="page-item <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
            <?php if ($page < $totalPages): ?>
                <a href="?page=<?= $page + 1 ?>&sort=<?= e($sort) ?>" class="page-item">&raquo;</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
<?php endif; ?>

<div id="lightbox" class="lightbox" style="display:none;">
    <button class="lightbox-close" data-close>&times;</button>
    <img src="" alt="" class="lightbox-img" id="lightboxImg">
</div>