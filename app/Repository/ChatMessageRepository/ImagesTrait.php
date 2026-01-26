<?php

namespace App\Repository\ChatMessageRepository;

trait ImagesTrait {

    // =============================================
    // ИЗОБРАЖЕНИЯ
    // =============================================

    /**
     * Добавить изображение к сообщению
     */
    public function addImage(int $messageId, string $path, int $sortOrder = 0): int
    {
        $currentCount = $this->countImages($messageId);
        if ($currentCount >= self::MAX_IMAGES) {
            return 0;
        }
        
        $this->db->execute(
            'INSERT INTO chat_message_images (message_id, image_path, sort_order) VALUES (?, ?, ?)',
            [$messageId, $path, $sortOrder]
        );
        return $this->db->lastInsertId();
    }

    /**
     * Получить изображения сообщения
     */
    public function getMessageImages(int $messageId): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM chat_message_images WHERE message_id = ? ORDER BY sort_order',
            [$messageId]
        );
    }

    /**
     * Получить изображения для нескольких сообщений
     */
    private function getImagesForMessages(array $messageIds): array
    {
        if (empty($messageIds)) return [];
        
        $placeholders = implode(',', array_fill(0, count($messageIds), '?'));
        $images = $this->db->fetchAll(
            "SELECT * FROM chat_message_images WHERE message_id IN ($placeholders) ORDER BY sort_order",
            $messageIds
        );
        
        $grouped = [];
        foreach ($images as $image) {
            $grouped[$image['message_id']][] = $image;
        }
        
        return $grouped;
    }

    /**
     * Подсчитать изображения сообщения
     */
    public function countImages(int $messageId): int
    {
        $result = $this->db->fetchOne(
            'SELECT COUNT(*) as cnt FROM chat_message_images WHERE message_id = ?',
            [$messageId]
        );
        return (int) ($result['cnt'] ?? 0);
    }

    // =============================================
    // ЛАЙКИ
    // =============================================

    /**
     * Поставить/убрать лайк
     */
    public function toggleLike(int $messageId, int $userId): array
    {
        $existing = $this->db->fetchOne(
            'SELECT id FROM chat_message_likes WHERE message_id = ? AND user_id = ?',
            [$messageId, $userId]
        );
        
        if ($existing) {
            $this->db->execute(
                'DELETE FROM chat_message_likes WHERE message_id = ? AND user_id = ?',
                [$messageId, $userId]
            );
            return ['liked' => false];
        }
        
        $this->db->execute(
            'INSERT INTO chat_message_likes (message_id, user_id) VALUES (?, ?)',
            [$messageId, $userId]
        );
        return ['liked' => true];
    }

    /**
     * Проверить, лайкнул ли пользователь
     */
    public function hasLiked(int $messageId, int $userId): bool
    {
        return (bool) $this->db->fetchOne(
            'SELECT 1 FROM chat_message_likes WHERE message_id = ? AND user_id = ?',
            [$messageId, $userId]
        );
    }

    /**
     * Получить лайки для нескольких сообщений от конкретного пользователя
     */
    public function getUserLikesForMessages(array $messageIds, int $userId): array
    {
        if (empty($messageIds)) return [];
        
        $placeholders = implode(',', array_fill(0, count($messageIds), '?'));
        $params = array_merge($messageIds, [$userId]);
        
        $likes = $this->db->fetchAll(
            "SELECT message_id FROM chat_message_likes 
             WHERE message_id IN ($placeholders) AND user_id = ?",
            $params
        );
        
        return array_column($likes, 'message_id');
    }
}

?>