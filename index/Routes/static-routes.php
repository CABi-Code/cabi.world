<?php

use App\Auth\AuthManager;

// Static routes
if (isset($routes[$uri])) {
    $route = $routes[$uri];
    
    if (isset($route['action']) && $route['action'] === 'logout') {
        $authManager = new AuthManager();
        $refreshToken = $_COOKIE['refresh_token'] ?? '';
        if ($refreshToken) $authManager->logout($refreshToken);
        $authManager->clearTokenCookies();
        redirect('/login');
    }
    
    if (isset($route['guest']) && $route['guest'] && $user) {
        redirect('/@' . $user['login']);
    }
    
    if (isset($route['auth']) && $route['auth'] && !$user) {
        redirect('/login');
    }
    
    $pageName = $route['page'];
    $pageFile = TEMPLATES_PATH . '/pages/' . $pageName . '.php';
    
    if (file_exists($pageFile)) {
        $title = match($pageName) {
            'home' => 'Найди компанию — cabi.world',
            'modrinth' => 'Модпаки Modrinth — cabi.world',
            'curseforge' => 'Модпаки CurseForge — cabi.world',
            'login' => 'Вход — cabi.world',
            'register' => 'Регистрация — cabi.world',
            'settings' => 'Настройки — cabi.world',
            default => 'cabi.world'
        };
        
        ob_start();
        require $pageFile;
        $content = ob_get_clean();
        
        if (in_array($pageName, ['login', 'register', 'forgot-password'])) {
            require TEMPLATES_PATH . '/layouts/auth.php';
        } else {
            require TEMPLATES_PATH . '/layouts/main.php';
        }
        exit;
    }
}

?>
