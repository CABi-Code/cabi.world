<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Http\Request;
use App\Repository\CommunityRepository;
use App\Repository\UserRepository;

class ChatController extends BaseController
{
    private CommunityRepository $communityRepo;
    private UserRepository $userRepo;

    public function __construct()
    {
        $this->communityRepo = new CommunityRepository();
        $this->userRepo = new UserRepository();
    }

    public function show(Request $request, int $chatId): void
    {
        $chat = $this->communityRepo->getChatWithCommunity($chatId);
        
        if (!$chat) {
            http_response_code(404);
            $this->render('pages/error', [
                'title' => 'Чат не найден',
                'message' => 'Запрашиваемый чат не найден',
            ]);
            return;
        }
        
        // Загружаем сообщество и владельца
        $community = $this->communityRepo->findById($chat['community_id']);
        $owner = $this->userRepo->findById($community['user_id']);
        
        // Текущий пользователь
        $user = $request->user();
        
        // Проверки прав
        $isOwner = $user && $user['id'] === $owner['id'];
        $isModerator = $user && $this->communityRepo->isModerator(
            $community['id'], 
            $user['id'], 
            'chat', 
            $chatId
        );
        $isBanned = $user && $this->communityRepo->isBanned(
            $community['id'], 
            $user['id'], 
            $chatId
        );
        
        // Настройки чата
        $settings = $this->communityRepo->getChatEffectiveSettings($chatId);
        
        $this->render('pages/chat/index', [
            'title' => $chat['name'] . ' — cabi.world',
            'chat' => $chat,
            'community' => $community,
            'owner' => $owner,
            'user' => $user,
            'isOwner' => $isOwner,
            'isModerator' => $isModerator,
            'isBanned' => $isBanned,
            'settings' => $settings,
        ]);
    }
}
