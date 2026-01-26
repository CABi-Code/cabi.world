<?php

namespace App\Repository\CommunityRepository;

trait CommunityTrait {

    // =============================================
    // СООБЩЕСТВО
    // =============================================

    /**
     * Получить сообщество пользователя
     */
    public function findByUserId(int $userId): ?array
    {
        return $this->db->fetchOne(
            'SELECT * FROM communities WHERE user_id = ?',
            [$userId]
        );
    }

    /**
     * Получить сообщество по ID
     */
    public function findById(int $id): ?array
    {
        return $this->db->fetchOne('SELECT * FROM communities WHERE id = ?', [$id]);
    }

    /**
     * Создать сообщество для пользователя
     */
    public function create(int $userId): int
    {
        $this->db->execute(
            'INSERT INTO communities (user_id) VALUES (?)',
            [$userId]
        );
        return $this->db->lastInsertId();
    }

    /**
     * Обновить настройки сообщества
     */
    public function update(int $id, array $data): bool
    {
        $allowed = ['message_timeout', 'files_disabled', 'messages_disabled'];
        $fields = [];
        $values = [];
        
        foreach ($data as $key => $value) {
            if (in_array($key, $allowed)) {
                $fields[] = "$key = ?";
                $values[] = $value;
            }
        }
        
        if (empty($fields)) return false;
        
        $values[] = $id;
        return $this->db->execute(
            'UPDATE communities SET ' . implode(', ', $fields) . ' WHERE id = ?',
            $values
        ) > 0;
    }

    /**
     * Удалить сообщество (и все связанные данные)
     */
    public function delete(int $id): bool
    {
        return $this->db->execute('DELETE FROM communities WHERE id = ?', [$id]) > 0;
    }

    /**
     * Проверить, есть ли у пользователя сообщество
     */
    public function userHasCommunity(int $userId): bool
    {
        return (bool) $this->db->fetchOne(
            'SELECT 1 FROM communities WHERE user_id = ?',
            [$userId]
        );
    }

    /**
     * Проверить, пустое ли сообщество (нет чатов и папок)
     */
    public function isEmpty(int $communityId): bool
    {
        $folders = $this->db->fetchOne(
            'SELECT 1 FROM community_folders WHERE community_id = ? LIMIT 1',
            [$communityId]
        );
        if ($folders) return false;
        
        $chats = $this->db->fetchOne(
            'SELECT 1 FROM community_chats WHERE community_id = ? LIMIT 1',
            [$communityId]
        );
        return !$chats;
    }

    // =============================================
    // ПАПКИ
    // =============================================

    /**
     * Получить папки сообщества (корневые или в родительской папке)
     */
    public function getFolders(int $communityId, ?int $parentId = null): array
    {
        if ($parentId === null) {
            return $this->db->fetchAll(
                'SELECT * FROM community_folders 
                 WHERE community_id = ? AND parent_id IS NULL 
                 ORDER BY sort_order, name',
                [$communityId]
            );
        }
        return $this->db->fetchAll(
            'SELECT * FROM community_folders 
             WHERE community_id = ? AND parent_id = ? 
             ORDER BY sort_order, name',
            [$communityId, $parentId]
        );
    }

    /**
     * Получить папку по ID
     */
    public function getFolder(int $id): ?array
    {
        return $this->db->fetchOne('SELECT * FROM community_folders WHERE id = ?', [$id]);
    }

    /**
     * Создать папку
     */
    public function createFolder(int $communityId, string $name, ?int $parentId = null): int
    {
        $sortOrder = $this->getNextFolderOrder($communityId, $parentId);
        
        $this->db->execute(
            'INSERT INTO community_folders (community_id, parent_id, name, sort_order) VALUES (?, ?, ?, ?)',
            [$communityId, $parentId, $name, $sortOrder]
        );
        return $this->db->lastInsertId();
    }

    /**
     * Обновить папку
     */
    public function updateFolder(int $id, array $data): bool
    {
        $allowed = ['name', 'sort_order', 'message_timeout', 'files_disabled', 'messages_disabled'];
        $fields = [];
        $values = [];
        
        foreach ($data as $key => $value) {
            if (in_array($key, $allowed)) {
                $fields[] = "$key = ?";
                $values[] = $value;
            }
        }
        
        if (empty($fields)) return false;
        
        $values[] = $id;
        return $this->db->execute(
            'UPDATE community_folders SET ' . implode(', ', $fields) . ' WHERE id = ?',
            $values
        ) > 0;
    }

    /**
     * Удалить папку
     */
    public function deleteFolder(int $id): bool
    {
        return $this->db->execute('DELETE FROM community_folders WHERE id = ?', [$id]) > 0;
    }

    /**
     * Получить следующий порядок для папки
     */
    private function getNextFolderOrder(int $communityId, ?int $parentId): int
    {
        if ($parentId === null) {
            $result = $this->db->fetchOne(
                'SELECT MAX(sort_order) as max_order FROM community_folders 
                 WHERE community_id = ? AND parent_id IS NULL',
                [$communityId]
            );
        } else {
            $result = $this->db->fetchOne(
                'SELECT MAX(sort_order) as max_order FROM community_folders 
                 WHERE community_id = ? AND parent_id = ?',
                [$communityId, $parentId]
            );
        }
        return ($result['max_order'] ?? 0) + 1;
    }
}

?>