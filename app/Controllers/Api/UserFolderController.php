<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Http\Request;
use App\Http\Response;
use App\Repository\UserFolderRepository;

class UserFolderController
{
    private UserFolderRepository $repo;

    public function __construct()
    {
        $this->repo = new UserFolderRepository();
    }

    // ========== ПУБЛИЧНЫЕ МЕТОДЫ (без авторизации) ==========

    /**
     * Получить элемент публично (для просмотра)
     */
    public function getItemPublic(Request $request): void
    {
        $id = (int)$request->query('id', 0);
        $item = $this->repo->getItem($id);
        
        if (!$item || !empty($item['is_hidden'])) {
            Response::error('Not found', 404);
            return;
        }
        
        // Получаем путь до корня
        $path = $this->repo->getItemPath($id);
        
        // Получаем детей (если это сущность)
        $children = [];
        if ($this->repo->isEntity($item['item_type'])) {
            $children = $this->repo->getChildrenPublic($item['user_id'], $id);
        }
        
        Response::json([
            'item' => $item,
            'path' => $path,
            'children' => $children
        ]);
    }

    /**
     * Получить содержимое папки публично
     */
    public function getFolderContents(Request $request): void
    {
        $folderId = $request->query('folder_id') ? (int)$request->query('folder_id') : null;
        $userId = (int)$request->query('user_id', 0);
        
        if (!$userId) {
            Response::error('user_id required', 400);
            return;
        }
        
        // Если указана папка - проверяем что она существует
        $folder = null;
        if ($folderId) {
            $folder = $this->repo->getItem($folderId);
            if (!$folder || $folder['user_id'] !== $userId || !empty($folder['is_hidden'])) {
                Response::error('Folder not found', 404);
                return;
            }
        }
        
        $children = $this->repo->getChildrenPublic($userId, $folderId);
        $path = $folderId ? $this->repo->getItemPath($folderId) : [];
        
        Response::json([
            'folder' => $folder,
            'path' => $path,
            'children' => $children
        ]);
    }

    /**
     * Получить структуру публично
     */
    public function getStructurePublic(Request $request): void
    {
        $userId = (int)$request->query('user_id', 0);
        if (!$userId) {
            Response::error('user_id required', 400);
            return;
        }
        Response::json(['structure' => $this->repo->getPublicStructure($userId)]);
    }

    // ========== АВТОРИЗОВАННЫЕ МЕТОДЫ ==========

    public function getStructure(Request $request): void
    {
        $user = $request->user();
        if (!$user) { Response::error('Unauthorized', 401); return; }
        Response::json(['structure' => $this->repo->getStructure($user['id'])]);
    }

    public function create(Request $request): void
    {
        $user = $request->user();
        if (!$user) { Response::error('Unauthorized', 401); return; }

        $type = $request->get('type');
        $name = trim($request->get('name', ''));
        $parentId = $request->get('parent_id') ? (int)$request->get('parent_id') : null;
        
        $allTypes = array_merge(UserFolderRepository::ENTITY_TYPES, UserFolderRepository::ELEMENT_TYPES);
        if (!in_array($type, $allTypes)) { Response::error('Invalid type', 400); return; }
        if (empty($name)) { Response::error('Name is required', 400); return; }

        if ($parentId !== null) {
            $parent = $this->repo->getItemByUser($parentId, $user['id']);
            if (!$parent || !$this->repo->isEntity($parent['item_type'])) {
                Response::error('Invalid parent', 400); return;
            }
        }

        $data = [
            'description' => $request->get('description'),
            'icon' => $request->get('icon'),
            'color' => $request->get('color'),
            'folder_category' => $request->get('folder_category'),
            'reference_id' => $request->get('reference_id'),
            'reference_type' => $request->get('reference_type'),
        ];
        
        // Для сервера - дополнительные данные в settings
        if ($type === 'server') {
            $data['settings'] = [
                'ip' => $request->get('server_ip'),
                'port' => $request->get('server_port', 25565),
            ];
        }
        
        $id = $this->repo->createItem($user['id'], $type, $name, $parentId, $data);
        Response::json(['id' => $id, 'success' => true]);
    }

    public function update(Request $request): void
    {
        $user = $request->user();
        if (!$user) { Response::error('Unauthorized', 401); return; }

        $id = (int)$request->get('id');
        $data = $request->only(['name', 'description', 'icon', 'color', 'is_collapsed', 'folder_category', 'settings']);
        
        Response::json(['success' => $this->repo->updateItem($id, $user['id'], $data)]);
    }

    public function delete(Request $request): void
    {
        $user = $request->user();
        if (!$user) { Response::error('Unauthorized', 401); return; }
        Response::json(['success' => $this->repo->deleteItem((int)$request->get('id'), $user['id'])]);
    }

    public function move(Request $request): void
    {
        $user = $request->user();
        if (!$user) { Response::error('Unauthorized', 401); return; }

        $itemId = (int)$request->get('item_id');
        $newParentId = $request->get('parent_id') !== null ? (int)$request->get('parent_id') : null;
        $afterItemId = $request->get('after_id') !== null ? (int)$request->get('after_id') : null;
        
        Response::json(['success' => $this->repo->moveItem($itemId, $user['id'], $newParentId, $afterItemId)]);
    }

    public function toggleCollapse(Request $request): void
    {
        $user = $request->user();
        if (!$user) { Response::error('Unauthorized', 401); return; }
        Response::json(['success' => $this->repo->toggleCollapsed((int)$request->get('id'), $user['id'])]);
    }

    public function getItem(Request $request): void
    {
        $user = $request->user();
        if (!$user) { Response::error('Unauthorized', 401); return; }

        $item = $this->repo->getItemByUser((int)$request->query('id', 0), $user['id']);
        if (!$item) { Response::error('Not found', 404); return; }
        Response::json(['item' => $item]);
    }

    public function subscribe(Request $request): void
    {
        $user = $request->user();
        if (!$user) { Response::error('Unauthorized', 401); return; }
        Response::json(['success' => $this->repo->subscribe((int)$request->get('user_id'), $user['id'])]);
    }

    public function unsubscribe(Request $request): void
    {
        $user = $request->user();
        if (!$user) { Response::error('Unauthorized', 401); return; }
        Response::json(['success' => $this->repo->unsubscribe((int)$request->get('user_id'), $user['id'])]);
    }

    public function showApplication(Request $request): void
    {
        $user = $request->user();
        if (!$user) { Response::error('Unauthorized', 401); return; }
        Response::json(['success' => $this->repo->showApplication((int)$request->get('application_id'), $user['id'])]);
    }

    public function hideApplication(Request $request): void
    {
        $user = $request->user();
        if (!$user) { Response::error('Unauthorized', 401); return; }
        Response::json(['success' => $this->repo->hideApplication((int)$request->get('application_id'), $user['id'])]);
    }
}
