<?php

namespace App\Repository\ChatMessageRepository;

trait MessagesTrait {

    // =============================================
    // СООБЩЕНИЯ
    // =============================================

    /**
     * Получить сообщения чата с пагинацией
     */
    public function getMessages(int $chatId, int $limit = 50, ?int $beforeId = null): array
    {
        $sql = 'SELECT m.*, u.login, u.username, u.avatar, u.avatar_bg_value, u.role
                FROM chat_messages m
                JOIN users u ON m.user_id = u.id
                WHERE m.chat_id = ?';
        $params = [$chatId];
        
        if ($beforeId !== null) {
            $sql .= ' AND m.id < ?';
            $params[] = $beforeId;
        }
        
        $sql .= ' ORDER BY m.id DESC LIMIT ?';
        $params[] = $limit;
        
        $messages = $this->db->fetchAll($sql, $params);
        
        // Загружаем изображения для сообщений
        if (!empty($messages)) {
            $messageIds = array_column($messages, 'id');
            $images = $this->getImagesForMessages($messageIds);
            
            foreach ($messages as &$message) {
                $message['images'] = $images[$message['id']] ?? [];
            }
        }
        
        return array_reverse($messages); // Возвращаем в хронологическом порядке
    }

    /**
     * Получить новые сообщения (для подгрузки)
     */
    public function getNewMessages(int $chatId, int $afterId): array
    {
        $sql = 'SELECT m.*, u.login, u.username, u.avatar, u.avatar_bg_value, u.role
                FROM chat_messages m
                JOIN users u ON m.user_id = u.id
                WHERE m.chat_id = ? AND m.id > ?
                ORDER BY m.id ASC
                LIMIT 10';
        
        $messages = $this->db->fetchAll($sql, [$chatId, $afterId]);
        
        if (!empty($messages)) {
            $messageIds = array_column($messages, 'id');
            $images = $this->getImagesForMessages($messageIds);
            
            foreach ($messages as &$message) {
                $message['images'] = $images[$message['id']] ?? [];
            }
        }
        
        return $messages;
    }

    /**
     * Получить сообщение по ID
     */
    public function getMessage(int $id): ?array
    {
        $message = $this->db->fetchOne(
            'SELECT m.*, u.login, u.username, u.avatar, u.avatar_bg_value, u.role
             FROM chat_messages m
             JOIN users u ON m.user_id = u.id
             WHERE m.id = ?',
            [$id]
        );
        
        if ($message) {
            $message['images'] = $this->getMessageImages($id);
        }
        
        return $message;
    }

    /**
     * Создать сообщение
     */
    public function create(int $chatId, int $userId, ?string $message = null, bool $isPoll = false): int
    {
        $this->db->execute(
            'INSERT INTO chat_messages (chat_id, user_id, message, is_poll) VALUES (?, ?, ?, ?)',
            [$chatId, $userId, $message, $isPoll ? 1 : 0]
        );
        return $this->db->lastInsertId();
    }

    /**
     * Удалить сообщение
     */
    public function delete(int $id): bool
    {
        return $this->db->execute('DELETE FROM chat_messages WHERE id = ?', [$id]) > 0;
    }

    /**
     * Проверить, принадлежит ли сообщение пользователю
     */
    public function isOwner(int $messageId, int $userId): bool
    {
        return (bool) $this->db->fetchOne(
            'SELECT 1 FROM chat_messages WHERE id = ? AND user_id = ?',
            [$messageId, $userId]
        );
    }

    /**
     * Получить chat_id и community_id по message_id
     */
    public function getMessageContext(int $messageId): ?array
    {
        return $this->db->fetchOne(
            'SELECT m.chat_id, c.community_id, c.folder_id
             FROM chat_messages m
             JOIN community_chats c ON m.chat_id = c.id
             WHERE m.id = ?',
            [$messageId]
        );
    }
}

?>