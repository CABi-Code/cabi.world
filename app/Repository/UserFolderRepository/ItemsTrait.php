<?php

namespace App\Repository\UserFolderRepository;

trait ItemsTrait
{
    public function getItem(int $id): ?array
    {
        return $this->db->fetchOne('SELECT * FROM user_folder_items WHERE id = ?', [$id]);
    }

    /**
     * Получить элемент по slug
     */
    public function getItemBySlug(string $slug): ?array
    {
        return $this->db->fetchOne('SELECT * FROM user_folder_items WHERE slug = ?', [$slug]);
    }

    /**
     * Получить элемент по slug-имени (без префикса типа) и user_id
     */
    public function getItemBySlugAndUser(string $slug, int $userId): ?array
    {
        return $this->db->fetchOne(
            'SELECT * FROM user_folder_items WHERE slug = ? AND user_id = ?',
            [$slug, $userId]
        );
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
    
    /**
     * Получить дочерние элементы по parent_id (без user_id)
     * Используется для публичного отображения
     */
    public function getChildrenByParent(int $parentId): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM user_folder_items WHERE parent_id = ? ORDER BY sort_order, name',
            [$parentId]
        );
    }

    /**
     * Получить путь к элементу (хлебные крошки)
     */
    public function getItemPath(int $id): array
    {
        $path = [];
        $current = $this->getItem($id);
        
        while ($current) {
            array_unshift($path, [
                'id' => $current['id'],
                'name' => $current['name'],
                'slug' => $current['slug'] ?? null,
                'item_type' => $current['item_type'],
                'icon' => $current['icon'],
                'color' => $current['color']
            ]);
            
            if ($current['parent_id']) {
                $current = $this->getItem($current['parent_id']);
            } else {
                break;
            }
        }
        
        return $path;
    }

    /**
     * Генерация UUID-hash slug для элемента
     */
    public function generateSlug(int $itemId, string $type, string $name, string $createdAt): string
    {
        $raw = $itemId . '-' . $createdAt . '-' . $type . '-' . $name;
        return substr(md5($raw), 0, 8);
    }

    /**
     * Получить полный slug с префиксом типа (для URL)
     */
    public static function getFullSlug(string $itemType, string $slug): string
    {
        $prefix = self::SLUG_PREFIXES[$itemType] ?? 'string-';
        return $prefix . $slug;
    }

    /**
     * Проверить, свободен ли slug
     */
    public function isSlugAvailable(string $slug, ?int $excludeItemId = null): bool
    {
        if ($excludeItemId) {
            $row = $this->db->fetchOne(
                'SELECT id FROM user_folder_items WHERE slug = ? AND id != ?',
                [$slug, $excludeItemId]
            );
        } else {
            $row = $this->db->fetchOne(
                'SELECT id FROM user_folder_items WHERE slug = ?',
                [$slug]
            );
        }
        return $row === null;
    }

    /**
     * Валидация slug: не пустой, минимум 3 символа, только допустимые символы,
     * не содержит зарезервированных слов
     */
    public function validateSlug(string $slug, ?int $excludeItemId = null): ?string
    {
        if (empty($slug)) {
            return 'Ссылка не может быть пустой';
        }
        if (mb_strlen($slug) < 3) {
            return 'Ссылка слишком короткая (минимум 3 символа)';
        }
        if (mb_strlen($slug) > 80) {
            return 'Ссылка слишком длинная (максимум 80 символов)';
        }
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $slug)) {
            return 'Ссылка может содержать только латинские буквы, цифры, дефис и подчёркивание';
        }

        // Проверка зарезервированных слов
        $reservedFile = __DIR__ . '/../../Auth/login-reserved.txt';
        if (file_exists($reservedFile)) {
            $reserved = file($reservedFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if (in_array(strtolower($slug), array_map('strtolower', $reserved))) {
                return 'Это слово зарезервировано и не может быть использовано в ссылке';
            }
        }

        if (!$this->isSlugAvailable($slug, $excludeItemId)) {
            return 'Эта ссылка уже занята';
        }

        return null;
    }

    /**
     * Обновить slug элемента
     */
    public function updateSlug(int $id, int $userId, string $slug): bool
    {
        return $this->db->execute(
            'UPDATE user_folder_items SET slug = ? WHERE id = ? AND user_id = ?',
            [$slug, $id, $userId]
        ) > 0;
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

        // Генерация временного slug (будет обновлён после получения ID)
        $tempSlug = substr(md5(uniqid((string)$userId, true)), 0, 8);

        $this->db->execute(
            'INSERT INTO user_folder_items
             (user_id, parent_id, item_type, name, slug, description, icon, color, sort_order,
              folder_category, reference_id, reference_type, settings)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $userId, $parentId, $type, $name, $tempSlug,
                $data['description'] ?? null, $data['icon'] ?? null, $data['color'] ?? null,
                $sortOrder, $data['folder_category'] ?? null,
                $data['reference_id'] ?? null, $data['reference_type'] ?? null,
                isset($data['settings']) ? json_encode($data['settings']) : null
            ]
        );

        $itemId = $this->db->lastInsertId();

        // Генерируем финальный slug на основе ID
        $item = $this->getItem($itemId);
        $slug = $this->generateSlug($itemId, $type, $name, $item['created_at']);

        // Если slug уже занят, добавляем суффикс
        while (!$this->isSlugAvailable($slug, $itemId)) {
            $slug = $slug . substr(md5(uniqid('', true)), 0, 4);
        }

        $this->db->execute('UPDATE user_folder_items SET slug = ? WHERE id = ?', [$slug, $itemId]);

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

    public function updateItem(int $id, int $userId, array $data): bool
    {
        $allowed = ['name', 'description', 'icon', 'color', 'is_collapsed', 'folder_category', 'settings', 'is_hidden', 'slug'];
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

    protected function getNextSortOrder(int $userId, ?int $parentId): float
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
        return ((float)($r['mx'] ?? 0)) + 1.0;
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

    public function isDescendant(int $ancestorId, int $descendantId): bool
    {
        if ($ancestorId === $descendantId) return true;
        return (bool)$this->db->fetchOne(
            'SELECT 1 FROM folder_paths WHERE ancestor_id = ? AND descendant_id = ? AND depth > 0',
            [$ancestorId, $descendantId]
        );
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

    protected function getSortOrderAfter(int $userId, ?int $parentId, int $afterItemId): float
    {
        $after = $this->getItemByUser($afterItemId, $userId);
        if (!$after) {
            return $this->getNextSortOrder($userId, $parentId);
        }

        $afterOrder = (float)$after['sort_order'];

        // Находим следующий элемент
        $sql = 'SELECT sort_order FROM user_folder_items 
                WHERE user_id = ? 
                AND parent_id ' . ($parentId === null ? 'IS NULL' : '= ?') . '
                AND sort_order > ?
                ORDER BY sort_order ASC
                LIMIT 1';

        $params = $parentId === null 
            ? [$userId, $afterOrder] 
            : [$userId, $parentId, $afterOrder];

        $next = $this->db->fetchOne($sql, $params);

        if ($next) {
            // Вставляем между текущим и следующим (DECIMAL позволяет)
            return ($afterOrder + (float)$next['sort_order']) / 2;
        }

        // Нет следующего — просто +1
        return $afterOrder + 1.0;
    }

    protected function shiftSortOrdersAfter(int $userId, ?int $parentId, float $fromOrder): void
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
            $newOrder = ($index + 1) * 1.0;
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
