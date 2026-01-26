<?php

use App\Repository\CommunityRepository;
use App\Core\Role;

// === Community API Routes ===

// Создать сообщество
if ($apiRoute === '/community/create' && $method === 'POST') {
    if (!$user) json(['error' => 'Unauthorized'], 401);
    
    $communityRepo = new CommunityRepository();
    
    // Проверяем, нет ли уже сообщества
    if ($communityRepo->userHasCommunity($user['id'])) {
        $existing = $communityRepo->findByUserId($user['id']);
        json(['id' => $existing['id']]);
    }
    
    $id = $communityRepo->create($user['id']);
    json(['id' => $id]);
}

// Обновить настройки сообщества
if ($apiRoute === '/community/update' && $method === 'POST') {
    if (!$user) json(['error' => 'Unauthorized'], 401);
    
    $communityRepo = new CommunityRepository();
    $community = $communityRepo->findById((int)($input['id'] ?? 0));
    
    if (!$community || $community['user_id'] !== $user['id']) {
        json(['error' => 'Forbidden'], 403);
    }
    
    $communityRepo->update($community['id'], [
        'message_timeout' => $input['message_timeout'] ?? null,
        'files_disabled' => (int)($input['files_disabled'] ?? 0),
        'messages_disabled' => (int)($input['messages_disabled'] ?? 0),
    ]);
    
    json(['success' => true]);
}

// Удалить сообщество
if ($apiRoute === '/community/delete' && $method === 'POST') {
    if (!$user) json(['error' => 'Unauthorized'], 401);
    
    $communityRepo = new CommunityRepository();
    $community = $communityRepo->findById((int)($input['id'] ?? 0));
    
    if (!$community || $community['user_id'] !== $user['id']) {
        json(['error' => 'Forbidden'], 403);
    }
    
    $communityRepo->delete($community['id']);
    json(['success' => true]);
}

// Подписаться на сообщество
if ($apiRoute === '/community/subscribe' && $method === 'POST') {
    if (!$user) json(['error' => 'Unauthorized'], 401);
    
    $communityRepo = new CommunityRepository();
    $communityId = (int)($input['community_id'] ?? 0);
    
    $community = $communityRepo->findById($communityId);
    if (!$community) {
        json(['error' => 'Community not found'], 404);
    }
    
    // Нельзя подписаться на своё сообщество
    if ($community['user_id'] === $user['id']) {
        json(['error' => 'Cannot subscribe to own community'], 400);
    }
    
    $communityRepo->subscribe($communityId, $user['id']);
    json(['success' => true]);
}

// Отписаться от сообщества
if ($apiRoute === '/community/unsubscribe' && $method === 'POST') {
    if (!$user) json(['error' => 'Unauthorized'], 401);
    
    $communityRepo = new CommunityRepository();
    $communityId = (int)($input['community_id'] ?? 0);
    
    $communityRepo->unsubscribe($communityId, $user['id']);
    json(['success' => true]);
}

// === Folders ===

// Создать папку
if ($apiRoute === '/community/folder/create' && $method === 'POST') {
    if (!$user) json(['error' => 'Unauthorized'], 401);
    
    $communityRepo = new CommunityRepository();
    $communityId = (int)($input['community_id'] ?? 0);
    
    $community = $communityRepo->findById($communityId);
    if (!$community || $community['user_id'] !== $user['id']) {
        json(['error' => 'Forbidden'], 403);
    }
    
    $name = trim($input['name'] ?? '');
    if (empty($name) || mb_strlen($name) > 100) {
        json(['error' => 'Invalid name'], 400);
    }
    
    $parentId = !empty($input['parent_id']) ? (int)$input['parent_id'] : null;
    
    $id = $communityRepo->createFolder($communityId, $name, $parentId);
    json(['id' => $id]);
}

// Обновить папку
if ($apiRoute === '/community/folder/update' && $method === 'POST') {
    if (!$user) json(['error' => 'Unauthorized'], 401);
    
    $communityRepo = new CommunityRepository();
    $folder = $communityRepo->getFolder((int)($input['id'] ?? 0));
    
    if (!$folder) {
        json(['error' => 'Folder not found'], 404);
    }
    
    $community = $communityRepo->findById($folder['community_id']);
    if ($community['user_id'] !== $user['id']) {
        json(['error' => 'Forbidden'], 403);
    }
    
    $data = [];
    if (isset($input['name'])) $data['name'] = trim($input['name']);
    if (isset($input['message_timeout'])) $data['message_timeout'] = $input['message_timeout'];
    if (isset($input['files_disabled'])) $data['files_disabled'] = (int)$input['files_disabled'];
    if (isset($input['messages_disabled'])) $data['messages_disabled'] = (int)$input['messages_disabled'];
    
    $communityRepo->updateFolder($folder['id'], $data);
    json(['success' => true]);
}

