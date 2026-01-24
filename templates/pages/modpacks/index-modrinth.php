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

<?php 
$platform = 'modrinth';
include_once 'popular-section.php'; 
?>

<?php if ($error): ?>
    <div class="alert alert-error">Ошибка загрузки: <?= e($error) ?></div>
<?php else: ?>

	<?php include_once 'toolbar.php'; ?>
	
	<?php 
		include_once 'toolbar.php';
		$showFollows = false;
		include_once 'modpack-grid.php';
		include_once 'pagination.php';
	?>

<?php endif; ?>