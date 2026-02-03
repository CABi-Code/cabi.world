<?php

namespace App\Repository\UserFolderRepository;

trait StructureTrait
{
    public function getStructure(int $userId): array
    {
        $this->fixOrphanedItems($userId);
        $items = $this->getAllItems($userId);
        return $this->buildTree($items);
    }

    private function buildTree(array $items, ?int $parentId = null): array
    {
        $result = [];
        foreach ($items as $item) {
            if ($item['parent_id'] == $parentId) {
                $node = ['type' => $item['item_type'], 'data' => $item, 'children' => []];
                if ($this->isEntity($item['item_type'])) {
                    $node['children'] = $this->buildTree($items, $item['id']);
                }
                $result[] = $node;
            }
        }
        return $result;
    }

    public function getPublicStructure(int $userId): array
    {
        $items = $this->db->fetchAll(
            'SELECT * FROM user_folder_items WHERE user_id = ? AND is_hidden = 0 ORDER BY sort_order, name',
            [$userId]
        );
        return $this->buildTree($items);
    }

    /**
     * Получить детей публично (только не скрытые)
     */
    public function getChildrenPublic(int $userId, ?int $parentId = null): array
    {
        if ($parentId === null) {
            return $this->db->fetchAll(
                'SELECT * FROM user_folder_items WHERE user_id = ? AND parent_id IS NULL AND is_hidden = 0 ORDER BY sort_order, name',
                [$userId]
            );
        }
        return $this->db->fetchAll(
            'SELECT * FROM user_folder_items WHERE user_id = ? AND parent_id = ? AND is_hidden = 0 ORDER BY sort_order, name',
            [$userId, $parentId]
        );
    }

    /**
     * Получить путь от корня до элемента
     */
    public function getItemPath(int $itemId): array
    {
        $path = [];
        $current = $this->getItem($itemId);
        
        while ($current) {
            array_unshift($path, [
                'id' => $current['id'],
                'name' => $current['name'],
                'icon' => $current['icon'],
                'color' => $current['color'],
                'item_type' => $current['item_type']
            ]);
            
            if ($current['parent_id']) {
                $current = $this->getItem($current['parent_id']);
            } else {
                break;
            }
        }
        
        return $path;
    }

    public function countItems(int $userId): int
    {
        $r = $this->db->fetchOne('SELECT COUNT(*) as cnt FROM user_folder_items WHERE user_id = ?', [$userId]);
        return (int)($r['cnt'] ?? 0);
    }

    public function countChats(int $userId): int
    {
        $r = $this->db->fetchOne("SELECT COUNT(*) as cnt FROM user_folder_items WHERE user_id = ? AND item_type = 'chat'", [$userId]);
        return (int)($r['cnt'] ?? 0);
    }

    public function getChat(int $chatId): ?array
    {
        return $this->db->fetchOne("SELECT * FROM user_folder_items WHERE id = ? AND item_type = 'chat'", [$chatId]);
    }

    public function getChatWithOwner(int $chatId): ?array
    {
        return $this->db->fetchOne(
            "SELECT ufi.*, u.id as owner_id, u.login as owner_login, u.username as owner_username
             FROM user_folder_items ufi JOIN users u ON ufi.user_id = u.id
             WHERE ufi.id = ? AND ufi.item_type = 'chat'",
            [$chatId]
        );
    }

    public function getChatSettings(int $chatId): array
    {
        $chat = $this->getChat($chatId);
        if (!$chat) return [];
        $settings = $chat['settings'] ? json_decode($chat['settings'], true) : [];
        return array_merge(['message_timeout' => 0, 'files_disabled' => false, 'messages_disabled' => false], $settings);
    }

    public function updateChatSettings(int $chatId, int $userId, array $settings): bool
    {
        $chat = $this->getItemByUser($chatId, $userId);
        if (!$chat || $chat['item_type'] !== 'chat') return false;
        $currentSettings = $chat['settings'] ? json_decode($chat['settings'], true) : [];
        $newSettings = array_merge($currentSettings, $settings);
        return $this->db->execute('UPDATE user_folder_items SET settings = ? WHERE id = ?', [json_encode($newSettings), $chatId]) > 0;
    }

    public function getChats(int $userId, ?int $folderId = null): array
    {
        if ($folderId === null) {
            return $this->db->fetchAll("SELECT * FROM user_folder_items WHERE user_id = ? AND item_type = 'chat' AND parent_id IS NULL ORDER BY sort_order, name", [$userId]);
        }
        return $this->db->fetchAll("SELECT * FROM user_folder_items WHERE user_id = ? AND item_type = 'chat' AND parent_id = ? ORDER BY sort_order, name", [$userId, $folderId]);
    }

    public function getFolders(int $userId, ?int $parentId = null): array
    {
        if ($parentId === null) {
            return $this->db->fetchAll("SELECT * FROM user_folder_items WHERE user_id = ? AND item_type = 'folder' AND parent_id IS NULL ORDER BY sort_order, name", [$userId]);
        }
        return $this->db->fetchAll("SELECT * FROM user_folder_items WHERE user_id = ? AND item_type = 'folder' AND parent_id = ? ORDER BY sort_order, name", [$userId, $parentId]);
    }
}
