<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Http\Request;
use App\Http\Response;
use App\Repository\CommunityRepository;
use App\Repository\ChatMessageRepository;
use App\Service\ImageService;
use App\Core\Role;

class ChatController
{
    private CommunityRepository $communityRepo;
    private ChatMessageRepository $messageRepo;
    private ImageService $imageService;

    public function __construct()
    {
        $this->communityRepo = new CommunityRepository();
        $this->messageRepo = new ChatMessageRepository();
        $this->imageService = new ImageService();
    }

    public function getMessages(Request $request): void
    {
        $chatId = (int)($request->query('chat_id', 0));
        $beforeId = $request->query('before_id') ? (int)$request->query('before_id') : null;
        $limit = min(50, max(10, (int)($request->query('limit', 50))));
        
        $chat = $this->communityRepo->getChat($chatId);
        if (!$chat) {
            Response::error('Chat not found', 404);
            return;
        }
        
        $messages = $this->messageRepo->getMessages($chatId, $limit, $beforeId);
        
        // Добавляем информацию о лайках пользователя
        $user = $request->user();
        if ($user && !empty($messages)) {
            $messageIds = array_column($messages, 'id');
            $userLikes = $this->messageRepo->getUserLikesForMessages($messageIds, $user['id']);
            
            foreach ($messages as &$msg) {
                $msg['liked'] = in_array($msg['id'], $userLikes);
                
                if ($msg['is_poll']) {
                    $poll = $this->messageRepo->getPoll($msg['id']);
                    if ($poll && $user) {
                        $userVotes = $this->messageRepo->getUserVotes($poll['id'], $user['id']);
                        $votedOptionIds = array_column($userVotes, 'option_id');
                        foreach ($poll['options'] as &$opt) {
                            $opt['user_voted'] = in_array($opt['id'], $votedOptionIds);
                        }
                    }
                    $msg['poll'] = $poll;
                }
            }
        }
        
        Response::json(['messages' => $messages]);
    }

    public function getNewMessages(Request $request): void
    {
        $chatId = (int)($request->query('chat_id', 0));
        $afterId = (int)($request->query('after_id', 0));
        
        $chat = $this->communityRepo->getChat($chatId);
        if (!$chat) {
            Response::error('Chat not found', 404);
            return;
        }
        
        $messages = $this->messageRepo->getNewMessages($chatId, $afterId);
        
        $user = $request->user();
        if ($user && !empty($messages)) {
            $messageIds = array_column($messages, 'id');
            $userLikes = $this->messageRepo->getUserLikesForMessages($messageIds, $user['id']);
            
            foreach ($messages as &$msg) {
                $msg['liked'] = in_array($msg['id'], $userLikes);
                
                if ($msg['is_poll']) {
                    $poll = $this->messageRepo->getPoll($msg['id']);
                    if ($poll && $user) {
                        $userVotes = $this->messageRepo->getUserVotes($poll['id'], $user['id']);
                        $votedOptionIds = array_column($userVotes, 'option_id');
                        foreach ($poll['options'] as &$opt) {
                            $opt['user_voted'] = in_array($opt['id'], $votedOptionIds);
                        }
                    }
                    $msg['poll'] = $poll;
                }
            }
        }
        
        Response::json(['messages' => $messages]);
    }

    public function send(Request $request): void
    {
        $user = $request->user();
        if (!$user) {
            Response::error('Unauthorized', 401);
            return;
        }

        $chatId = (int)($request->get('chat_id', 0));
        $message = trim($request->get('message', ''));
        
        $chat = $this->communityRepo->getChatWithCommunity($chatId);
        if (!$chat) {
            Response::error('Chat not found', 404);
            return;
        }
        
        $community = $this->communityRepo->findById($chat['community_id']);
        
        // Проверка бана
        if ($this->communityRepo->isBanned($community['id'], $user['id'], $chatId)) {
            Response::error('Вы заблокированы в этом чате', 403);
            return;
        }
        
        // Проверка настроек
        $settings = $this->communityRepo->getChatEffectiveSettings($chatId);
        
        if ($settings['messages_disabled'] && $community['user_id'] !== $user['id']) {
            Response::error('Отправка сообщений отключена', 403);
            return;
        }
        
        // Проверка тайм-аута
        $timeout = $settings['message_timeout'] ?? 0;
        if ($timeout > 0 && $community['user_id'] !== $user['id']) {
            $remaining = $this->messageRepo->getTimeUntilCanSend($chatId, $user['id'], $timeout);
            if ($remaining > 0) {
                Response::error("Подождите ещё {$remaining} сек. перед отправкой", 429);
                return;
            }
        }
        
        // Проверка изображений
        $hasImages = $request->hasFile('images');
        
        if ($hasImages) {
            if (!Role::isPremium($user['role'])) {
                Response::error('Загрузка изображений доступна только премиум пользователям', 403);
                return;
            }
            
            if ($settings['files_disabled']) {
                Response::error('Загрузка файлов отключена', 403);
                return;
            }
        }
        
        // Валидация сообщения
        if (empty($message) && !$hasImages) {
            Response::error('Введите сообщение или прикрепите изображение', 400);
            return;
        }
        
        if (mb_strlen($message) > ChatMessageRepository::MAX_MESSAGE_LENGTH) {
            Response::error('Сообщение слишком длинное', 400);
            return;
        }
        
        // Создаём сообщение
        $messageId = $this->messageRepo->create($chatId, $user['id'], $message ?: null);
        
        // Загружаем изображения
        if ($hasImages) {
            $files = $request->files('images');
            $count = min(count($files), ChatMessageRepository::MAX_IMAGES);
            
            foreach ($files as $i => $file) {
                if ($file['error'] !== UPLOAD_ERR_OK) {
                    continue;
                }
                
                // Проверка размера
                if ($file['size'] > 5 * 1024 * 1024) {
                    Response::error('Payload Too Large (file #' . ($i + 1) . ')', 413);
                    return;
                }
                
                // Проверка типа
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_file($finfo, $file['tmp_name']);
                finfo_close($finfo);
                
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if (!in_array($mimeType, $allowedTypes)) {
                    Response::error('Unsupported Media Type (file #' . ($i + 1) . ')', 415);
                    return;
                }
                
                $fileArray = [
                    'tmp_name' => $file['tmp_name'],
                    'size' => $file['size'],
                    'error' => $file['error']
                ];
                $path = $this->imageService->uploadFile($file['tmp_name'], 'chats/' . $chatId);
                if ($path) {
                    $this->messageRepo->addImage($messageId, $path, $i);
                }
            }
        }
        
        Response::json(['id' => $messageId]);
    }

    public function like(Request $request): void
    {
        $user = $request->user();
        if (!$user) {
            Response::error('Unauthorized', 401);
            return;
        }

        $messageId = (int)($request->get('message_id', 0));
        $result = $this->messageRepo->toggleLike($messageId, $user['id']);
        
        Response::json($result);
    }

    public function votePoll(Request $request): void
    {
        $user = $request->user();
        if (!$user) {
            Response::error('Unauthorized', 401);
            return;
        }

        $optionId = (int)($request->get('option_id', 0));
        $result = $this->messageRepo->votePoll($optionId, $user['id']);
        
        Response::json($result);
    }
}
