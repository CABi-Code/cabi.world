<?php

namespace App\Auth\AuthManager;

use App\Core\Database;

trait LogoutLogoutAllLogAuthTrait {

    public function logout(string $refreshToken): void
    {
        $this->tokenRepo->revoke(hash('sha256', $refreshToken));
    }

    public function logoutAll(int $userId): void
    {
        $this->tokenRepo->revokeAllForUser($userId);
    }

    private function logAuth(int $userId, string $action, bool $success, ?string $ip = null, ?string $ua = null): void
    {
        Database::getInstance()->execute(
            'INSERT INTO auth_logs (user_id, action, success, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)',
            [$userId, $action, $success ? 1 : 0, $ip ?? $_SERVER['REMOTE_ADDR'] ?? '', $ua ?? '']
        );
    }
}

?>