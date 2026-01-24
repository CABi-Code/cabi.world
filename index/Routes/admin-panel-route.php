<?php

use App\Middleware\AdminMiddleware;
use App\Repository\ApplicationRepository;

// Admin panel route
if ($uri === '/admin' || str_starts_with($uri, '/admin/')) {
    $adminMiddleware = new AdminMiddleware();
    $adminUser = $adminMiddleware->requireModerator();
    
    $appRepo = new ApplicationRepository();
    $page = max(1, (int)($_GET['page'] ?? 1));
    $status = $_GET['status'] ?? null;
    $limit = 20;
    $offset = ($page - 1) * $limit;
    
    // Валидация статуса
    if ($status && !in_array($status, ['pending', 'accepted', 'rejected'])) {
        $status = null;
    }
    
    $applications = $appRepo->findAllForAdmin($limit, $offset, $status);
    $totalCount = $appRepo->countAllForAdmin($status);
    $totalPages = max(1, (int)ceil($totalCount / $limit));
    $pendingCount = $appRepo->countPending();
    
    $title = 'Панель управления — cabi.world';
    ob_start();
    require TEMPLATES_PATH . '/pages/admin/index.php';
    $content = ob_get_clean();
    require TEMPLATES_PATH . '/layouts/main.php';
    exit;
}

?>