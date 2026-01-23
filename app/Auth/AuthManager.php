<?php

declare(strict_types=1);

namespace App\Auth;

use App\Core\Database;
use App\Repository\UserRepository;
use App\Repository\RefreshTokenRepository;

class AuthManager
{
    private JWT $jwt;
    private UserRepository $userRepo;
    private RefreshTokenRepository $tokenRepo;
    private array $config;

    public function __construct()
    {
        $this->config = require CONFIG_PATH . '/app.php';
        $this->jwt = new JWT($this->config['jwt_secret']);
        $this->userRepo = new UserRepository();
        $this->tokenRepo = new RefreshTokenRepository();
    }

    public function register(string $login, string $email, string $password, string $username): array
    {
        $errors = [];
        
        if (strlen($login) < 3 || strlen($login) > 30) {
            $errors['login'] = 'Логин должен быть от 3 до 30 символов';
        } elseif (!preg_match('/^[a-zA-Z0-9_-]+$/', $login)) {
            $errors['login'] = 'Логин: только буквы, цифры, - и _';
        } elseif ($this->userRepo->loginExists($login)) {
            $errors['login'] = 'Логин уже занят';
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Некорректный email';
        } elseif ($this->userRepo->emailExists($email)) {
            $errors['email'] = 'Email уже зарегистрирован';
        }
        
        if (strlen($password) < 8) {
            $errors['password'] = 'Пароль минимум 8 символов';
        }
        
        if (mb_strlen($username) < 2 || mb_strlen($username) > 50) {
            $errors['username'] = 'Имя от 2 до 50 символов';
        }
        
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        $passwordHash = password_hash($password, PASSWORD_ARGON2ID);
        $userId = $this->userRepo->create($login, $email, $passwordHash, $username);

        if (!$userId) {
            return ['success' => false, 'errors' => ['general' => 'Ошибка создания']];
        }

        $tokens = $this->generateTokens($userId);
        $this->logAuth($userId, 'register', true);

        return ['success' => true, 'user_id' => $userId, 'tokens' => $tokens];
    }

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

    public function logout(string $refreshToken): void
    {
        $this->tokenRepo->revoke(hash('sha256', $refreshToken));
    }

    public function logoutAll(int $userId): void
    {
        $this->tokenRepo->revokeAllForUser($userId);
    }

    public function getCurrentUser(): ?array
    {
        $token = $_COOKIE['access_token'] ?? null;
        if (!$token) return null;

        $payload = $this->jwt->decode($token);
        if (!$payload || ($payload['type'] ?? '') !== 'access') return null;

        return $this->userRepo->findById($payload['user_id']);
    }

    public function setTokenCookies(array $tokens): void
    {
        $opts = [
            'path' => '/',
            'secure' => $this->config['session']['secure'],
            'httponly' => $this->config['session']['httponly'],
            'samesite' => $this->config['session']['samesite']
        ];

        setcookie('access_token', $tokens['access_token'], 
            ['expires' => time() + $this->config['jwt_access_lifetime']] + $opts);
        setcookie('refresh_token', $tokens['refresh_token'], 
            ['expires' => time() + $this->config['jwt_refresh_lifetime']] + $opts);
    }

    public function clearTokenCookies(): void
    {
        setcookie('access_token', '', ['expires' => time() - 3600, 'path' => '/']);
        setcookie('refresh_token', '', ['expires' => time() - 3600, 'path' => '/']);
    }

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

    private function logAuth(int $userId, string $action, bool $success, ?string $ip = null, ?string $ua = null): void
    {
        Database::getInstance()->execute(
            'INSERT INTO auth_logs (user_id, action, success, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)',
            [$userId, $action, $success ? 1 : 0, $ip ?? $_SERVER['REMOTE_ADDR'] ?? '', $ua ?? '']
        );
    }
}