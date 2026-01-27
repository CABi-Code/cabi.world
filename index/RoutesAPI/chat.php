<?php

use App\Repository\CommunityRepository;
use App\Repository\ChatMessageRepository;
use App\Service\ImageService;
use App\Core\Role;

// === Chat Messages API ===

// Получить сообщения чата
if ($apiRoute === '/chat/messages' && $method === 'GET') {
    $chatId = (int)($_GET['chat_id'] ?? 0);
    $beforeId = isset($_GET['before_id']) ? (int)$_GET['before_id'] : null;
    $limit = min(50, max(10, (int)($_GET['limit'] ?? 50)));
    
    $communityRepo = new CommunityRepository();
    $messageRepo = new ChatMessageRepository();
    
    $chat = $communityRepo->getChat($chatId);
    if (!$chat) {
        json(['error' => 'Chat not found'], 404);
    }
    
    $messages = $messageRepo->getMessages($chatId, $limit, $beforeId);
    
    // Добавляем информацию о лайках пользователя
    if ($user && !empty($messages)) {
        $messageIds = array_column($messages, 'id');
        $userLikes = $messageRepo->getUserLikesForMessages($messageIds, $user['id']);
        
        foreach ($messages as &$msg) {
            $msg['liked'] = in_array($msg['id'], $userLikes);
            
            // Если сообщение - опрос, загружаем данные
            if ($msg['is_poll']) {
                $poll = $messageRepo->getPoll($msg['id']);
                if ($poll && $user) {
                    $userVotes = $messageRepo->getUserVotes($poll['id'], $user['id']);
                    $votedOptionIds = array_column($userVotes, 'option_id');
                    foreach ($poll['options'] as &$opt) {
                        $opt['user_voted'] = in_array($opt['id'], $votedOptionIds);
                    }
                }
                $msg['poll'] = $poll;
            }
        }
    }
    
    json(['messages' => $messages]);
}

// Получить новые сообщения
if ($apiRoute === '/chat/messages/new' && $method === 'GET') {
    $chatId = (int)($_GET['chat_id'] ?? 0);
    $afterId = (int)($_GET['after_id'] ?? 0);
    
    $communityRepo = new CommunityRepository();
    $messageRepo = new ChatMessageRepository();
    
    $chat = $communityRepo->getChat($chatId);
    if (!$chat) {
        json(['error' => 'Chat not found'], 404);
    }
    
    $messages = $messageRepo->getNewMessages($chatId, $afterId);
    
    // Добавляем информацию о лайках
    if ($user && !empty($messages)) {
        $messageIds = array_column($messages, 'id');
        $userLikes = $messageRepo->getUserLikesForMessages($messageIds, $user['id']);
        
        foreach ($messages as &$msg) {
            $msg['liked'] = in_array($msg['id'], $userLikes);
            
            if ($msg['is_poll']) {
                $poll = $messageRepo->getPoll($msg['id']);
                if ($poll && $user) {
                    $userVotes = $messageRepo->getUserVotes($poll['id'], $user['id']);
                    $votedOptionIds = array_column($userVotes, 'option_id');
                    foreach ($poll['options'] as &$opt) {
                        $opt['user_voted'] = in_array($opt['id'], $votedOptionIds);
                    }
                }
                $msg['poll'] = $poll;
            }
        }
    }
    
    json(['messages' => $messages]);
}

