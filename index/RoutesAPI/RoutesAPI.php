<?php


use App\Middleware\AuthMiddleware;
use App\Middleware\AdminMiddleware;
use App\Auth\AuthManager;
use App\Repository\UserRepository;
use App\Repository\ModpackRepository;
use App\Repository\ApplicationRepository;
use App\Repository\NotificationRepository;
use App\Service\ImageService;
use App\Core\Database;
use App\Core\Role;


// === API Routes ===
if (str_starts_with($uri, '/api/')) {
    header('Content-Type: application/json; charset=utf-8');
    
    // Обработка ошибок
    set_exception_handler(function($e) {
        json([
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            '_db_errors' => Database::getErrors()
        ], 500);
    });
    
    $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if ($method !== 'GET') {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $csrfToken)) {
            json(['error' => 'Invalid CSRF token'], 403);
        }
    }

    $apiRoute = substr($uri, 4);
    $input = json_decode(file_get_contents('php://input'), true) ?? [];

    // === Admin API Routes ===
    if (str_starts_with($apiRoute, '/admin/')) {
		include_once 'AdminRoutesAPI.php';
    }
	
	include_once 'switch.php';
	
	
    exit;
}

?>