<?php

namespace App\Repository\CommunityRepository;

trait SubscriptionsTrait {

    // =============================================
    // ПОДПИСКИ
    // =============================================

    /**
     * Подписаться на сообщество
     */
    public function subscribe(int $communityId, int $userId): bool
    {
        try {
            $this->db->execute(
                'INSERT INTO community_subscribers (community_id, user_id) VALUES (?, ?)',
                [$communityId, $userId]
            );
            return true;
        } catch (\PDOException $e) {
            if ($e->getCode() == 23000) return false; // Duplicate
            throw $e;
        }
    }

    /**
     * Отписаться от сообщества
     */
    public function unsubscribe(int $communityId, int $userId): bool
    {
        return $this->db->execute(
            'DELETE FROM community_subscribers WHERE community_id = ? AND user_id = ?',
            [$communityId, $userId]
        ) > 0;
    }

    /**
     * Проверить, подписан ли пользователь
     */
    public function isSubscribed(int $communityId, int $userId): bool
    {
        return (bool) $this->db->fetchOne(
            'SELECT 1 FROM community_subscribers WHERE community_id = ? AND user_id = ?',
            [$communityId, $userId]
        );
    }

    /**
     * Получить подписчиков сообщества
     */
    public function getSubscribers(int $communityId, int $limit = 50, int $offset = 0): array
    {
        return $this->db->fetchAll(
            'SELECT u.id, u.login, u.username, u.avatar, u.avatar_bg_value, u.role
             FROM community_subscribers cs
             JOIN users u ON cs.user_id = u.id
             WHERE cs.community_id = ?
             ORDER BY cs.created_at DESC
             LIMIT ? OFFSET ?',
            [$communityId, $limit, $offset]
        );
    }

    /**
     * Поиск подписчиков по имени/логину
     */
    public function searchSubscribers(int $communityId, string $query, int $limit = 20): array
    {
        $searchTerm = '%' . $query . '%';
        return $this->db->fetchAll(
            'SELECT u.id, u.login, u.username, u.avatar, u.avatar_bg_value, u.role
             FROM community_subscribers cs
             JOIN users u ON cs.user_id = u.id
             WHERE cs.community_id = ? AND (u.username LIKE ? OR u.login LIKE ?)
             ORDER BY u.username
             LIMIT ?',
            [$communityId, $searchTerm, $searchTerm, $limit]
        );
    }

    /**
     * Получить сообщества, на которые подписан пользователь
     */
    public function getUserSubscriptions(int $userId, int $limit = 50, int $offset = 0): array
    {
        return $this->db->fetchAll(
            'SELECT c.*, u.login as owner_login, u.username as owner_username, 
                    u.avatar as owner_avatar, u.avatar_bg_value as owner_avatar_bg
             FROM community_subscribers cs
             JOIN communities c ON cs.community_id = c.id
             JOIN users u ON c.user_id = u.id
             WHERE cs.user_id = ?
             ORDER BY cs.created_at DESC
             LIMIT ? OFFSET ?',
            [$userId, $limit, $offset]
        );
    }

    /**
     * Количество подписок пользователя
     */
    public function countUserSubscriptions(int $userId): int
    {
        $result = $this->db->fetchOne(
            'SELECT COUNT(*) as cnt FROM community_subscribers WHERE user_id = ?',
            [$userId]
        );
        return (int) ($result['cnt'] ?? 0);
    }
}

?>