<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Http\Request;
use App\Http\Response;
use App\Services\AuthService;

class AuthController extends BaseController
{
    private AuthService $authService;
    private static bool $authRendered = false;

    public function __construct()
    {
        $this->authService = new AuthService();
    }

    public function showLogin(Request $request): void
    {
        $this->renderAuth('pages/auth/login', [
            'title' => 'Вход — cabi.world',
        ]);
    }

    public function showRegister(Request $request): void
    {
        $this->renderAuth('pages/auth/register', [
            'title' => 'Регистрация — cabi.world',
        ]);
    }

    public function showForgotPassword(Request $request): void
    {
        $this->renderAuth('pages/auth/forgot-password', [
            'title' => 'Восстановление пароля — cabi.world',
        ]);
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

    /**
     * Рендерит страницу с auth layout (только один раз)
     */
    private function renderAuth(string $template, array $data = []): void
    {
        // Защита от повторного рендера
        if (self::$authRendered) {
            return;
        }
        self::$authRendered = true;

        extract($data);
        
        ob_start();
        require TEMPLATES_PATH . '/' . $template . '.php';
        $content = ob_get_clean();
        
        require TEMPLATES_PATH . '/layouts/auth.php';
        
        exit; // Прекращаем выполнение
    }
}