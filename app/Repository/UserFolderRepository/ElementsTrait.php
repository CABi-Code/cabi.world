<?php

namespace App\Repository\UserFolderRepository;

trait ElementsTrait
{
    /**
     * Создать элемент "сервер"
     */
    public function createServer(int $userId, string $name, ?int $parentId = null, array $options = []): int
    {
        // Сначала создаём запись сервера
        $this->db->execute(
            'INSERT INTO user_servers (user_id, name, address, port, version, description) 
             VALUES (?, ?, ?, ?, ?, ?)',
            [
                $userId,
                $name,
                $options['address'] ?? null,
                $options['port'] ?? 25565,
                $options['version'] ?? null,
                $options['description'] ?? null
            ]
        );
        $serverId = $this->db->lastInsertId();
        
        // Создаём элемент в папке
        $sortOrder = $this->getNextSortOrder($userId, $parentId);
        
        $this->db->execute(
            'INSERT INTO user_folder_items 
             (user_id, parent_id, item_type, name, icon, sort_order, reference_id, reference_type) 
             VALUES (?, ?, "server", ?, "server", ?, ?, "user_servers")',
            [$userId, $parentId, $name, $sortOrder, $serverId]
        );
        
        return $this->db->lastInsertId();
    }

    /**
     * Создать элемент "заявка"
     */
    public function createApplication(int $userId, int $applicationId, ?int $parentId = null): int
    {
        // Получаем данные заявки
        $app = $this->db->fetchOne(
            'SELECT a.*, m.name as modpack_name FROM modpack_applications a 
             JOIN modpacks m ON a.modpack_id = m.id WHERE a.id = ?',
            [$applicationId]
        );
        
        if (!$app) return 0;
        
        $sortOrder = $this->getNextSortOrder($userId, $parentId);
        
        $this->db->execute(
            'INSERT INTO user_folder_items 
             (user_id, parent_id, item_type, name, icon, sort_order, reference_id, reference_type) 
             VALUES (?, ?, "application", ?, "file-text", ?, ?, "modpack_applications")',
            [$userId, $parentId, $app['modpack_name'], $sortOrder, $applicationId]
        );
        
        return $this->db->lastInsertId();
    }

    /**
     * Создать элемент "чат"
     */
    public function createChat(int $userId, int $chatId, ?int $parentId = null): int
    {
        // Получаем данные чата
        $chat = $this->db->fetchOne(
            'SELECT * FROM community_chats WHERE id = ?',
            [$chatId]
        );
        
        if (!$chat) return 0;
        
        $sortOrder = $this->getNextSortOrder($userId, $parentId);
        
        $this->db->execute(
            'INSERT INTO user_folder_items 
             (user_id, parent_id, item_type, name, icon, sort_order, reference_id, reference_type) 
             VALUES (?, ?, "chat", ?, "message-circle", ?, ?, "community_chats")',
            [$userId, $parentId, $chat['name'], $sortOrder, $chatId]
        );
        
        return $this->db->lastInsertId();
    }

    /**
     * Создать элемент "ярлык"
     */
    public function createShortcut(int $userId, string $name, string $url, ?int $parentId = null, array $options = []): int
    {
        // Сначала создаём запись ярлыка
        $this->db->execute(
            'INSERT INTO user_shortcuts (user_id, name, url, icon, color, description) 
             VALUES (?, ?, ?, ?, ?, ?)',
            [
                $userId,
                $name,
                $url,
                $options['icon'] ?? 'link',
                $options['color'] ?? null,
                $options['description'] ?? null
            ]
        );
        $shortcutId = $this->db->lastInsertId();
        
        // Создаём элемент в папке
        $sortOrder = $this->getNextSortOrder($userId, $parentId);
        
        $this->db->execute(
            'INSERT INTO user_folder_items 
             (user_id, parent_id, item_type, name, icon, sort_order, reference_id, reference_type) 
             VALUES (?, ?, "shortcut", ?, ?, ?, ?, "user_shortcuts")',
            [
                $userId,
                $parentId,
                $name,
                $options['icon'] ?? 'link',
                $sortOrder,
                $shortcutId
            ]
        );
        
        return $this->db->lastInsertId();
    }

    /**
     * Получить данные сервера
     */
    public function getServer(int $serverId): ?array
    {
        return $this->db->fetchOne('SELECT * FROM user_servers WHERE id = ?', [$serverId]);
    }

    /**
     * Обновить данные сервера
     */
    public function updateServer(int $serverId, int $userId, array $data): bool
    {
        $allowed = ['name', 'address', 'port', 'version', 'description'];
        $fields = [];
        $values = [];
        
        foreach ($data as $key => $value) {
            if (in_array($key, $allowed)) {
                $fields[] = "$key = ?";
                $values[] = $value;
            }
        }
        
        if (empty($fields)) return false;
        
        $values[] = $serverId;
        $values[] = $userId;
        
        return $this->db->execute(
            'UPDATE user_servers SET ' . implode(', ', $fields) . ' WHERE id = ? AND user_id = ?',
            $values
        ) > 0;
    }

    /**
     * Получить данные ярлыка
     */
    public function getShortcut(int $shortcutId): ?array
    {
        return $this->db->fetchOne('SELECT * FROM user_shortcuts WHERE id = ?', [$shortcutId]);
    }

    /**
     * Обновить данные ярлыка
     */
    public function updateShortcut(int $shortcutId, int $userId, array $data): bool
    {
        $allowed = ['name', 'url', 'icon', 'color', 'description'];
        $fields = [];
        $values = [];
        
        foreach ($data as $key => $value) {
            if (in_array($key, $allowed)) {
                $fields[] = "$key = ?";
                $values[] = $value;
            }
        }
        
        if (empty($fields)) return false;
        
        $values[] = $shortcutId;
        $values[] = $userId;
        
        return $this->db->execute(
            'UPDATE user_shortcuts SET ' . implode(', ', $fields) . ' WHERE id = ? AND user_id = ?',
            $values
        ) > 0;
    }
}
