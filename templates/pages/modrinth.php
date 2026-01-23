<?php
use React\EventLoop\Loop;
use React\Http\Browser;
use App\Repository\ModpackRepository;

$config = require CONFIG_PATH . '/app.php';
$modpackRepo = new ModpackRepository();

// Параметры
$page = max(1, (int)($_GET['page'] ?? 1));
$topPage = max(1, (int)($_GET['top'] ?? 1));
$sort = $_GET['sort'] ?? 'downloads';
$view = $_COOKIE['view_mode'] ?? 'grid';

// Получить популярные модпаки с заявками
$popularModpacks = $modpackRepo->getPopularWithApplications('modrinth', 10);
$hasPopular = !empty($popularModpacks);

// API запрос
$limit = 20;
$offset = ($page - 1) * $limit;
$sortMap = ['downloads' => 'downloads', 'updated' => 'updated', 'newest' => 'newest', 'follows' => 'follows'];
$apiSort = $sortMap[$sort] ?? 'downloads';

$browser = new Browser();
$data = null;
$error = null;

$apiUrl = "https://api.modrinth.com/v2/search?facets=[[\"project_type:modpack\"]]&limit={$limit}&offset={$offset}&index={$apiSort}";

$browser->get($apiUrl, ['User-Agent' => 'CabiWorld/1.0'])->then(
    function ($response) use (&$data) {
        $data = json_decode((string)$response->getBody(), true);
    },
    function ($e) use (&$error) {
        $error = $e->getMessage();
    }
);

Loop::run();

$modpacks = $data['hits'] ?? [];
$totalHits = $data['total_hits'] ?? 0;
$totalPages = max(1, (int)ceil($totalHits / $limit));

// Получить счётчики заявок
$slugs = array_column($modpacks, 'slug');
$appCounts = $modpackRepo->getApplicationCounts($slugs, 'modrinth');
?>

<div class="page-header">
    <h1 class="page-title">Модпаки Modrinth</h1>
</div>

<?php if ($hasPopular): ?>
<div class="popular-section">
    <div class="popular-header">
        <div>
            <div class="popular-title">🔥 С активными заявками</div>
        </div>
    </div>
    <div class="popular-grid">
        <?php foreach ($popularModpacks as $mp): ?>
            <a href="/modpack/modrinth/<?= e($mp['slug']) ?>" class="popular-card">
                <div class="popular-card-icon">
                    <?php if ($mp['icon_url']): ?>
                        <img src="<?= e($mp['icon_url']) ?>" alt="">
                    <?php endif; ?>
                </div>
                <div class="popular-card-info">
                    <div class="popular-card-name"><?= e($mp['name']) ?></div>
                    <div class="popular-card-count"><?= $mp['accepted_count'] ?> заявок</div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-error">Ошибка загрузки: <?= e($error) ?></div>
<?php else: ?>

<div class="toolbar">
    <div class="toolbar-left">
        <span style="font-size:0.875rem;color:var(--text-secondary)">
            Найдено: <strong style="color:var(--text)"><?= number_format($totalHits) ?></strong>
        </span>
    </div>
    <div class="toolbar-right">
        <select class="sort-select" onchange="location.href='?sort='+this.value+'&page=1'">
            <option value="downloads" <?= $sort === 'downloads' ? 'selected' : '' ?>>По загрузкам</option>
            <option value="updated" <?= $sort === 'updated' ? 'selected' : '' ?>>По обновлению</option>
            <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Новые</option>
            <option value="follows" <?= $sort === 'follows' ? 'selected' : '' ?>>По подписчикам</option>
        </select>
        <div class="view-toggle">
            <button class="view-btn <?= $view === 'grid' ? 'active' : '' ?>" data-view="grid" title="Сетка">
                <svg width="16" height="16"><use href="#icon-grid"/></svg>
            </button>
            <button class="view-btn <?= $view === 'compact' ? 'active' : '' ?>" data-view="compact" title="Компакт">
                <svg width="16" height="16"><use href="#icon-grid-small"/></svg>
            </button>
            <button class="view-btn <?= $view === 'list' ? 'active' : '' ?>" data-view="list" title="Список">
                <svg width="16" height="16"><use href="#icon-list"/></svg>
            </button>
        </div>
    </div>
</div>

<div data-view="<?= e($view) ?>">
    <div class="modpack-grid">
        <?php foreach ($modpacks as $mp): ?>
            <?php $count = $appCounts[$mp['slug']] ?? 0; ?>
            <div class="mp-card">
                <a href="/modpack/modrinth/<?= e($mp['slug']) ?>" class="mp-card-link">
                    <img src="<?= e($mp['icon_url'] ?? '') ?>" alt="" class="mp-card-img" 
                         onerror="this.style.display='none'">
                    <div class="mp-card-body">
                        <div class="mp-card-info">
                            <div class="mp-card-title"><?= e($mp['title']) ?></div>
                            <div class="mp-card-author"><?= e($mp['author'] ?? 'Unknown') ?></div>
                        </div>
                        <div class="mp-card-stats">
                            <span><svg width="12" height="12"><use href="#icon-download"/></svg><?= number_format($mp['downloads']) ?></span>
                            <span><svg width="12" height="12"><use href="#icon-heart"/></svg><?= number_format($mp['follows']) ?></span>
                            <?php if ($count > 0): ?>
                                <span class="mp-card-badge"><?= $count ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php if ($totalPages > 1): ?>
    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="?page=<?= $page - 1 ?>&sort=<?= e($sort) ?>" class="page-item">&laquo;</a>
        <?php endif; ?>
        
        <?php
        $start = max(1, $page - 2);
        $end = min($totalPages, $page + 2);
        for ($i = $start; $i <= $end; $i++):
        ?>
            <a href="?page=<?= $i ?>&sort=<?= e($sort) ?>" class="page-item <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
        <?php endfor; ?>
        
        <?php if ($page < $totalPages): ?>
            <a href="?page=<?= $page + 1 ?>&sort=<?= e($sort) ?>" class="page-item">&raquo;</a>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php endif; ?>
