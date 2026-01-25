<?php

namespace App\Auth\AuthManager;

trait GenerateTokensTrait {

    private function generateTokens(int $userId): array
    {
        $accessToken = $this->jwt->encode([
            'user_id' => $userId,
            'type' => 'access',
            'exp' => time() + $this->config['jwt_access_lifetime']
        ]);

        $refreshToken = $this->jwt->encode([
            'user_id' => $userId,
            'type' => 'refresh',
            'exp' => time() + $this->config['jwt_refresh_lifetime']
        ]);

        $this->tokenRepo->create($userId, hash('sha256', $refreshToken), $this->config['jwt_refresh_lifetime']);

        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'expires_in' => $this->config['jwt_access_lifetime']
        ];
    }
}

?>