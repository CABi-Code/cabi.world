<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Http\Request;
use App\Http\Response;
use App\Repository\CommunityRepository;

class CommunityController
{
    private CommunityRepository $repo;

    public function __construct()
    {
        $this->repo = new CommunityRepository();
    }

    public function create(Request $request): void
    {
        $user = $request->user();
        if (!$user) {
            Response::error('Unauthorized', 401);
            return;
        }

        if ($this->repo->userHasCommunity($user['id'])) {
            $community = $this->repo->findByUserId($user['id']);
            Response::json(['id' => $community['id']]);
            return;
        }

        $id = $this->repo->create($user['id']);
        Response::json(['id' => $id]);
    }

    public function update(Request $request): void
    {
        $user = $request->user();
        $id = (int)$request->get('id');
        
        $community = $this->repo->findById($id);
        if (!$community || $community['user_id'] !== $user['id']) {
            Response::error('Forbidden', 403);
            return;
        }

        $data = $request->only(['message_timeout', 'files_disabled', 'messages_disabled']);
        $this->repo->update($id, $data);
        Response::json(['success' => true]);
    }

    public function delete(Request $request): void
    {
        $user = $request->user();
        $id = (int)$request->get('id');
        
        $community = $this->repo->findById($id);
        if (!$community || $community['user_id'] !== $user['id']) {
            Response::error('Forbidden', 403);
            return;
        }

        $this->repo->delete($id);
        Response::json(['success' => true]);
    }

    public function subscribe(Request $request): void
    {
        $user = $request->user();
        $communityId = (int)$request->get('community_id');
        
        $this->repo->subscribe($communityId, $user['id']);
        Response::json(['success' => true]);
    }

    public function unsubscribe(Request $request): void
    {
        $user = $request->user();
        $communityId = (int)$request->get('community_id');
        
        $this->repo->unsubscribe($communityId, $user['id']);
        Response::json(['success' => true]);
    }

    public function createChat(Request $request): void
    {
        $user = $request->user();
        $communityId = (int)$request->get('community_id');
        $folderId = $request->get('folder_id') ? (int)$request->get('folder_id') : null;
        $name = trim($request->get('name', ''));
        $description = trim($request->get('description', '')) ?: null;

        $community = $this->repo->findById($communityId);
        if (!$community || $community['user_id'] !== $user['id']) {
            Response::error('Forbidden', 403);
            return;
        }

        if (empty($name)) {
            Response::error('Название обязательно', 400);
            return;
        }

        $id = $this->repo->createChat($communityId, $name, $folderId, $description);
        Response::json(['id' => $id]);
    }

    public function updateChat(Request $request): void
    {
        $user = $request->user();
        $chatId = (int)$request->get('id');
        
        $chat = $this->repo->getChatWithCommunity($chatId);
        if (!$chat || $chat['owner_id'] !== $user['id']) {
            Response::error('Forbidden', 403);
            return;
        }

        $data = $request->only(['name', 'description', 'message_timeout', 'files_disabled', 'messages_disabled']);
        $this->repo->updateChat($chatId, $data);
        Response::json(['success' => true]);
    }

    public function deleteChat(Request $request): void
    {
        $user = $request->user();
        $chatId = (int)$request->get('id');
        
        $chat = $this->repo->getChatWithCommunity($chatId);
        if (!$chat || $chat['owner_id'] !== $user['id']) {
            Response::error('Forbidden', 403);
            return;
        }

        $this->repo->deleteChat($chatId);
        Response::json(['success' => true]);
    }

    public function createFolder(Request $request): void
    {
        $user = $request->user();
        $communityId = (int)$request->get('community_id');
        $parentId = $request->get('parent_id') ? (int)$request->get('parent_id') : null;
        $name = trim($request->get('name', ''));

        $community = $this->repo->findById($communityId);
        if (!$community || $community['user_id'] !== $user['id']) {
            Response::error('Forbidden', 403);
            return;
        }

        if (empty($name)) {
            Response::error('Название обязательно', 400);
            return;
        }

        $id = $this->repo->createFolder($communityId, $name, $parentId);
        Response::json(['id' => $id]);
    }

    public function updateFolder(Request $request): void
    {
        $user = $request->user();
        $folderId = (int)$request->get('id');
        
        $folder = $this->repo->getFolder($folderId);
        if (!$folder) {
            Response::error('Not found', 404);
            return;
        }
        
        $community = $this->repo->findById($folder['community_id']);
        if (!$community || $community['user_id'] !== $user['id']) {
            Response::error('Forbidden', 403);
            return;
        }

        $data = $request->only(['name', 'message_timeout', 'files_disabled', 'messages_disabled']);
        $this->repo->updateFolder($folderId, $data);
        Response::json(['success' => true]);
    }

    public function deleteFolder(Request $request): void
    {
        $user = $request->user();
        $folderId = (int)$request->get('id');
        
        $folder = $this->repo->getFolder($folderId);
        if (!$folder) {
            Response::error('Not found', 404);
            return;
        }
        
        $community = $this->repo->findById($folder['community_id']);
        if (!$community || $community['user_id'] !== $user['id']) {
            Response::error('Forbidden', 403);
            return;
        }

        $this->repo->deleteFolder($folderId);
        Response::json(['success' => true]);
    }
}
