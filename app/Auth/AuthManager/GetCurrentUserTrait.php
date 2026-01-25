<?php

namespace App\Auth\AuthManager;

trait GetCurrentUserTrait {

    public function getCurrentUser(): ?array
    {
        $token = $_COOKIE['access_token'] ?? null;
        if (!$token) return null;

        $payload = $this->jwt->decode($token);
        if (!$payload || ($payload['type'] ?? '') !== 'access') return null;

        return $this->userRepo->findById($payload['user_id']);
    }
}

?>