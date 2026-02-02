<?php

namespace App\Controllers\Api\ChatController;

use App\Http\Request;
use App\Http\Response;
use App\Repository\ChatMessageRepository;
use App\Core\Role;

trait MessagesTrait
{
    /**
     * Получить сообщения чата
     */
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
            $messages = $this->enrichMessagesWithUserData($messages, $user);
        }
        
        Response::json(['messages' => $messages]);
    }

    /**
     * Получить новые сообщения (для polling)
     */
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
            $messages = $this->enrichMessagesWithUserData($messages, $user);
        }
        
        Response::json(['messages' => $messages]);
    }

    /**
     * Отправить сообщение
     */
    public function sendMessage(Request $request): void
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
                Response::error("Подождите ещё {$remaining} сек.", 429);
                return;
            }
        }
        
        // Проверка изображений
        $hasImages = $request->hasFile('images');
        
        if ($hasImages) {
            if (!Role::isPremium($user['role'])) {
                Response::error('Загрузка изображений доступна только премиум', 403);
                return;
            }
            
            if ($settings['files_disabled']) {
                Response::error('Загрузка файлов отключена', 403);
                return;
            }
        }
        
        // Валидация
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
            $this->uploadMessageImages($request, $messageId, $chatId);
        }
        
        Response::json(['id' => $messageId, 'success' => true]);
    }

    /**
     * Загрузить изображения для сообщения
     */
    private function uploadMessageImages(Request $request, int $messageId, int $chatId): void
    {
        $files = $request->files('images');
        $count = min(count($files), ChatMessageRepository::MAX_IMAGES);
        
        for ($i = 0; $i < $count; $i++) {
            $file = $files[$i];
            if ($file['error'] !== UPLOAD_ERR_OK) continue;
            
            if ($file['size'] > 5 * 1024 * 1024) continue;
            
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!in_array($mimeType, $allowedTypes)) continue;
            
            $path = $this->imageService->uploadFile($file['tmp_name'], 'chats/' . $chatId);
            if ($path) {
                $this->messageRepo->addImage($messageId, $path, $i);
            }
        }
    }

    /**
     * Обогатить сообщения данными пользователя
     */
    private function enrichMessagesWithUserData(array $messages, array $user): array
    {
        $messageIds = array_column($messages, 'id');
        $userLikes = $this->messageRepo->getUserLikesForMessages($messageIds, $user['id']);
        
        foreach ($messages as &$msg) {
            $msg['liked'] = in_array($msg['id'], $userLikes);
            
            if (!empty($msg['is_poll'])) {
                $poll = $this->messageRepo->getPoll($msg['id']);
                if ($poll) {
                    $userVotes = $this->messageRepo->getUserVotes($poll['id'], $user['id']);
                    $votedOptionIds = array_column($userVotes, 'option_id');
                    foreach ($poll['options'] as &$opt) {
                        $opt['user_voted'] = in_array($opt['id'], $votedOptionIds);
                    }
                    $msg['poll'] = $poll;
                }
            }
        }
        
        return $messages;
    }
}