// Удалить папку
if ($apiRoute === '/community/folder/delete' && $method === 'POST') {
    if (!$user) json(['error' => 'Unauthorized'], 401);
    
    $communityRepo = new CommunityRepository();
    $folder = $communityRepo->getFolder((int)($input['id'] ?? 0));
    
    if (!$folder) {
        json(['error' => 'Folder not found'], 404);
    }
    
    $community = $communityRepo->findById($folder['community_id']);
    if ($community['user_id'] !== $user['id']) {
        json(['error' => 'Forbidden'], 403);
    }
    
    $communityRepo->deleteFolder($folder['id']);
    json(['success' => true]);
}

// === Chats ===

// Создать чат
if ($apiRoute === '/community/chat/create' && $method === 'POST') {
    if (!$user) json(['error' => 'Unauthorized'], 401);
    
    $communityRepo = new CommunityRepository();
    $communityId = (int)($input['community_id'] ?? 0);
    
    $community = $communityRepo->findById($communityId);
    if (!$community || $community['user_id'] !== $user['id']) {
        json(['error' => 'Forbidden'], 403);
    }
    
    $name = trim($input['name'] ?? '');
    if (empty($name) || mb_strlen($name) > 100) {
        json(['error' => 'Invalid name'], 400);
    }
    
    $folderId = !empty($input['folder_id']) ? (int)$input['folder_id'] : null;
    $description = trim($input['description'] ?? '') ?: null;
    
    $id = $communityRepo->createChat($communityId, $name, $folderId, $description);
    json(['id' => $id]);
}

// Обновить чат
if ($apiRoute === '/community/chat/update' && $method === 'POST') {
    if (!$user) json(['error' => 'Unauthorized'], 401);
    
    $communityRepo = new CommunityRepository();
    $chat = $communityRepo->getChat((int)($input['id'] ?? 0));
    
    if (!$chat) {
        json(['error' => 'Chat not found'], 404);
    }
    
    $community = $communityRepo->findById($chat['community_id']);
    $isOwner = $community['user_id'] === $user['id'];
    $isModerator = $communityRepo->isModerator($community['id'], $user['id'], 'chat', $chat['id']);
    
    if (!$isOwner && !$isModerator) {
        json(['error' => 'Forbidden'], 403);
    }
    
    $data = [];
    if (isset($input['name'])) $data['name'] = trim($input['name']);
    if (isset($input['description'])) $data['description'] = trim($input['description']) ?: null;
    if (isset($input['message_timeout'])) $data['message_timeout'] = $input['message_timeout'];
    if (isset($input['files_disabled'])) $data['files_disabled'] = (int)$input['files_disabled'];
    if (isset($input['messages_disabled'])) $data['messages_disabled'] = (int)$input['messages_disabled'];
    
    $communityRepo->updateChat($chat['id'], $data);
    json(['success' => true]);
}

// Удалить чат
if ($apiRoute === '/community/chat/delete' && $method === 'POST') {
    if (!$user) json(['error' => 'Unauthorized'], 401);
    
    $communityRepo = new CommunityRepository();
    $chat = $communityRepo->getChat((int)($input['id'] ?? 0));
    
    if (!$chat) {
        json(['error' => 'Chat not found'], 404);
    }
    
    $community = $communityRepo->findById($chat['community_id']);
    if ($community['user_id'] !== $user['id']) {
        json(['error' => 'Forbidden'], 403);
    }
    
    $communityRepo->deleteChat($chat['id']);
    json(['success' => true]);
}

// === Moderators ===

