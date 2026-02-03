<?php

namespace App\Repository\ApplicationRepository;

use App\Core\DbFields;

trait ForAdminTrait {
	
    public function findAllForAdmin(int $limit = 50, int $offset = 0, ?string $status = null): array
    {
        $sql = 'SELECT ' . DbFields::APP_FULL . '
                FROM modpack_applications a 
                JOIN modpacks m ON a.modpack_id = m.id 
                JOIN users u ON a.user_id = u.id';
        
        $params = [];
        
        if ($status) {
            $sql .= ' WHERE a.status = ?';
            $params[] = $status;
        }
        
        $sql .= ' ORDER BY a.created_at DESC LIMIT ? OFFSET ?';
        $params[] = $limit;
        $params[] = $offset;
        
        return $this->db->fetchAll($sql, $params);
    }

    public function countAllForAdmin(?string $status = null): int
    {
        $sql = 'SELECT COUNT(*) as cnt FROM modpack_applications';
        $params = [];
        
        if ($status) {
            $sql .= ' WHERE status = ?';
            $params[] = $status;
        }
        
        $result = $this->db->fetchOne($sql, $params);
        return (int) ($result['cnt'] ?? 0);
    }
}

?>