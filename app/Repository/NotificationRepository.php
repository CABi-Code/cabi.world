<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;
use App\Core\DbFields;

class NotificationRepository
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function create(int $userId, string $type, string $title, ?string $message = null, ?string $link = null): int
    {
        $this->db->execute(
            'INSERT INTO notifications (user_id, type, title, message, link) VALUES (?, ?, ?, ?, ?)',
            [$userId, $type, $title, $message, $link]
        );
        return $this->db->lastInsertId();
    }

    public function findByUser(int $userId, int $limit = 20): array
    {
        return $this->db->fetchAll(
            'SELECT ' . DbFields::NOTIF_BASE . ' FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?',
            [$userId, $limit]
        );
    }

    public function countUnread(int $userId): int
    {
        $result = $this->db->fetchOne(
            'SELECT COUNT(*) as cnt FROM notifications WHERE user_id = ? AND is_read = 0',
            [$userId]
        );
        return (int) ($result['cnt'] ?? 0);
    }

    public function markAsRead(int $id, int $userId): bool
    {
        return $this->db->execute(
            'UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?',
            [$id, $userId]
        ) > 0;
    }

    public function markAllAsRead(int $userId): void
    {
        $this->db->execute('UPDATE notifications SET is_read = 1 WHERE user_id = ?', [$userId]);
    }

    public function delete(int $id, int $userId): bool
    {
        return $this->db->execute('DELETE FROM notifications WHERE id = ? AND user_id = ?', [$id, $userId]) > 0;
    }

    /**
     * Уведомление о принятии заявки
     */
    public function notifyApplicationAccepted(int $userId, string $modpackName, string $modpackSlug, string $platform): int
    {
        return $this->create(
            $userId,
            'application_accepted',
            'Заявка одобрена',
            "Ваша заявка на \"$modpackName\" одобрена!",
            "/modpack/$platform/$modpackSlug"
        );
    }

    /**
     * Уведомление об отклонении заявки
     */
    public function notifyApplicationRejected(int $userId, string $modpackName): int
    {
        return $this->create(
            $userId,
            'application_rejected',
            'Заявка отклонена',
            "Ваша заявка на \"$modpackName\" отклонена."
        );
    }
}
