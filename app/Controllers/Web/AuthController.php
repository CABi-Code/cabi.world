<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Http\Request;
use App\Http\Response;
use App\Services\AuthService;

class AuthController
{
    private AuthService $authService;

    public function __construct()
    {
        $this->authService = new AuthService();
    }

    public function showLogin(Request $request): void
    {
        $title = 'Вход — cabi.world';
        ob_start();
        require TEMPLATES_PATH . '/pages/auth/login.php';
        $content = ob_get_clean();
        require TEMPLATES_PATH . '/layouts/auth.php';
    }

    public function showRegister(Request $request): void
    {
        $title = 'Регистрация — cabi.world';
        ob_start();
        require TEMPLATES_PATH . '/pages/auth/register.php';
        $content = ob_get_clean();
        require TEMPLATES_PATH . '/layouts/auth.php';
    }

    public function showForgotPassword(Request $request): void
    {
        $title = 'Восстановление пароля — cabi.world';
        ob_start();
        require TEMPLATES_PATH . '/pages/auth/forgot-password.php';
        $content = ob_get_clean();
        require TEMPLATES_PATH . '/layouts/auth.php';
    }

    public function logout(Request $request): void
    {
        $refreshToken = $_COOKIE['refresh_token'] ?? '';
        if ($refreshToken) {
            $this->authService->logout($refreshToken);
        }
        $this->authService->clearTokenCookies();
        Response::redirect('/login');
    }
}
