<?php

namespace App\Repository\ApplicationRepository;

use App\Core\DbFields;

trait ByModpackTrait {

    /**
     * Заявки по модпаку с эффективными контактами
     */
    public function findByModpack(int $modpackId, ?int $currentUserId = null, int $limit = 50, int $offset = 0): array
    {
        $baseSelect = 'SELECT ' . DbFields::APP_WITH_USER . ',
                       COALESCE(a.contact_discord, u.discord) as effective_discord,
                       COALESCE(a.contact_telegram, u.telegram) as effective_telegram,
                       COALESCE(a.contact_vk, u.vk) as effective_vk';
        
        if ($currentUserId) {
            return $this->db->fetchAll(
                $baseSelect . '
                 FROM modpack_applications a 
                 JOIN users u ON a.user_id = u.id 
                 WHERE a.modpack_id = ? AND (a.status = "accepted" OR a.user_id = ?)
                 ORDER BY a.created_at DESC LIMIT ? OFFSET ?',
                [$modpackId, $currentUserId, $limit, $offset]
            );
        }
        return $this->db->fetchAll(
            $baseSelect . '
             FROM modpack_applications a 
             JOIN users u ON a.user_id = u.id 
             WHERE a.modpack_id = ? AND a.status = "accepted"
             ORDER BY a.created_at DESC LIMIT ? OFFSET ?',
            [$modpackId, $limit, $offset]
        );
    }
	
    public function countByModpack(int $modpackId, bool $acceptedOnly = true): int
    {
        $sql = 'SELECT COUNT(*) as cnt FROM modpack_applications WHERE modpack_id = ?';
        if ($acceptedOnly) $sql .= ' AND status = "accepted"';
        $result = $this->db->fetchOne($sql, [$modpackId]);
        return (int) ($result['cnt'] ?? 0);
    }

    public function userHasApplied(int $modpackId, int $userId): bool
    {
        return (bool) $this->db->fetchOne(
            'SELECT 1 FROM modpack_applications WHERE modpack_id = ? AND user_id = ?', 
            [$modpackId, $userId]
        );
    }

    public function getUserApplication(int $modpackId, int $userId): ?array
    {
        return $this->db->fetchOne(
            'SELECT ' . DbFields::APP_BASE . ' FROM modpack_applications a WHERE a.modpack_id = ? AND a.user_id = ?', 
            [$modpackId, $userId]
        );
    }
}

?>