<?php

namespace App\Repository\UserFolderRepository;

trait StructureTrait
{
    /**
     * Получить полную структуру папки пользователя
     */
    public function getStructure(int $userId): array
    {
        $items = $this->getAllItems($userId);
        return $this->buildTree($items);
    }

    /**
     * Рекурсивное построение дерева
     */
    private function buildTree(array $items, ?int $parentId = null): array
    {
        $result = [];
        
        foreach ($items as $item) {
            if ($item['parent_id'] == $parentId) {
                $node = [
                    'type' => $item['item_type'],
                    'data' => $item,
                    'children' => []
                ];
                
                // Только сущности могут иметь детей
                if ($this->isEntity($item['item_type'])) {
                    $node['children'] = $this->buildTree($items, $item['id']);
                }
                
                $result[] = $node;
            }
        }
        
        return $result;
    }

    /**
     * Получить структуру для просмотра (без скрытых элементов)
     */
    public function getPublicStructure(int $userId): array
    {
        $items = $this->db->fetchAll(
            'SELECT * FROM user_folder_items 
             WHERE user_id = ? AND is_hidden = 0 
             ORDER BY sort_order, name',
            [$userId]
        );
        
        return $this->buildTree($items);
    }

    /**
     * Получить количество элементов
     */
    public function countItems(int $userId): int
    {
        $result = $this->db->fetchOne(
            'SELECT COUNT(*) as cnt FROM user_folder_items WHERE user_id = ?',
            [$userId]
        );
        return (int)($result['cnt'] ?? 0);
    }

    /**
     * Получить количество чатов
     */
    public function countChats(int $userId): int
    {
        $result = $this->db->fetchOne(
            "SELECT COUNT(*) as cnt FROM user_folder_items WHERE user_id = ? AND item_type = 'chat'",
            [$userId]
        );
        return (int)($result['cnt'] ?? 0);
    }

    /**
     * Получить чаты для просмотра
     */
    public function getChats(int $userId, ?int $folderId = null): array
    {
        if ($folderId === null) {
            return $this->db->fetchAll(
                "SELECT * FROM user_folder_items 
                 WHERE user_id = ? AND item_type = 'chat' AND parent_id IS NULL 
                 ORDER BY sort_order, name",
                [$userId]
            );
        }
        
        return $this->db->fetchAll(
            "SELECT * FROM user_folder_items 
             WHERE user_id = ? AND item_type = 'chat' AND parent_id = ? 
             ORDER BY sort_order, name",
            [$userId, $folderId]
        );
    }

    /**
     * Получить папки
     */
    public function getFolders(int $userId, ?int $parentId = null): array
    {
        if ($parentId === null) {
            return $this->db->fetchAll(
                "SELECT * FROM user_folder_items 
                 WHERE user_id = ? AND item_type = 'folder' AND parent_id IS NULL 
                 ORDER BY sort_order, name",
                [$userId]
            );
        }
        
        return $this->db->fetchAll(
            "SELECT * FROM user_folder_items 
             WHERE user_id = ? AND item_type = 'folder' AND parent_id = ? 
             ORDER BY sort_order, name",
            [$userId, $parentId]
        );
    }

    /**
     * Получить элемент чата по ID (для совместимости)
     */
    public function getChat(int $chatId): ?array
    {
        return $this->db->fetchOne(
            "SELECT * FROM user_folder_items WHERE id = ? AND item_type = 'chat'",
            [$chatId]
        );
    }

    /**
     * Получить чат с информацией о владельце
     */
    public function getChatWithOwner(int $chatId): ?array
    {
        return $this->db->fetchOne(
            "SELECT ufi.*, u.id as owner_id, u.login as owner_login, u.username as owner_username
             FROM user_folder_items ufi
             JOIN users u ON ufi.user_id = u.id
             WHERE ufi.id = ? AND ufi.item_type = 'chat'",
            [$chatId]
        );
    }
}
