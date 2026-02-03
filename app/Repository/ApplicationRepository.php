<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;
use App\Core\DbFields;

class ApplicationRepository
{
	use \App\Repository\ApplicationRepository\ValidateRelevantUntilTrait;
	use \App\Repository\ApplicationRepository\ImageTrait;
	use \App\Repository\ApplicationRepository\WarpTrait;
	use \App\Repository\ApplicationRepository\PendingTrait;
	use \App\Repository\ApplicationRepository\ByModpackTrait;
	use \App\Repository\ApplicationRepository\ForAdminTrait;
	
    private Database $db;
    public const MAX_MESSAGE_LENGTH = 2000;
    public const MAX_RELEVANCE_DAYS = 31;
    public const MAX_IMAGES = 2;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Все одобренные заявки с сортировкой
     * Контакты подставляются из профиля, если в заявке они null
     */
    public function findAllAccepted(int $limit = 50, int $offset = 0, string $sort = 'relevance'): array
    {
        $orderBy = match($sort) {
            'date' => 'a.created_at DESC',
            'popular' => 'm.accepted_count DESC, a.created_at DESC',
            default => 'CASE 
                WHEN a.relevant_until IS NULL THEN 2
                WHEN a.relevant_until >= CURDATE() THEN 1 
                ELSE 3 
            END ASC,
            CASE 
                WHEN a.relevant_until >= CURDATE() THEN a.relevant_until 
                ELSE NULL 
            END ASC,
            a.created_at DESC'
        };

        return $this->db->fetchAll(
            "SELECT " . DbFields::APP_FULL . ",
                    COALESCE(a.contact_discord, u.discord) as effective_discord,
                    COALESCE(a.contact_telegram, u.telegram) as effective_telegram,
                    COALESCE(a.contact_vk, u.vk) as effective_vk
             FROM modpack_applications a 
             JOIN modpacks m ON a.modpack_id = m.id 
             JOIN users u ON a.user_id = u.id
             WHERE a.status = 'accepted' AND a.is_hidden = 0
             ORDER BY {$orderBy}
             LIMIT ? OFFSET ?",
            [$limit, $offset]
        );
    }

    public function countAllAccepted(): int
    {
        $result = $this->db->fetchOne(
            "SELECT COUNT(*) as cnt FROM modpack_applications WHERE status = 'accepted' AND is_hidden = 0"
        );
        return (int) ($result['cnt'] ?? 0);
    }

    /**
     * Заявки пользователя
     */
    public function findByUser(int $userId, bool $isOwner = false, int $limit = 50, int $offset = 0): array
    {
        $baseSelect = 'SELECT ' . DbFields::APP_WITH_MODPACK . ',
                       COALESCE(a.contact_discord, (SELECT discord FROM users WHERE id = a.user_id)) as effective_discord,
                       COALESCE(a.contact_telegram, (SELECT telegram FROM users WHERE id = a.user_id)) as effective_telegram,
                       COALESCE(a.contact_vk, (SELECT vk FROM users WHERE id = a.user_id)) as effective_vk';
        
        if ($isOwner) {
            return $this->db->fetchAll(
                $baseSelect . '
                 FROM modpack_applications a JOIN modpacks m ON a.modpack_id = m.id 
                 WHERE a.user_id = ? ORDER BY a.created_at DESC LIMIT ? OFFSET ?',
                [$userId, $limit, $offset]
            );
        }
        return $this->db->fetchAll(
            $baseSelect . '
             FROM modpack_applications a JOIN modpacks m ON a.modpack_id = m.id 
             WHERE a.user_id = ? AND a.status = "accepted" AND a.is_hidden = 0
             ORDER BY a.created_at DESC LIMIT ? OFFSET ?',
            [$userId, $limit, $offset]
        );
    }

    public function setStatus(int $id, string $status): bool
    {
        if (!in_array($status, ['pending', 'accepted', 'rejected'])) return false;
        return $this->db->execute(
            'UPDATE modpack_applications SET status = ?, updated_at = NOW() WHERE id = ?', 
            [$status, $id]
        ) > 0;
    }

    public function setStatusByModerator(int $id, string $status): bool
    {
        if (!in_array($status, ['pending', 'accepted', 'rejected'])) return false;
        return $this->db->execute(
            'UPDATE modpack_applications SET status = ?, updated_at = NOW() WHERE id = ?', 
            [$status, $id]
        ) > 0;
    }

}
