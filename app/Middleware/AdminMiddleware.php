<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Role;

class AdminMiddleware
{
    private AuthMiddleware $auth;

    public function __construct()
    {
        $this->auth = new AuthMiddleware();
    }

    /**
     * Требует роль модератора или админа
     */
    public function requireModerator(): array
    {
        $user = $this->auth->requireAuth();
        
        if (!Role::isModerator($user['role'] ?? null)) {
            http_response_code(403);
            if ($this->isAjax()) {
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Доступ запрещён']);
                exit;
            }
            redirect('/');
        }
        
        return $user;
    }

    /**
     * Требует роль админа
     */
    public function requireAdmin(): array
    {
        $user = $this->auth->requireAuth();
        
        if (!Role::isAdmin($user['role'] ?? null)) {
            http_response_code(403);
            if ($this->isAjax()) {
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Доступ запрещён']);
                exit;
            }
            redirect('/');
        }
        
        return $user;
    }

    /**
     * Проверяет, является ли запрос AJAX
     */
    private function isAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) 
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}