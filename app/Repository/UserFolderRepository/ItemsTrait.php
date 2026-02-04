<?php

namespace App\Repository\UserFolderRepository;

trait ItemsTrait
{
    public function getItem(int $id): ?array
    {
        return $this->db->fetchOne('SELECT * FROM user_folder_items WHERE id = ?', [$id]);
    }

    public function getItemByUser(int $id, int $userId): ?array
    {
        return $this->db->fetchOne(
            'SELECT * FROM user_folder_items WHERE id = ? AND user_id = ?',
            [$id, $userId]
        );
    }

    public function getAllItems(int $userId): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM user_folder_items WHERE user_id = ? ORDER BY sort_order, name',
            [$userId]
        );
    }

    public function getChildren(int $userId, ?int $parentId = null): array
    {
        if ($parentId === null) {
            return $this->db->fetchAll(
                'SELECT * FROM user_folder_items WHERE user_id = ? AND parent_id IS NULL ORDER BY sort_order, name',
                [$userId]
            );
        }
        return $this->db->fetchAll(
            'SELECT * FROM user_folder_items WHERE user_id = ? AND parent_id = ? ORDER BY sort_order, name',
            [$userId, $parentId]
        );
    }

    public function createItem(int $userId, string $type, string $name, ?int $parentId = null, array $data = []): int
    {
        if ($parentId !== null) {
            $parent = $this->getItemByUser($parentId, $userId);
            if (!$parent || !$this->isEntity($parent['item_type'])) {
                throw new \InvalidArgumentException('Invalid parent');
            }
        }
        
        $sortOrder = $this->getNextSortOrder($userId, $parentId);
        
        $this->db->execute(
            'INSERT INTO user_folder_items 
             (user_id, parent_id, item_type, name, description, icon, color, sort_order, 
              folder_category, reference_id, reference_type, settings) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $userId, $parentId, $type, $name,
                $data['description'] ?? null, $data['icon'] ?? null, $data['color'] ?? null,
                $sortOrder, $data['folder_category'] ?? null,
                $data['reference_id'] ?? null, $data['reference_type'] ?? null,
                isset($data['settings']) ? json_encode($data['settings']) : null
            ]
        );
        
        $itemId = $this->db->lastInsertId();
        $this->addClosurePaths($itemId, $parentId);
        return $itemId;
    }

    protected function addClosurePaths(int $itemId, ?int $parentId): void
    {
        $this->db->execute(
            'INSERT IGNORE INTO folder_paths (ancestor_id, descendant_id, depth) VALUES (?, ?, 0)',
            [$itemId, $itemId]
        );
        
        if ($parentId !== null) {
            $this->db->execute(
                'INSERT INTO folder_paths (ancestor_id, descendant_id, depth)
                 SELECT ancestor_id, ?, depth + 1 FROM folder_paths WHERE descendant_id = ?',
                [$itemId, $parentId]
            );
        }
    }

    public function isDescendant(int $ancestorId, int $descendantId): bool
    {
        if ($ancestorId === $descendantId) return true;
        return (bool)$this->db->fetchOne(
            'SELECT 1 FROM folder_paths WHERE ancestor_id = ? AND descendant_id = ? AND depth > 0',
            [$ancestorId, $descendantId]
        );
    }

    public function updateItem(int $id, int $userId, array $data): bool
    {
        $allowed = ['name', 'description', 'icon', 'color', 'is_collapsed', 'folder_category', 'settings', 'is_hidden'];
        $fields = []; $values = [];
        foreach ($data as $k => $v) {
            if (in_array($k, $allowed)) {
                $fields[] = "$k = ?";
                $values[] = $k === 'settings' && is_array($v) ? json_encode($v) : $v;
            }
        }
        if (empty($fields)) return false;
        $values[] = $id; $values[] = $userId;
        return $this->db->execute('UPDATE user_folder_items SET ' . implode(', ', $fields) . ' WHERE id = ? AND user_id = ?', $values) > 0;
    }

    public function deleteItem(int $id, int $userId): bool
    {
        return $this->db->execute('DELETE FROM user_folder_items WHERE id = ? AND user_id = ?', [$id, $userId]) > 0;
    }