// Отправить сообщение
if ($apiRoute === '/chat/send' && $method === 'POST') {
    if (!$user) json(['error' => 'Unauthorized'], 401);
    
    $communityRepo = new CommunityRepository();
    $messageRepo = new ChatMessageRepository();
    
    // Поддержка FormData
    $isFormData = strpos($_SERVER['CONTENT_TYPE'] ?? '', 'multipart/form-data') !== false;
    $chatId = (int)($isFormData ? ($_POST['chat_id'] ?? 0) : ($input['chat_id'] ?? 0));
    $message = trim($isFormData ? ($_POST['message'] ?? '') : ($input['message'] ?? ''));
    
    $chat = $communityRepo->getChatWithCommunity($chatId);
    if (!$chat) {
        json(['error' => 'Chat not found'], 404);
    }
    
    $community = $communityRepo->findById($chat['community_id']);
    
    // Проверка бана
    if ($communityRepo->isBanned($community['id'], $user['id'], $chatId)) {
        json(['error' => 'Вы заблокированы в этом чате'], 403);
    }
    
    // Проверка настроек
    $settings = $communityRepo->getChatEffectiveSettings($chatId);
    
    if ($settings['messages_disabled'] && $community['user_id'] !== $user['id']) {
        json(['error' => 'Отправка сообщений отключена'], 403);
    }
    
    // Проверка тайм-аута
    $timeout = $settings['message_timeout'] ?? 0;
    if ($timeout > 0 && $community['user_id'] !== $user['id']) {
        $remaining = $messageRepo->getTimeUntilCanSend($chatId, $user['id'], $timeout);
        if ($remaining > 0) {
            json(['error' => "Подождите ещё {$remaining} сек. перед отправкой"], 429);
        }
    }
    
    // Проверка изображений
    $hasImages = !empty($_FILES['images']) && is_array($_FILES['images']['name']);
    
    if ($hasImages) {
        // Проверка премиума
        if (!Role::isPremium($user['role'])) {
            json(['error' => 'Загрузка изображений доступна только премиум пользователям'], 403);
        }
        
        // Проверка настроек файлов
        if ($settings['files_disabled']) {
            json(['error' => 'Загрузка файлов отключена'], 403);
        }
    }
	
	if (!empty($_FILES['images'])) {
        $imageService = new ImageService();
        $files = $_FILES['images'];
        $count = min(count($files['name']), ChatMessageRepository::MAX_IMAGES);
        
        for ($i = 0; $i < $count; $i++) {
            if ($files['error'][$i] !== UPLOAD_ERR_OK) {
				json(['error' => 'Bad Request (file #'.$i.')'], 400);
				continue;
			}
            
            // Проверка размера
            if ($files['size'][$i] > 5 * 1024 * 1024) {
				$sizeMB = $files['size'][$i]/1024/1024 ;
				json(['error' => 'Payload Too Large (file #'.$i.' - '. round($sizeMB,2) .'MB) (MAX 5MB)'], 413);
				continue;
			}
            
            // Проверка типа
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $files['tmp_name'][$i]);
            finfo_close($finfo);
            
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!in_array($mimeType, $allowedTypes)) {
				json(['error' => 'Unsupported Media Type (file #'.$i.') ('.implode(', ',$allowedTypes).')'], 413);
				continue;
			}
        }
	}
	
    // Валидация сообщения
    if (empty($message) && empty($_FILES['images'])) {
        json(['error' => 'Введите сообщение или прикрепите изображение'], 400);
    }
    
    if (mb_strlen($message) > ChatMessageRepository::MAX_MESSAGE_LENGTH) {
        json(['error' => 'Сообщение слишком длинное'], 400);
    }
    
    // Создаём сообщение
    $messageId = $messageRepo->create($chatId, $user['id'], $message ?: null);
    
    // Загружаем изображения
    if ($hasImages) {
        $imageService = new ImageService();
        $files = $_FILES['images'];
        $count = min(count($files['name']), ChatMessageRepository::MAX_IMAGES);
        
        for ($i = 0; $i < $count; $i++) {            
            $path = $imageService->uploadFile($files['tmp_name'][$i], 'chats/' . $chatId );
            if ($path) {
                $messageRepo->addImage($messageId, $path, $i);
            }
        }
    }
    
    json(['id' => $messageId]);
}

