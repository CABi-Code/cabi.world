<?php

namespace App\Repository\UserFolderRepository;

trait FoldersTrait
{
    /**
     * Создать папку
     */
    public function createFolder(int $userId, string $name, ?int $parentId = null, array $data = []): int
    {
        $data['icon'] = $data['icon'] ?? 'folder';
        return $this->createItem($userId, 'folder', $name, $parentId, $data);
    }

    /**
     * Создать чат
     */
    public function createChat(int $userId, string $name, ?int $parentId = null, array $data = []): int
    {
        $data['icon'] = $data['icon'] ?? 'message-circle';
        return $this->createItem($userId, 'chat', $name, $parentId, $data);
    }

    /**
     * Получить папку по категории
     */
    public function getFolderByCategory(int $userId, string $category): ?array
    {
        return $this->db->fetchOne(
            "SELECT * FROM user_folder_items 
             WHERE user_id = ? AND item_type = 'folder' AND folder_category = ?",
            [$userId, $category]
        );
    }

    /**
     * Получить или создать папку "Главные заявки"
     */
    public function getOrCreateMainApplicationsFolder(int $userId): array
    {
        $folder = $this->getFolderByCategory($userId, self::CATEGORY_MAIN_APPS);
        
        if (!$folder) {
            $id = $this->createFolder($userId, 'Заявки', null, [
                'folder_category' => self::CATEGORY_MAIN_APPS,
                'icon' => 'inbox',
                'color' => '#3b82f6'
            ]);
            $folder = $this->getItem($id);
        }
        
        return $folder;
    }

    /**
     * Получить или создать папку "Модпаки"
     */
    public function getOrCreateModpacksFolder(int $userId): array
    {
        $folder = $this->getFolderByCategory($userId, self::CATEGORY_MODPACKS);
        
        if (!$folder) {
            $id = $this->createFolder($userId, 'Мои модпаки', null, [
                'folder_category' => self::CATEGORY_MODPACKS,
                'icon' => 'package',
                'color' => '#8b5cf6'
            ]);
            $folder = $this->getItem($id);
        }
        
        return $folder;
    }

    /**
     * Найти или создать сущность модпака в папке модпаков
     */
    public function getOrCreateModpackEntity(int $userId, int $modpackId, string $modpackName): array
    {
        // Ищем существующую
        $entity = $this->db->fetchOne(
            "SELECT * FROM user_folder_items 
             WHERE user_id = ? AND item_type = 'modpack' 
             AND reference_type = 'modpacks' AND reference_id = ?",
            [$userId, $modpackId]
        );
        
        if ($entity) return $entity;
        
        // Создаём папку модпаков если нет
        $modpacksFolder = $this->getOrCreateModpacksFolder($userId);
        
        // Создаём сущность модпака
        $id = $this->createItem($userId, 'modpack', $modpackName, $modpacksFolder['id'], [
            'reference_id' => $modpackId,
            'reference_type' => 'modpacks',
            'icon' => 'package'
        ]);
        
        return $this->getItem($id);
    }

    /**
     * Проверить, пустая ли папка пользователя
     */
    public function isEmpty(int $userId): bool
    {
        $result = $this->db->fetchOne(
            'SELECT 1 FROM user_folder_items WHERE user_id = ? LIMIT 1',
            [$userId]
        );
        return !$result;
    }

    /**
     * Проверить, есть ли папка с данной категорией
     */
    public function hasCategoryFolder(int $userId, string $category): bool
    {
        return (bool)$this->db->fetchOne(
            "SELECT 1 FROM user_folder_items 
             WHERE user_id = ? AND item_type = 'folder' AND folder_category = ?",
            [$userId, $category]
        );
    }
}