protected function getNextSortOrder(int $userId, ?int $parentId): int
{
    $sql = 'SELECT COALESCE(MAX(sort_order), 0) as mx 
            FROM user_folder_items 
            WHERE user_id = ?';
    
    $params = [$userId];
    
    if ($parentId === null) {
        $sql .= ' AND parent_id IS NULL';
    } else {
        $sql .= ' AND parent_id = ?';
        $params[] = $parentId;
    }

    $r = $this->db->fetchOne($sql, $params);
    return ($r['mx'] ?? 0) + 1;
}

public function moveItem(
    int $itemId, 
    int $userId, 
    ?int $newParentId, 
    ?int $afterItemId = null, 
    ?float $newSortOrder = null
): bool
{
    $item = $this->getItemByUser($itemId, $userId);
    if (!$item) {
        return false;
    }

    if ($newParentId === $itemId) {
        return false;
    }

    // Проверка родителя
    if ($newParentId !== null) {
        $parent = $this->getItemByUser($newParentId, $userId);
        if (!$parent || !$this->isEntity($parent['item_type'])) {
            return false;
        }
        if ($this->isDescendant($itemId, $newParentId)) {
            return false;
        }
    }

    $oldParentId = $item['parent_id'];

    // === Новый режим: клиент передал конкретное значение sort_order ===
    if ($newSortOrder !== null) {
        if ($oldParentId == $newParentId) {
            // Только меняем порядок внутри той же папки
            return $this->db->execute(
                'UPDATE user_folder_items SET sort_order = ? WHERE id = ?',
                [$newSortOrder, $itemId]
            ) > 0;
        } else {
            // Меняем родителя + устанавливаем новый sort_order
            $this->updateClosurePaths($itemId, $oldParentId, $newParentId);

            return $this->db->execute(
                'UPDATE user_folder_items SET parent_id = ?, sort_order = ? WHERE id = ?',
                [$newParentId, $newSortOrder, $itemId]
            ) > 0;
        }
    }

    // === Старый режим (совместимость): используем afterItemId ===
    if ($oldParentId == $newParentId) {
        // Тот же родитель — только меняем порядок
        if ($afterItemId) {
            $so = $this->getSortOrderAfter($userId, $newParentId, $afterItemId);
            return $this->db->execute(
                'UPDATE user_folder_items SET sort_order = ? WHERE id = ?',
                [$so, $itemId]
            ) > 0;
        }
        return true;
    } else {
        // Меняем родителя
        $this->updateClosurePaths($itemId, $oldParentId, $newParentId);

        $so = $afterItemId
            ? $this->getSortOrderAfter($userId, $newParentId, $afterItemId)
            : $this->getNextSortOrder($userId, $newParentId);

        return $this->db->execute(
            'UPDATE user_folder_items SET parent_id = ?, sort_order = ? WHERE id = ?',
            [$newParentId, $so, $itemId]
        ) > 0;
    }
}

    protected function updateClosurePaths(int $itemId, ?int $oldParentId, ?int $newParentId): void
    {
        $desc = $this->db->fetchAll('SELECT descendant_id FROM folder_paths WHERE ancestor_id = ?', [$itemId]);
        $descIds = array_column($desc, 'descendant_id');
        if (empty($descIds)) return;
        $ph = implode(',', array_fill(0, count($descIds), '?'));
        
        if ($oldParentId !== null) {
            $this->db->execute(
                "DELETE fp FROM folder_paths fp
                 JOIN folder_paths anc ON fp.ancestor_id = anc.ancestor_id
                 WHERE anc.descendant_id = ? AND fp.descendant_id IN ($ph) AND fp.ancestor_id != fp.descendant_id",
                array_merge([$oldParentId], $descIds)
            );
        }
        
        if ($newParentId !== null) {
            foreach ($descIds as $dId) {
                $dr = $this->db->fetchOne('SELECT depth FROM folder_paths WHERE ancestor_id = ? AND descendant_id = ?', [$itemId, $dId]);
                $sd = $dr['depth'] ?? 0;
                $this->db->execute(
                    'INSERT IGNORE INTO folder_paths (ancestor_id, descendant_id, depth)
                     SELECT ancestor_id, ?, depth + ? + 1 FROM folder_paths WHERE descendant_id = ?',
                    [$dId, $sd, $newParentId]
                );
            }
        }
    }

