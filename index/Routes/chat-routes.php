<?php

use App\Repository\CommunityRepository;
use App\Repository\UserRepository;

// Страница чата: /chat/{id}
if (preg_match('#^/chat/(\d+)$#', $uri, $matches)) {
    $chatId = (int) $matches[1];
    
    $communityRepo = new CommunityRepository();
    $userRepo = new UserRepository();
    
    // Получаем чат с информацией о сообществе
    $chat = $communityRepo->getChatWithCommunity($chatId);
    
    if (!$chat) {
        http_response_code(404);
        $title = 'Чат не найден';
        $content = '<div class="alert alert-error">Чат не найден</div>';
        require TEMPLATES_PATH . '/layouts/main.php';
        exit;
    }
    
    // Получаем сообщество и владельца
    $community = $communityRepo->findById($chat['community_id']);
    $owner = $userRepo->findById($community['user_id']);
    
    // Проверяем доступ
    $isOwner = $user && $user['id'] === $community['user_id'];
    $isModerator = $user && $communityRepo->isModerator($community['id'], $user['id'], 'chat', $chatId);
    $isBanned = $user && $communityRepo->isBanned($community['id'], $user['id'], $chatId);
    
    // Получаем эффективные настройки
    $settings = $communityRepo->getChatEffectiveSettings($chatId);
    
    $title = $chat['name'] . ' — ' . $owner['username'];
    ob_start();
    require TEMPLATES_PATH . '/pages/chat/index.php';
    $content = ob_get_clean();
    require TEMPLATES_PATH . '/layouts/main.php';
    exit;
}

?>
