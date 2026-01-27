<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Http\Request;
use App\Repository\CommunityRepository;

class ChatController
{
    private CommunityRepository $communityRepo;

    public function __construct()
    {
        $this->communityRepo = new CommunityRepository();
    }

    public function show(Request $request, int $chatId): void
    {
        $chat = $this->communityRepo->getChat($chatId);
        
        if (!$chat) {
            http_response_code(404);
            $title = 'Чат не найден';
            $content = '<div class="alert alert-error">Чат не найден</div>';
            require TEMPLATES_PATH . '/layouts/main.php';
            return;
        }
        
        $title = $chat['name'] . ' — cabi.world';
        ob_start();
        require TEMPLATES_PATH . '/pages/chat/index.php';
        $content = ob_get_clean();
        require TEMPLATES_PATH . '/layouts/main.php';
    }
}
