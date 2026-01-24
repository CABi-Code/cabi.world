<?php

use App\Repository\UserRepository;

// Profile routes - теперь без /profile, просто /@username
if (preg_match('#^/@([a-zA-Z0-9_-]+)$#', $uri, $matches)) {
    $profileLogin = $matches[1];
    $userRepo = new UserRepository();
    $profileUser = $userRepo->findByLogin($profileLogin);
    
    if (!$profileUser) {
        http_response_code(404);
        $title = 'Пользователь не найден';
        $content = '<div class="alert alert-error">Пользователь не найден</div>';
        require TEMPLATES_PATH . '/layouts/main.php';
        exit;
    }
    
    $uploadPath = ROOT_PATH . '/public';
    $fileUpdates = [];
    if ($profileUser['avatar'] && !file_exists($uploadPath . $profileUser['avatar'])) {
        $fileUpdates['avatar'] = null;
        $profileUser['avatar'] = null;
    }
    if ($profileUser['banner'] && !file_exists($uploadPath . $profileUser['banner'])) {
        $fileUpdates['banner'] = null;
        $profileUser['banner'] = null;
    }
    if (!empty($fileUpdates)) {
        $userRepo->update($profileUser['id'], $fileUpdates);
    }
    
    $isOwner = $user && $user['id'] === $profileUser['id'];
    $title = $profileUser['username'] . ' — cabi.world';
    ob_start();
    require TEMPLATES_PATH . '/pages/profile.php';
    $content = ob_get_clean();
    require TEMPLATES_PATH . '/layouts/main.php';
    exit;
}

?>
