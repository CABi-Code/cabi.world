<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Auth\AuthManager;

class AuthMiddleware
{
    private AuthManager $auth;

    public function __construct()
    {
        $this->auth = new AuthManager();
    }

    public function getUser(): ?array
    {
        $user = $this->auth->getCurrentUser();

        if (!$user) {
            $refreshToken = $_COOKIE['refresh_token'] ?? null;
            if ($refreshToken) {
                $result = $this->auth->refresh($refreshToken);
                if ($result['success']) {
                    $this->auth->setTokenCookies($result['tokens']);
                    $user = $this->auth->getCurrentUser();
                }
            }
        }

        return $user;
    }

    public function requireAuth(): array
    {
        $user = $this->getUser();
        if (!$user) {
            redirect('/login');
        }
        return $user;
    }

    public function requireGuest(): void
    {
        if ($this->getUser()) {
            redirect('/');
        }
    }
}
