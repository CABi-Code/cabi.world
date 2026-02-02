<?php

namespace App\Repository\UserFolderRepository;

trait EntitiesTrait
{
    /**
     * Создать категорию (папку)
     */
    public function createCategory(int $userId, string $name, ?int $parentId = null, array $options = []): int
    {
        $sortOrder = $this->getNextSortOrder($userId, $parentId);
        
        $this->db->execute(
            'INSERT INTO user_folder_items 
             (user_id, parent_id, item_type, name, description, icon, color, sort_order, settings) 
             VALUES (?, ?, "category", ?, ?, ?, ?, ?, ?)',
            [
                $userId,
                $parentId,
                $name,
                $options['description'] ?? null,
                $options['icon'] ?? 'folder',
                $options['color'] ?? null,
                $sortOrder,
                isset($options['settings']) ? json_encode($options['settings']) : null
            ]
        );
        
        return $this->db->lastInsertId();
    }

    /**
     * Создать модпак
     */
    public function createModpack(int $userId, string $name, ?int $parentId = null, array $options = []): int
    {
        $sortOrder = $this->getNextSortOrder($userId, $parentId);
        
        $this->db->execute(
            'INSERT INTO user_folder_items 
             (user_id, parent_id, item_type, name, description, icon, color, sort_order, reference_id, reference_type, settings) 
             VALUES (?, ?, "modpack", ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $userId,
                $parentId,
                $name,
                $options['description'] ?? null,
                $options['icon'] ?? 'package',
                $options['color'] ?? null,
                $sortOrder,
                $options['reference_id'] ?? null,
                $options['reference_type'] ?? null,
                isset($options['settings']) ? json_encode($options['settings']) : null
            ]
        );
        
        return $this->db->lastInsertId();
    }

    /**
     * Создать мод
     */
    public function createMod(int $userId, string $name, ?int $parentId = null, array $options = []): int
    {
        $sortOrder = $this->getNextSortOrder($userId, $parentId);
        
        $this->db->execute(
            'INSERT INTO user_folder_items 
             (user_id, parent_id, item_type, name, description, icon, color, sort_order, reference_id, reference_type, settings) 
             VALUES (?, ?, "mod", ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $userId,
                $parentId,
                $name,
                $options['description'] ?? null,
                $options['icon'] ?? 'puzzle',
                $options['color'] ?? null,
                $sortOrder,
                $options['reference_id'] ?? null,
                $options['reference_type'] ?? null,
                isset($options['settings']) ? json_encode($options['settings']) : null
            ]
        );
        
        return $this->db->lastInsertId();
    }

    /**
     * Создать специальную папку "Заявки" для пользователя
     */
    public function createApplicationsFolder(int $userId): int
    {
        return $this->createCategory($userId, 'Заявки', null, [
            'icon' => 'inbox',
            'color' => '#3b82f6',
            'settings' => ['system' => true, 'type' => 'applications']
        ]);
    }

    /**
     * Получить папку заявок пользователя
     */
    public function getApplicationsFolder(int $userId): ?array
    {
        return $this->db->fetchOne(
            "SELECT * FROM user_folder_items 
             WHERE user_id = ? AND item_type = 'category' 
             AND JSON_EXTRACT(settings, '$.type') = 'applications'",
            [$userId]
        );
    }

    /**
     * Переключить состояние свёрнутости
     */
    public function toggleCollapsed(int $id, int $userId): bool
    {
        return $this->db->execute(
            'UPDATE user_folder_items SET is_collapsed = NOT is_collapsed WHERE id = ? AND user_id = ?',
            [$id, $userId]
        ) > 0;
    }
}
