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

<?php 
$platform = 'curseforge';
include_once 'popular-section.php'; 
?>

<?php if (isset($error) && $error): ?>
    <div class="alert alert-error">Ошибка: <?= e($error) ?></div>
<?php elseif (!empty($modpacks)): ?>


	<?php 
		include_once 'toolbar.php';
		$showFollows = false;
		include_once 'modpack-grid.php';
		include_once 'pagination.php';
	?>
	

	

<?php endif; ?>