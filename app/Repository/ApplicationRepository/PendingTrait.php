<?php

namespace App\Repository\ApplicationRepository;

use App\Core\DbFields;

trait PendingTrait {

    public function findPending(int $limit = 50, int $offset = 0): array
    {
        return $this->db->fetchAll(
            'SELECT ' . DbFields::APP_FULL . '
             FROM modpack_applications a 
             JOIN modpacks m ON a.modpack_id = m.id 
             JOIN users u ON a.user_id = u.id
             WHERE a.status = "pending"
             ORDER BY a.created_at ASC
             LIMIT ? OFFSET ?',
            [$limit, $offset]
        );
    }

    public function countPending(): int
    {
        $result = $this->db->fetchOne(
            "SELECT COUNT(*) as cnt FROM modpack_applications WHERE status = 'pending'"
        );
        return (int) ($result['cnt'] ?? 0);
    }
}

?>