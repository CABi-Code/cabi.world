<?php

namespace App\Repository\UserFolderRepository;

trait SubscriptionsTrait
{
    /**
     * Подписаться на папку пользователя
     */
    public function subscribe(int $folderOwnerId, int $subscriberId): bool
    {
        if ($folderOwnerId === $subscriberId) return false;
        
        try {
            $this->db->execute(
                'INSERT IGNORE INTO user_folder_subscriptions (folder_owner_id, subscriber_id) VALUES (?, ?)',
                [$folderOwnerId, $subscriberId]
            );
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Отписаться от папки
     */
    public function unsubscribe(int $folderOwnerId, int $subscriberId): bool
    {
        return $this->db->execute(
            'DELETE FROM user_folder_subscriptions WHERE folder_owner_id = ? AND subscriber_id = ?',
            [$folderOwnerId, $subscriberId]
        ) > 0;
    }

    /**
     * Проверить подписку
     */
    public function isSubscribed(int $folderOwnerId, int $subscriberId): bool
    {
        return (bool)$this->db->fetchOne(
            'SELECT 1 FROM user_folder_subscriptions WHERE folder_owner_id = ? AND subscriber_id = ?',
            [$folderOwnerId, $subscriberId]
        );
    }

    /**
     * Получить количество подписчиков
     */
    public function getSubscribersCount(int $userId): int
    {
        $result = $this->db->fetchOne(
            'SELECT COUNT(*) as cnt FROM user_folder_subscriptions WHERE folder_owner_id = ?',
            [$userId]
        );
        return (int)($result['cnt'] ?? 0);
    }

    /**
     * Получить подписки пользователя
     */
    public function getUserSubscriptions(int $userId, int $limit = 20): array
    {
        return $this->db->fetchAll(
            'SELECT u.id, u.login, u.username, u.avatar, u.avatar_bg_value, u.subscribers_count
             FROM user_folder_subscriptions ufs
             JOIN users u ON ufs.folder_owner_id = u.id
             WHERE ufs.subscriber_id = ?
             ORDER BY ufs.created_at DESC
             LIMIT ?',
            [$userId, $limit]
        );
    }

    /**
     * Подсчитать подписки пользователя
     */
    public function countUserSubscriptions(int $userId): int
    {
        $result = $this->db->fetchOne(
            'SELECT COUNT(*) as cnt FROM user_folder_subscriptions WHERE subscriber_id = ?',
            [$userId]
        );
        return (int)($result['cnt'] ?? 0);
    }
}
