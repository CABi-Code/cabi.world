<?php

namespace App\Repository\CommunityRepository;

trait ChatsTrait {

    // =============================================
    // ЧАТЫ
    // =============================================

    /**
     * Получить чаты (в корне или в папке)
     */
    public function getChats(int $communityId, ?int $folderId = null): array
    {
        if ($folderId === null) {
            return $this->db->fetchAll(
                'SELECT * FROM community_chats 
                 WHERE community_id = ? AND folder_id IS NULL 
                 ORDER BY sort_order, name',
                [$communityId]
            );
        }
        return $this->db->fetchAll(
            'SELECT * FROM community_chats 
             WHERE community_id = ? AND folder_id = ? 
             ORDER BY sort_order, name',
            [$communityId, $folderId]
        );
    }

    /**
     * Получить чат по ID
     */
    public function getChat(int $id): ?array
    {
        return $this->db->fetchOne('SELECT * FROM community_chats WHERE id = ?', [$id]);
    }

    /**
     * Получить чат с информацией о сообществе
     */
    public function getChatWithCommunity(int $chatId): ?array
    {
        return $this->db->fetchOne(
            'SELECT c.*, com.user_id as owner_id 
             FROM community_chats c 
             JOIN communities com ON c.community_id = com.id 
             WHERE c.id = ?',
            [$chatId]
        );
    }

    /**
     * Создать чат
     */
    public function createChat(int $communityId, string $name, ?int $folderId = null, ?string $description = null): int
    {
        $sortOrder = $this->getNextChatOrder($communityId, $folderId);
        
        $this->db->execute(
            'INSERT INTO community_chats (community_id, folder_id, name, description, sort_order) 
             VALUES (?, ?, ?, ?, ?)',
            [$communityId, $folderId, $name, $description, $sortOrder]
        );
        return $this->db->lastInsertId();
    }

    /**
     * Обновить чат
     */
    public function updateChat(int $id, array $data): bool
    {
        $allowed = ['name', 'description', 'sort_order', 'message_timeout', 'files_disabled', 'messages_disabled'];
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
            'UPDATE community_chats SET ' . implode(', ', $fields) . ' WHERE id = ?',
            $values
        ) > 0;
    }

    /**
     * Удалить чат
     */
    public function deleteChat(int $id): bool
    {
        return $this->db->execute('DELETE FROM community_chats WHERE id = ?', [$id]) > 0;
    }

    /**
     * Получить следующий порядок для чата
     */
    private function getNextChatOrder(int $communityId, ?int $folderId): int
    {
        if ($folderId === null) {
            $result = $this->db->fetchOne(
                'SELECT MAX(sort_order) as max_order FROM community_chats 
                 WHERE community_id = ? AND folder_id IS NULL',
                [$communityId]
            );
        } else {
            $result = $this->db->fetchOne(
                'SELECT MAX(sort_order) as max_order FROM community_chats 
                 WHERE community_id = ? AND folder_id = ?',
                [$communityId, $folderId]
            );
        }
        return ($result['max_order'] ?? 0) + 1;
    }

    /**
     * Получить эффективные настройки для чата
     * (учитывая иерархию: чат -> папка -> сообщество)
     */
    public function getChatEffectiveSettings(int $chatId): array
    {
        $chat = $this->getChat($chatId);
        if (!$chat) return [];
        
        $community = $this->findById($chat['community_id']);
        $folder = $chat['folder_id'] ? $this->getFolder($chat['folder_id']) : null;
        
        return [
            'message_timeout' => $chat['message_timeout'] 
                ?? ($folder['message_timeout'] ?? null) 
                ?? $community['message_timeout'],
            'files_disabled' => $chat['files_disabled'] 
                ?? ($folder['files_disabled'] ?? null) 
                ?? $community['files_disabled'],
            'messages_disabled' => $chat['messages_disabled'] 
                ?? ($folder['messages_disabled'] ?? null) 
                ?? $community['messages_disabled'],
        ];
    }
}

?>