<?php

use App\Repository\ApplicationRepository;
use App\Core\Role;

$appRepo = new ApplicationRepository();
$page = max(1, (int)($_GET['page'] ?? 1));
$sort = $_GET['sort'] ?? 'date';
$limit = 20;
$offset = ($page - 1) * $limit;

$applications = $appRepo->findAllAccepted($limit, $offset, $sort);

ob_start();
foreach ($applications as $app) {
	$images = $appRepo->getImages($app['id']);
	$avatarStyle = '';
	if (empty($app['avatar'])) {
		$colors = explode(',', $app['avatar_bg_value'] ?? '#3b82f6,#8b5cf6');
		$avatarStyle = 'background:linear-gradient(135deg,' . $colors[0] . ',' . ($colors[1] ?? $colors[0]) . ')';
	}
	
	include_once 'feed-card.php';
	
}
$html = ob_get_clean();
json(['success' => true, 'html' => $html]);

?>