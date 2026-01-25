<?php

namespace App\Auth\AuthManager;

trait RefreshTrait {

    public function refresh(string $refreshToken): array
    {
        $payload = $this->jwt->decode($refreshToken);
        if (!$payload || ($payload['type'] ?? '') !== 'refresh') {
            return ['success' => false, 'error' => 'Invalid token'];
        }

        $tokenHash = hash('sha256', $refreshToken);
        $stored = $this->tokenRepo->findByHash($tokenHash);
        
        if (!$stored || $stored['revoked']) {
            return ['success' => false, 'error' => 'Token revoked'];
        }

        $this->tokenRepo->revoke($tokenHash);
        $tokens = $this->generateTokens($payload['user_id']);

        return ['success' => true, 'tokens' => $tokens];
    }
}

?>