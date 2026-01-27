<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\Request;
use App\Http\Response;
use App\Services\AuthService;

class AuthMiddleware implements MiddlewareInterface
{
    private AuthService $auth;

    public function __construct()
    {
        $this->auth = new AuthService();
    }

    public function handle(Request $request, callable $next)
    {
        $user = $this->getUser($request);
        
        if (!$user) {
            if ($request->isJson() || str_starts_with($request->uri(), '/api/')) {
                Response::json(['error' => 'Unauthorized'], 401);
                return null;
            }
            Response::redirect('/login');
            return null;
        }
        
        $request->setUser($user);
        return $next($request);
    }

    public function getUser(?Request $request = null): ?array
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
            \App\Http\Response::redirect('/login');
        }
        return $user;
    }
}
