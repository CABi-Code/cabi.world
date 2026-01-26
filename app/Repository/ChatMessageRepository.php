<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;

class ChatMessageRepository
{
    private Database $db;
    
    public const MAX_IMAGES = 4;
    public const MAX_MESSAGE_LENGTH = 2000;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

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
                LIMIT 100';
        
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

    // =============================================
    // ОПРОСЫ
    // =============================================

    /**
     * Создать опрос
     */
    public function createPoll(int $messageId, string $question, array $options, bool $isMultiple = false): int
    {
        $this->db->execute(
            'INSERT INTO chat_polls (message_id, question, is_multiple) VALUES (?, ?, ?)',
            [$messageId, $question, $isMultiple ? 1 : 0]
        );
        $pollId = $this->db->lastInsertId();
        
        foreach ($options as $i => $optionText) {
            $this->db->execute(
                'INSERT INTO chat_poll_options (poll_id, option_text, sort_order) VALUES (?, ?, ?)',
                [$pollId, $optionText, $i]
            );
        }
        
        return $pollId;
    }

    /**
     * Получить опрос по message_id
     */
    public function getPoll(int $messageId): ?array
    {
        $poll = $this->db->fetchOne(
            'SELECT * FROM chat_polls WHERE message_id = ?',
            [$messageId]
        );
        
        if (!$poll) return null;
        
        $poll['options'] = $this->db->fetchAll(
            'SELECT * FROM chat_poll_options WHERE poll_id = ? ORDER BY sort_order',
            [$poll['id']]
        );
        
        return $poll;
    }

    /**
     * Проголосовать в опросе
     */
    public function vote(int $optionId, int $userId): bool
    {
        // Получаем информацию о опросе
        $option = $this->db->fetchOne(
            'SELECT po.*, p.is_multiple 
             FROM chat_poll_options po
             JOIN chat_polls p ON po.poll_id = p.id
             WHERE po.id = ?',
            [$optionId]
        );
        
        if (!$option) return false;
        
        // Если не множественный выбор, удаляем предыдущие голоса
        if (!$option['is_multiple']) {
            $this->db->execute(
                'DELETE FROM chat_poll_votes 
                 WHERE user_id = ? AND option_id IN (
                     SELECT id FROM chat_poll_options WHERE poll_id = ?
                 )',
                [$userId, $option['poll_id']]
            );
        }
        
        try {
            $this->db->execute(
                'INSERT INTO chat_poll_votes (option_id, user_id) VALUES (?, ?)',
                [$optionId, $userId]
            );
            return true;
        } catch (\PDOException $e) {
            if ($e->getCode() == 23000) return false;
            throw $e;
        }
    }

    /**
     * Убрать голос
     */
    public function unvote(int $optionId, int $userId): bool
    {
        return $this->db->execute(
            'DELETE FROM chat_poll_votes WHERE option_id = ? AND user_id = ?',
            [$optionId, $userId]
        ) > 0;
    }

    /**
     * Получить голоса пользователя в опросе
     */
    public function getUserVotes(int $pollId, int $userId): array
    {
        return $this->db->fetchAll(
            'SELECT pv.option_id 
             FROM chat_poll_votes pv
             JOIN chat_poll_options po ON pv.option_id = po.id
             WHERE po.poll_id = ? AND pv.user_id = ?',
            [$pollId, $userId]
        );
    }

    // =============================================
    // ВСПОМОГАТЕЛЬНЫЕ
    // =============================================

    /**
     * Проверить тайм-аут на отправку сообщений
     */
    public function canSendMessage(int $chatId, int $userId, int $timeout): bool
    {
        if ($timeout <= 0) return true;
        
        $lastMessage = $this->db->fetchOne(
            'SELECT created_at FROM chat_messages 
             WHERE chat_id = ? AND user_id = ? 
             ORDER BY created_at DESC LIMIT 1',
            [$chatId, $userId]
        );
        
        if (!$lastMessage) return true;
        
        $lastTime = strtotime($lastMessage['created_at']);
        $now = time();
        
        return ($now - $lastTime) >= $timeout;
    }

    /**
     * Получить оставшееся время до возможности отправки
     */
    public function getTimeUntilCanSend(int $chatId, int $userId, int $timeout): int
    {
        if ($timeout <= 0) return 0;
        
        $lastMessage = $this->db->fetchOne(
            'SELECT created_at FROM chat_messages 
             WHERE chat_id = ? AND user_id = ? 
             ORDER BY created_at DESC LIMIT 1',
            [$chatId, $userId]
        );
        
        if (!$lastMessage) return 0;
        
        $lastTime = strtotime($lastMessage['created_at']);
        $now = time();
        $elapsed = $now - $lastTime;
        
        return max(0, $timeout - $elapsed);
    }
}