protected function getSortOrderAfter(int $userId, ?int $parentId, int $afterItemId): int
{
    $after = $this->getItemByUser($afterItemId, $userId);
    if (!$after) {
        return $this->getNextSortOrder($userId, $parentId);
    }

    $afterOrder = (int)$after['sort_order'];

    // Проверяем, есть ли уже элемент с sort_order = afterOrder + 1
    $sql = 'SELECT COUNT(*) as cnt 
            FROM user_folder_items 
            WHERE user_id = ? 
            AND parent_id ' . ($parentId === null ? 'IS NULL' : '= ?') . '
            AND sort_order = ?';

    $params = $parentId === null 
        ? [$userId, $afterOrder + 1] 
        : [$userId, $parentId, $afterOrder + 1];

    $exists = (int)$this->db->fetchOne($sql, $params)['cnt'];

    if ($exists === 0) {
        return $afterOrder + 1;                     // безопасно — место свободно
    }

    // Если место занято → сдвигаем все последующие элементы на +1
    $this->shiftSortOrdersAfter($userId, $parentId, $afterOrder);

    return $afterOrder + 1;
}

protected function shiftSortOrdersAfter(int $userId, ?int $parentId, int $fromOrder): void
{
    $sql = 'UPDATE user_folder_items 
            SET sort_order = sort_order + 1 
            WHERE user_id = ? 
            AND parent_id ' . ($parentId === null ? 'IS NULL' : '= ?') . '
            AND sort_order > ?';

    $params = $parentId === null 
        ? [$userId, $fromOrder] 
        : [$userId, $parentId, $fromOrder];

    $this->db->execute($sql, $params);
}

protected function normalizeSortOrders(int $userId, ?int $parentId): void
{
    $sql = 'SELECT id FROM user_folder_items 
            WHERE user_id = ? 
            AND parent_id ' . ($parentId === null ? 'IS NULL' : '= ?') . '
            ORDER BY sort_order ASC, id ASC';

    $params = $parentId === null ? [$userId] : [$userId, $parentId];
    
    $items = $this->db->fetchAll($sql, $params);
    
    foreach ($items as $index => $item) {
        $newOrder = $index + 1;
        $this->db->execute(
            'UPDATE user_folder_items SET sort_order = ? WHERE id = ?', 
            [$newOrder, $item['id']]
        );
    }
}

    public function toggleCollapsed(int $id, int $userId): bool
    {
        return $this->db->execute('UPDATE user_folder_items SET is_collapsed = NOT is_collapsed WHERE id = ? AND user_id = ?', [$id, $userId]) > 0;
    }

    public function fixOrphanedItems(int $userId): int
    {
        $orphans = $this->db->fetchAll(
            'SELECT i.id FROM user_folder_items i LEFT JOIN user_folder_items p ON i.parent_id = p.id WHERE i.user_id = ? AND i.parent_id IS NOT NULL AND p.id IS NULL',
            [$userId]
        );
        $cnt = 0;
        foreach ($orphans as $o) {
            $this->db->execute('UPDATE user_folder_items SET parent_id = NULL WHERE id = ?', [$o['id']]);
            $cnt++;
        }
        return $cnt;
    }
}
