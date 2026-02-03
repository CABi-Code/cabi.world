<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Http\Request;
use App\Repository\UserFolderRepository;
use App\Repository\UserRepository;

class ChatController extends BaseController
{
    private UserFolderRepository $folderRepo;
    private UserRepository $userRepo;

    public function __construct()
    {
        $this->folderRepo = new UserFolderRepository();
        $this->userRepo = new UserRepository();
    }

    public function show(Request $request, int $chatId): void
    {
        // Чат теперь в user_folder_items
        $chat = $this->folderRepo->getChatWithOwner($chatId);
        
        if (!$chat) {
            $this->notFound('Чат не найден');
            return;
        }
        
        $owner = $this->userRepo->findById($chat['user_id']);
        $user = $request->user();
        
        $isOwner = $user && $user['id'] === $owner['id'];
        $settings = $this->folderRepo->getChatSettings($chatId);
        
        // Проверка бана (упрощённая - можно расширить)
        $isBanned = false;
        $isModerator = $isOwner;
        
        $this->render('pages/chat/index', [
            'title' => $chat['name'] . ' — cabi.world',
            'chat' => $chat,
            'owner' => $owner,
            'user' => $user,
            'isOwner' => $isOwner,
            'isModerator' => $isModerator,
            'isBanned' => $isBanned,
            'settings' => $settings,
        ]);
    }
}