// Удалить сообщение
if ($apiRoute === '/chat/message/delete' && $method === 'POST') {
    if (!$user) json(['error' => 'Unauthorized'], 401);
    
    $messageId = (int)($input['message_id'] ?? 0);
    
    $communityRepo = new CommunityRepository();
    $messageRepo = new ChatMessageRepository();
    
    $context = $messageRepo->getMessageContext($messageId);
    if (!$context) {
        json(['error' => 'Message not found'], 404);
    }
    
    $community = $communityRepo->findById($context['community_id']);
    $isOwner = $community['user_id'] === $user['id'];
    $isMessageOwner = $messageRepo->isOwner($messageId, $user['id']);
    $isModerator = $communityRepo->isModerator($context['community_id'], $user['id'], 'chat', $context['chat_id']);
    
    if (!$isOwner && !$isMessageOwner && !$isModerator) {
        json(['error' => 'Forbidden'], 403);
    }
    
    $messageRepo->delete($messageId);
    json(['success' => true]);
}

// Лайк сообщения
if ($apiRoute === '/chat/like' && $method === 'POST') {
    if (!$user) json(['error' => 'Unauthorized'], 401);
    
    $messageId = (int)($input['message_id'] ?? 0);
    
    $messageRepo = new ChatMessageRepository();
    $result = $messageRepo->toggleLike($messageId, $user['id']);
    
    json($result);
}

// === Polls ===

// Создать опрос
if ($apiRoute === '/chat/poll/create' && $method === 'POST') {
    if (!$user) json(['error' => 'Unauthorized'], 401);
    
    $communityRepo = new CommunityRepository();
    $messageRepo = new ChatMessageRepository();
    
    $chatId = (int)($input['chat_id'] ?? 0);
    $question = trim($input['question'] ?? '');
    $options = $input['options'] ?? [];
    $isMultiple = (bool)($input['is_multiple'] ?? false);
    
    $chat = $communityRepo->getChatWithCommunity($chatId);
    if (!$chat) {
        json(['error' => 'Chat not found'], 404);
    }
    
    $community = $communityRepo->findById($chat['community_id']);
    
    // Проверка бана
    if ($communityRepo->isBanned($community['id'], $user['id'], $chatId)) {
        json(['error' => 'Вы заблокированы'], 403);
    }
    
    // Проверка настроек
    $settings = $communityRepo->getChatEffectiveSettings($chatId);
    if ($settings['messages_disabled'] && $community['user_id'] !== $user['id']) {
        json(['error' => 'Отправка сообщений отключена'], 403);
    }
    
    // Валидация
    if (empty($question) || mb_strlen($question) > 500) {
        json(['error' => 'Некорректный вопрос'], 400);
    }
    
    $options = array_filter(array_map('trim', $options));
    if (count($options) < 2 || count($options) > 10) {
        json(['error' => 'Должно быть от 2 до 10 вариантов'], 400);
    }
    
    // Создаём сообщение-опрос
    $messageId = $messageRepo->create($chatId, $user['id'], null, true);
    $pollId = $messageRepo->createPoll($messageId, $question, $options, $isMultiple);
    
    json(['id' => $messageId, 'poll_id' => $pollId]);
}

// Голосовать в опросе
if ($apiRoute === '/chat/poll/vote' && $method === 'POST') {
    if (!$user) json(['error' => 'Unauthorized'], 401);
    
    $optionId = (int)($input['option_id'] ?? 0);
    
    $messageRepo = new ChatMessageRepository();
    $result = $messageRepo->vote($optionId, $user['id']);
    
    json(['success' => $result]);
}

// Отменить голос
if ($apiRoute === '/chat/poll/unvote' && $method === 'POST') {
    if (!$user) json(['error' => 'Unauthorized'], 401);
    
    $optionId = (int)($input['option_id'] ?? 0);
    
    $messageRepo = new ChatMessageRepository();
    $result = $messageRepo->unvote($optionId, $user['id']);
    
    json(['success' => $result]);
}
