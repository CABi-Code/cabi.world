<?php
use React\EventLoop\Loop;
use React\Http\Browser;
use App\Repository\ModpackRepository;

$config = require CONFIG_PATH . '/app.php';
$modpackRepo = new ModpackRepository();

$page = max(1, (int)($_GET['page'] ?? 1));
$sort = $_GET['sort'] ?? 'downloads';
$view = $_COOKIE['view_mode'] ?? 'grid';

$popularModpacks = $modpackRepo->getPopularWithApplications('curseforge', 10);
$hasPopular = !empty($popularModpacks);

$apiKey = $config['curseforge_api_key'] ?? '';

if (!$apiKey) {
    $error = 'CurseForge API key not configured';
    $modpacks = [];
    $totalPages = 1;
} else {
    $limit = 20;
    $offset = ($page - 1) * $limit;
    $sortMap = ['downloads' => 2, 'updated' => 3, 'newest' => 1, 'name' => 4];
    $apiSort = $sortMap[$sort] ?? 2;
    
    $browser = new Browser();
    $data = null;
    $error = null;
    
    $apiUrl = "https://api.curseforge.com/v1/mods/search?gameId=432&classId=4471&pageSize={$limit}&index={$offset}&sortField={$apiSort}&sortOrder=desc";
    
    $browser->get($apiUrl, [
        'User-Agent' => 'CabiWorld/1.0',
        'x-api-key' => $apiKey
    ])->then(
        function ($response) use (&$data) {
            $data = json_decode((string)$response->getBody(), true);
        },
        function ($e) use (&$error) {
            $error = $e->getMessage();
        }
    );
    
    Loop::run();
    
    $modpacks = $data['data'] ?? [];
    $totalHits = $data['pagination']['totalCount'] ?? 0;
    $totalPages = max(1, (int)ceil($totalHits / $limit));
    
    $slugs = array_column($modpacks, 'slug');
    $appCounts = $modpackRepo->getApplicationCounts($slugs, 'curseforge');
}
?>

<div class="page-header">
    <h1 class="page-title">Модпаки CurseForge</h1>
</div>

<?php if ($hasPopular): ?>
<div class="popular-section">
    <div class="popular-header">
        <div>
            <div class="popular-title">🔥 С активными заявками</div>
        </div>
    </div>
    <!-- Desktop Grid -->
    <div class="popular-grid">
        <?php foreach ($popularModpacks as $mp): ?>
            <a href="/modpack/curseforge/<?= e($mp['slug']) ?>" class="popular-card">
                <div class="popular-card-icon">
                    <?php if ($mp['icon_url']): ?>
                        <img src="<?= e($mp['icon_url']) ?>" alt="">
                    <?php endif; ?>
                </div>
                <div class="popular-card-info">
                    <div class="popular-card-name"><?= e($mp['name']) ?></div>
                    <div class="popular-card-count"><?= $mp['active_app_count'] ?> заявок</div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
    <!-- Mobile Carousel -->
    <div class="popular-carousel-wrapper">
        <div class="popular-carousel">
            <?php foreach ($popularModpacks as $mp): ?>
                <a href="/modpack/curseforge/<?= e($mp['slug']) ?>" class="popular-card">
                    <div class="popular-card-icon">
                        <?php if ($mp['icon_url']): ?>
                            <img src="<?= e($mp['icon_url']) ?>" alt="">
                        <?php endif; ?>
                    </div>
                    <div class="popular-card-info">
                        <div class="popular-card-name"><?= e($mp['name']) ?></div>
                        <div class="popular-card-count"><?= $mp['active_app_count'] ?> заявок</div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if (isset($error) && $error): ?>
    <div class="alert alert-error">Ошибка: <?= e($error) ?></div>
<?php elseif (!empty($modpacks)): ?>

<div class="toolbar">
    <div class="toolbar-left">
        <span style="font-size:0.875rem;color:var(--text-secondary)">
            Найдено: <strong style="color:var(--text)"><?= number_format($totalHits ?? 0) ?></strong>
        </span>
    </div>
    <div class="toolbar-right">
        <select class="sort-select" onchange="location.href='?sort='+this.value+'&page=1'">
            <option value="downloads" <?= $sort === 'downloads' ? 'selected' : '' ?>>По загрузкам</option>
            <option value="updated" <?= $sort === 'updated' ? 'selected' : '' ?>>По обновлению</option>
            <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Новые</option>
            <option value="name" <?= $sort === 'name' ? 'selected' : '' ?>>По названию</option>
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
                <a href="/modpack/curseforge/<?= e($mp['slug']) ?>" class="mp-card-link">
                    <img src="<?= e($mp['logo']['thumbnailUrl'] ?? '') ?>" alt="" class="mp-card-img"
                         onerror="this.style.display='none'">
                    <div class="mp-card-body">
                        <div class="mp-card-info">
                            <div class="mp-card-title"><?= e($mp['name']) ?></div>
                            <div class="mp-card-author"><?= e($mp['authors'][0]['name'] ?? 'Unknown') ?></div>
                        </div>
                        <div class="mp-card-stats">
                            <span><svg width="12" height="12"><use href="#icon-download"/></svg><?= formatNumber($mp['downloadCount']) ?></span>
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