<?php

namespace App\Repository\UserFolderRepository;

trait ItemsTrait
{
    /**
     * Получить элемент по ID
     */
    public function getItem(int $id): ?array
    {
        return $this->db->fetchOne('SELECT * FROM user_folder_items WHERE id = ?', [$id]);
    }

    /**
     * Получить элемент с проверкой владельца
     */
    public function getItemByUser(int $id, int $userId): ?array
    {
        return $this->db->fetchOne(
            'SELECT * FROM user_folder_items WHERE id = ? AND user_id = ?',
            [$id, $userId]
        );
    }

    /**
     * Получить все элементы пользователя
     */
    public function getAllItems(int $userId): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM user_folder_items WHERE user_id = ? ORDER BY sort_order, name',
            [$userId]
        );
    }

    /**
     * Получить дочерние элементы
     */
    public function getChildren(int $userId, ?int $parentId = null): array
    {
        if ($parentId === null) {
            return $this->db->fetchAll(
                'SELECT * FROM user_folder_items 
                 WHERE user_id = ? AND parent_id IS NULL 
                 ORDER BY sort_order, name',
                [$userId]
            );
        }
        
        return $this->db->fetchAll(
            'SELECT * FROM user_folder_items 
             WHERE user_id = ? AND parent_id = ? 
             ORDER BY sort_order, name',
            [$userId, $parentId]
        );
    }

    /**
     * Создать элемент
     */
    public function createItem(int $userId, string $type, string $name, ?int $parentId = null, array $data = []): int
    {
        $sortOrder = $this->getNextSortOrder($userId, $parentId);
        
        $this->db->execute(
            'INSERT INTO user_folder_items 
             (user_id, parent_id, item_type, name, description, icon, color, sort_order, 
              folder_category, reference_id, reference_type, settings) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $userId, $parentId, $type, $name,
                $data['description'] ?? null,
                $data['icon'] ?? null,
                $data['color'] ?? null,
                $sortOrder,
                $data['folder_category'] ?? null,
                $data['reference_id'] ?? null,
                $data['reference_type'] ?? null,
                isset($data['settings']) ? json_encode($data['settings']) : null
            ]
        );
        
        return $this->db->lastInsertId();
    }

    /**
     * Обновить элемент
     */
    public function updateItem(int $id, int $userId, array $data): bool
    {
        $allowed = ['name', 'description', 'icon', 'color', 'is_collapsed', 'folder_category', 'settings', 'is_hidden'];
        $fields = [];
        $values = [];
        
        foreach ($data as $key => $value) {
            if (in_array($key, $allowed)) {
                $fields[] = "$key = ?";
                $values[] = $key === 'settings' && is_array($value) ? json_encode($value) : $value;
            }
        }
        
        if (empty($fields)) return false;
        
        $values[] = $id;
        $values[] = $userId;
        
        return $this->db->execute(
            'UPDATE user_folder_items SET ' . implode(', ', $fields) . ' WHERE id = ? AND user_id = ?',
            $values
        ) > 0;
    }

    /**
     * Удалить элемент
     */
    public function deleteItem(int $id, int $userId): bool
    {
        return $this->db->execute(
            'DELETE FROM user_folder_items WHERE id = ? AND user_id = ?',
            [$id, $userId]
        ) > 0;
    }

    /**
     * Получить следующий sort_order
     */
    protected function getNextSortOrder(int $userId, ?int $parentId): int
    {
        $sql = 'SELECT MAX(sort_order) as max_order FROM user_folder_items WHERE user_id = ?';
        $params = [$userId];
        
        if ($parentId === null) {
            $sql .= ' AND parent_id IS NULL';
        } else {
            $sql .= ' AND parent_id = ?';
            $params[] = $parentId;
        }
        
        $result = $this->db->fetchOne($sql, $params);
        return ($result['max_order'] ?? 0) + 1;
    }

    /**
     * Переместить элемент
     */
    public function moveItem(int $itemId, int $userId, ?int $newParentId, ?int $afterItemId = null): bool
    {
        $item = $this->getItemByUser($itemId, $userId);
        if (!$item) return false;
        
        // Проверяем родителя
        if ($newParentId !== null) {
            $parent = $this->getItemByUser($newParentId, $userId);
            if (!$parent || !$this->isEntity($parent['item_type'])) {
                return false;
            }
        }
        
        $newSortOrder = $afterItemId 
            ? $this->getSortOrderAfter($userId, $newParentId, $afterItemId)
            : $this->getNextSortOrder($userId, $newParentId);
        
        return $this->db->execute(
            'UPDATE user_folder_items SET parent_id = ?, sort_order = ? WHERE id = ? AND user_id = ?',
            [$newParentId, $newSortOrder, $itemId, $userId]
        ) > 0;
    }

    /**
     * Получить sort_order после указанного элемента
     */
    protected function getSortOrderAfter(int $userId, ?int $parentId, int $afterItemId): int
    {
        $afterItem = $this->getItemByUser($afterItemId, $userId);
        if (!$afterItem) return $this->getNextSortOrder($userId, $parentId);
        
        return $afterItem['sort_order'] + 1;
    }

    /**
     * Переключить свёрнутость
     */
    public function toggleCollapsed(int $id, int $userId): bool
    {
        return $this->db->execute(
            'UPDATE user_folder_items SET is_collapsed = NOT is_collapsed WHERE id = ? AND user_id = ?',
            [$id, $userId]
        ) > 0;
    }
}
