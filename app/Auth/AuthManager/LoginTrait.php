<?php

namespace App\Auth\AuthManager;

trait LoginTrait {

    public function login(string $loginOrEmail, string $password, string $ip, string $userAgent): array
    {
        // Используем findForAuth который возвращает password_hash
        $user = $this->userRepo->findForAuth($loginOrEmail);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            usleep(random_int(500000, 1000000));
            return ['success' => false, 'errors' => ['general' => 'Неверный логин или пароль']];
        }

        // Получаем полные данные пользователя
        $fullUser = $this->userRepo->findById($user['id']);
        
        if (!$fullUser['is_active']) {
            return ['success' => false, 'errors' => ['general' => 'Аккаунт деактивирован']];
        }

        $tokens = $this->generateTokens($user['id']);
        $this->userRepo->updateLastLogin($user['id']);
        $this->logAuth($user['id'], 'login', true, $ip, $userAgent);

        return [
            'success' => true,
            'user' => [
                'id' => $fullUser['id'],
                'login' => $fullUser['login'],
                'username' => $fullUser['username'],
                'avatar' => $fullUser['avatar']
            ],
            'tokens' => $tokens
        ];
    }
}

?>