<?php

namespace App\Repository\CommunityRepository;

trait BansTrait {

    // =============================================
    // БАНЫ
    // =============================================

    /**
     * Забанить пользователя
     */
    public function banUser(
        int $communityId, 
        int $userId, 
        int $bannedBy, 
        string $scope = 'community', 
        ?int $scopeId = null,
        ?string $reason = null,
        ?string $expiresAt = null
    ): bool {
        try {
            $this->db->execute(
                'INSERT INTO community_bans (community_id, user_id, banned_by, scope, scope_id, reason, expires_at) 
                 VALUES (?, ?, ?, ?, ?, ?, ?)',
                [$communityId, $userId, $bannedBy, $scope, $scopeId, $reason, $expiresAt]
            );
            return true;
        } catch (\PDOException $e) {
            if ($e->getCode() == 23000) return false;
            throw $e;
        }
    }

    /**
     * Разбанить пользователя
     */
    public function unbanUser(int $communityId, int $userId, string $scope = 'community', ?int $scopeId = null): bool
    {
        if ($scopeId === null) {
            return $this->db->execute(
                'DELETE FROM community_bans 
                 WHERE community_id = ? AND user_id = ? AND scope = ? AND scope_id IS NULL',
                [$communityId, $userId, $scope]
            ) > 0;
        }
        return $this->db->execute(
            'DELETE FROM community_bans 
             WHERE community_id = ? AND user_id = ? AND scope = ? AND scope_id = ?',
            [$communityId, $userId, $scope, $scopeId]
        ) > 0;
    }

    /**
     * Проверить, забанен ли пользователь
     */
    public function isBanned(int $communityId, int $userId, ?int $chatId = null): bool
    {
        // Проверяем бан на всё сообщество
        $communityBan = $this->db->fetchOne(
            'SELECT 1 FROM community_bans 
             WHERE community_id = ? AND user_id = ? AND scope = "community"
             AND (expires_at IS NULL OR expires_at > NOW())',
            [$communityId, $userId]
        );
        if ($communityBan) return true;
        
        // Если указан чат, проверяем бан на него
        if ($chatId !== null) {
            return (bool) $this->db->fetchOne(
                'SELECT 1 FROM community_bans 
                 WHERE community_id = ? AND user_id = ? AND scope = "chat" AND scope_id = ?
                 AND (expires_at IS NULL OR expires_at > NOW())',
                [$communityId, $userId, $chatId]
            );
        }
        
        return false;
    }

    /**
     * Получить забаненных пользователей
     */
    public function getBannedUsers(int $communityId, ?string $scope = null, ?int $scopeId = null): array
    {
        $sql = 'SELECT b.*, u.login, u.username, u.avatar, u.avatar_bg_value,
                       bu.username as banned_by_username
                FROM community_bans b
                JOIN users u ON b.user_id = u.id
                JOIN users bu ON b.banned_by = bu.id
                WHERE b.community_id = ?';
        $params = [$communityId];
        
        if ($scope !== null) {
            $sql .= ' AND b.scope = ?';
            $params[] = $scope;
            
            if ($scopeId !== null) {
                $sql .= ' AND b.scope_id = ?';
                $params[] = $scopeId;
            }
        }
        
        $sql .= ' ORDER BY b.created_at DESC';
        
        return $this->db->fetchAll($sql, $params);
    }
}

?>