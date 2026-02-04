<?php

namespace App\Repository\UserFolderRepository;

trait MoveTrait
{
    /**
     * Переместить элемент в другую позицию
     * 
     * @param int $itemId - ID перемещаемого элемента
     * @param int $userId - ID пользователя
     * @param int|null $newParentId - ID новой родительской сущности (null = корень)
     * @param int|null $afterItemId - ID элемента, после которого вставить (null = в начало)
     */
    public function moveItem(int $itemId, int $userId, ?int $newParentId, ?int $afterItemId = null): bool
    {
        $item = $this->getItemByUser($itemId, $userId);
        if (!$item) return false;
        
        // Проверяем, что новый родитель существует и является сущностью
        if ($newParentId !== null) {
            $parent = $this->getItemByUser($newParentId, $userId);
            if (!$parent || !$this->isEntity($parent['item_type'])) {
                return false;
            }
            
            // Нельзя переместить элемент внутрь самого себя
            if ($this->isDescendant($newParentId, $itemId, $userId)) {
                return false;
            }
        }
        
        // Вычисляем новый sort_order
        $newSortOrder = $this->calculateNewSortOrder($userId, $newParentId, $afterItemId);
        
        // Обновляем элемент
        return $this->db->execute(
            'UPDATE user_folder_items SET parent_id = ?, sort_order = ? WHERE id = ? AND user_id = ?',
            [$newParentId, $newSortOrder, $itemId, $userId]
        ) > 0;
    }

    /**
     * Переместить элемент перед другим элементом
     */
    public function moveItemBefore(int $itemId, int $userId, int $beforeItemId): bool
    {
        $beforeItem = $this->getItemByUser($beforeItemId, $userId);
        if (!$beforeItem) return false;
        
        // Находим элемент, который идёт перед beforeItem
        $prevItem = $this->db->fetchOne(
            'SELECT id FROM user_folder_items 
             WHERE user_id = ? AND parent_id <=> ? AND sort_order < ? 
             ORDER BY sort_order DESC LIMIT 1',
            [$userId, $beforeItem['parent_id'], $beforeItem['sort_order']]
        );
        
        return $this->moveItem($itemId, $userId, $beforeItem['parent_id'], $prevItem['id'] ?? null);
    }

    /**
     * Проверить, является ли элемент потомком другого
     */
    private function isDescendant(int $itemId, int $ancestorId, int $userId): bool
    {
        $item = $this->getItemByUser($itemId, $userId);
        
        while ($item && $item['parent_id'] !== null) {
            if ($item['parent_id'] === $ancestorId) {
                return true;
            }
            $item = $this->getItemByUser($item['parent_id'], $userId);
        }
        
        return false;
    }

    /**
     * Вычислить новый sort_order
     */
    private function calculateNewSortOrder(int $userId, ?int $parentId, ?int $afterItemId): int
    {
        if ($afterItemId === null) {
            // В начало списка
            $first = $this->db->fetchOne(
                'SELECT MIN(sort_order) as min_order FROM user_folder_items 
                 WHERE user_id = ? AND parent_id <=> ?',
                [$userId, $parentId]
            );
            return max(0, ($first['min_order'] ?? 1) - 1);
        }
        
        $afterItem = $this->getItemByUser($afterItemId, $userId);
        if (!$afterItem) {
            return $this->getNextSortOrder($userId, $parentId);
        }
        
        // Находим элемент после afterItem
        $nextItem = $this->db->fetchOne(
            'SELECT sort_order FROM user_folder_items 
             WHERE user_id = ? AND parent_id <=> ? AND sort_order > ? 
             ORDER BY sort_order ASC LIMIT 1',
            [$userId, $parentId, $afterItem['sort_order']]
        );
        
        if ($nextItem) {
            // Вставляем между afterItem и nextItem
            return (int)(($afterItem['sort_order'] + $nextItem['sort_order']) / 2);
        }
        
        // Вставляем в конец
        return $afterItem['sort_order'] + 1;
    }

    /**
     * Перенормировать sort_order для всех элементов в папке
     */
    public function reorderItems(int $userId, ?int $parentId): void
    {
        $items = $this->getChildren($userId, $parentId);
        
        $order = 0;
        foreach ($items as $item) {
            $this->db->execute(
                'UPDATE user_folder_items SET sort_order = ? WHERE id = ?',
                [$order, $item['id']]
            );
            $order++;
        }
    }

    /**
     * Установить порядок элементов
     */
    public function setItemsOrder(int $userId, array $itemIds): bool
    {
        $order = 0;
        foreach ($itemIds as $itemId) {
            $this->db->execute(
                'UPDATE user_folder_items SET sort_order = ? WHERE id = ? AND user_id = ?',
                [$order, $itemId, $userId]
            );
            $order++;
        }
        return true;
    }
}