// Добавить модератора
if ($apiRoute === '/community/moderator/add' && $method === 'POST') {
    if (!$user) json(['error' => 'Unauthorized'], 401);
    
    $communityRepo = new CommunityRepository();
    $communityId = (int)($input['community_id'] ?? 0);
    
    $community = $communityRepo->findById($communityId);
    if (!$community || $community['user_id'] !== $user['id']) {
        json(['error' => 'Forbidden'], 403);
    }
    
    $userId = (int)($input['user_id'] ?? 0);
    $scope = $input['scope'] ?? 'community';
    $scopeId = isset($input['scope_id']) ? (int)$input['scope_id'] : null;
    
    if (!in_array($scope, ['community', 'folder', 'chat'])) {
        json(['error' => 'Invalid scope'], 400);
    }
    
    $result = $communityRepo->addModerator($communityId, $userId, $scope, $scopeId);
    
    if (!$result) {
        json(['error' => 'User must be subscribed to become moderator'], 400);
    }
    
    json(['success' => true]);
}

// Удалить модератора
if ($apiRoute === '/community/moderator/remove' && $method === 'POST') {
    if (!$user) json(['error' => 'Unauthorized'], 401);
    
    $communityRepo = new CommunityRepository();
    $communityId = (int)($input['community_id'] ?? 0);
    
    $community = $communityRepo->findById($communityId);
    if (!$community || $community['user_id'] !== $user['id']) {
        json(['error' => 'Forbidden'], 403);
    }
    
    $userId = (int)($input['user_id'] ?? 0);
    $scope = $input['scope'] ?? 'community';
    $scopeId = isset($input['scope_id']) ? (int)$input['scope_id'] : null;
    
    $communityRepo->removeModerator($communityId, $userId, $scope, $scopeId);
    json(['success' => true]);
}

// Поиск подписчиков для назначения модератора
if ($apiRoute === '/community/subscribers/search' && $method === 'GET') {
    if (!$user) json(['error' => 'Unauthorized'], 401);
    
    $communityRepo = new CommunityRepository();
    $communityId = (int)($_GET['community_id'] ?? 0);
    $query = $_GET['q'] ?? '';
    
    $community = $communityRepo->findById($communityId);
    if (!$community || $community['user_id'] !== $user['id']) {
        json(['error' => 'Forbidden'], 403);
    }
    
    $subscribers = $communityRepo->searchSubscribers($communityId, $query, 20);
    json(['subscribers' => $subscribers]);
}

// === Bans ===

// Забанить пользователя
if ($apiRoute === '/community/ban' && $method === 'POST') {
    if (!$user) json(['error' => 'Unauthorized'], 401);
    
    $communityRepo = new CommunityRepository();
    $communityId = (int)($input['community_id'] ?? 0);
    
    $community = $communityRepo->findById($communityId);
    if (!$community) {
        json(['error' => 'Community not found'], 404);
    }
    
    $isOwner = $community['user_id'] === $user['id'];
    $isModerator = $communityRepo->isModerator($communityId, $user['id']);
    
    if (!$isOwner && !$isModerator) {
        json(['error' => 'Forbidden'], 403);
    }
    
    $userId = (int)($input['user_id'] ?? 0);
    $scope = $input['scope'] ?? 'community';
    $scopeId = isset($input['scope_id']) ? (int)$input['scope_id'] : null;
    $reason = $input['reason'] ?? null;
    $expiresAt = $input['expires_at'] ?? null;
    
    // Нельзя забанить владельца
    if ($userId === $community['user_id']) {
        json(['error' => 'Cannot ban community owner'], 400);
    }
    
    $communityRepo->banUser($communityId, $userId, $user['id'], $scope, $scopeId, $reason, $expiresAt);
    json(['success' => true]);
}

// Разбанить пользователя
if ($apiRoute === '/community/unban' && $method === 'POST') {
    if (!$user) json(['error' => 'Unauthorized'], 401);
    
    $communityRepo = new CommunityRepository();
    $communityId = (int)($input['community_id'] ?? 0);
    
    $community = $communityRepo->findById($communityId);
    if (!$community) {
        json(['error' => 'Community not found'], 404);
    }
    
    $isOwner = $community['user_id'] === $user['id'];
    $isModerator = $communityRepo->isModerator($communityId, $user['id']);
    
    if (!$isOwner && !$isModerator) {
        json(['error' => 'Forbidden'], 403);
    }
    
    $userId = (int)($input['user_id'] ?? 0);
    $scope = $input['scope'] ?? 'community';
    $scopeId = isset($input['scope_id']) ? (int)$input['scope_id'] : null;
    
    $communityRepo->unbanUser($communityId, $userId, $scope, $scopeId);
    json(['success' => true]);
}
