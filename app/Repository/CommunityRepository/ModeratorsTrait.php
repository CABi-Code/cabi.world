<?php

namespace App\Repository\CommunityRepository;

trait ModeratorsTrait {

    // =============================================
    // МОДЕРАТОРЫ
    // =============================================

    /**
     * Назначить модератора
     */
    public function addModerator(
        int $communityId, 
        int $userId, 
        string $scope = 'community', 
        ?int $scopeId = null
    ): bool {
        // Проверяем, что пользователь подписан
        if (!$this->isSubscribed($communityId, $userId)) {
            return false;
        }
        
        try {
            $this->db->execute(
                'INSERT INTO community_moderators (community_id, user_id, scope, scope_id) 
                 VALUES (?, ?, ?, ?)',
                [$communityId, $userId, $scope, $scopeId]
            );
            return true;
        } catch (\PDOException $e) {
            if ($e->getCode() == 23000) return false;
            throw $e;
        }
    }

    /**
     * Удалить модератора
     */
    public function removeModerator(int $communityId, int $userId, string $scope = 'community', ?int $scopeId = null): bool
    {
        if ($scopeId === null) {
            return $this->db->execute(
                'DELETE FROM community_moderators 
                 WHERE community_id = ? AND user_id = ? AND scope = ? AND scope_id IS NULL',
                [$communityId, $userId, $scope]
            ) > 0;
        }
        return $this->db->execute(
            'DELETE FROM community_moderators 
             WHERE community_id = ? AND user_id = ? AND scope = ? AND scope_id = ?',
            [$communityId, $userId, $scope, $scopeId]
        ) > 0;
    }

    /**
     * Проверить, является ли пользователь модератором
     */
    public function isModerator(int $communityId, int $userId, ?string $scope = null, ?int $scopeId = null): bool
    {
        // Модератор всего сообщества имеет права везде
        $communityMod = $this->db->fetchOne(
            'SELECT 1 FROM community_moderators 
             WHERE community_id = ? AND user_id = ? AND scope = "community"',
            [$communityId, $userId]
        );
        if ($communityMod) return true;
        
        if ($scope === null) return false;
        
        // Проверяем конкретный scope
        if ($scopeId === null) {
            return (bool) $this->db->fetchOne(
                'SELECT 1 FROM community_moderators 
                 WHERE community_id = ? AND user_id = ? AND scope = ? AND scope_id IS NULL',
                [$communityId, $userId, $scope]
            );
        }
        
        return (bool) $this->db->fetchOne(
            'SELECT 1 FROM community_moderators 
             WHERE community_id = ? AND user_id = ? AND scope = ? AND scope_id = ?',
            [$communityId, $userId, $scope, $scopeId]
        );
    }

    /**
     * Получить модераторов
     */
    public function getModerators(int $communityId, ?string $scope = null, ?int $scopeId = null): array
    {
        $sql = 'SELECT m.*, u.login, u.username, u.avatar, u.avatar_bg_value, u.role
                FROM community_moderators m
                JOIN users u ON m.user_id = u.id
                WHERE m.community_id = ?';
        $params = [$communityId];
        
        if ($scope !== null) {
            $sql .= ' AND m.scope = ?';
            $params[] = $scope;
            
            if ($scopeId !== null) {
                $sql .= ' AND m.scope_id = ?';
                $params[] = $scopeId;
            }
        }
        
        $sql .= ' ORDER BY m.created_at';
        
        return $this->db->fetchAll($sql, $params);
    }
}

?>