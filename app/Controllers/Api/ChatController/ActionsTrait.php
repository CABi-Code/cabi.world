<?php

namespace App\Controllers\Api\ChatController;

use App\Http\Request;
use App\Http\Response;

trait ActionsTrait
{
    /**
     * Удалить сообщение
     */
    public function deleteMessage(Request $request): void
    {
        $user = $request->user();
        if (!$user) {
            Response::error('Unauthorized', 401);
            return;
        }

        $messageId = (int)($request->get('message_id', 0));
        
        $message = $this->messageRepo->getMessage($messageId);
        if (!$message) {
            Response::error('Message not found', 404);
            return;
        }
        
        // Проверяем права: автор или владелец сообщества
        $chat = $this->communityRepo->getChatWithCommunity($message['chat_id']);
        $isAuthor = $message['user_id'] === $user['id'];
        $isOwner = $chat && $chat['owner_id'] === $user['id'];
        
        if (!$isAuthor && !$isOwner) {
            Response::error('Forbidden', 403);
            return;
        }
        
        $this->messageRepo->delete($messageId);
        Response::json(['success' => true]);
    }

    /**
     * Поставить/убрать лайк
     */
    public function toggleLike(Request $request): void
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

    /**
     * Создать опрос
     */
    public function createPoll(Request $request): void
    {
        $user = $request->user();
        if (!$user) {
            Response::error('Unauthorized', 401);
            return;
        }

        $chatId = (int)($request->get('chat_id', 0));
        $question = trim($request->get('question', ''));
        $options = $request->get('options', []);
        
        if (empty($question)) {
            Response::error('Введите вопрос', 400);
            return;
        }
        
        if (!is_array($options) || count($options) < 2) {
            Response::error('Добавьте минимум 2 варианта ответа', 400);
            return;
        }
        
        $chat = $this->communityRepo->getChatWithCommunity($chatId);
        if (!$chat) {
            Response::error('Chat not found', 404);
            return;
        }
        
        $community = $this->communityRepo->findById($chat['community_id']);
        
        // Проверка бана
        if ($this->communityRepo->isBanned($community['id'], $user['id'], $chatId)) {
            Response::error('Вы заблокированы', 403);
            return;
        }
        
        $messageId = $this->messageRepo->createPoll($chatId, $user['id'], $question, $options);
        
        Response::json(['id' => $messageId, 'success' => true]);
    }

    /**
     * Проголосовать в опросе
     */
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

    /**
     * Обновить настройки чата (для владельца)
     */
    public function updateSettings(Request $request): void
    {
        $user = $request->user();
        if (!$user) {
            Response::error('Unauthorized', 401);
            return;
        }

        $chatId = (int)($request->get('chat_id', 0));
        
        $chat = $this->communityRepo->getChatWithCommunity($chatId);
        if (!$chat || $chat['owner_id'] !== $user['id']) {
            Response::error('Forbidden', 403);
            return;
        }
        
        $data = $request->only(['name', 'description', 'message_timeout', 'files_disabled', 'messages_disabled']);
        $this->communityRepo->updateChat($chatId, $data);
        
        Response::json(['success' => true]);
    }
}
