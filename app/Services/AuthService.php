<?php

declare(strict_types=1);

namespace App\Services;

use App\Auth\JWT;
use App\Repository\UserRepository;
use App\Repository\RefreshTokenRepository;
use App\Core\Database;
use App\Services\TurnstileService;

class AuthService
{
    private JWT $jwt;
    private UserRepository $userRepo;
    private RefreshTokenRepository $tokenRepo;
    private array $config;
	private TurnstileService $turnstile;

	public function __construct()
	{
		$this->config = require CONFIG_PATH . '/app.php';
		$this->jwt = new JWT($this->config['jwt_secret']);
		$this->userRepo = new UserRepository();
		$this->tokenRepo = new RefreshTokenRepository();
		$this->turnstile = new TurnstileService();
	}

	public function register(
		string $login, 
		string $email, 
		string $password, 
		string $username, 
		?string $ip = null, 
		?string $userAgent = null,
		?string $captchaToken = null
	): array {
        $errors = [];
        
		// Проверка капчи
		$captchaResult = $this->turnstile->validate($captchaToken, $ip);
		if (!$captchaResult['success']) {
			$errors['captcha'] = $captchaResult['error'];
		}
		
        $content_reserved = file_get_contents(APP_PATH . '/Auth/login-reserved.txt');
        $reserved = explode(', ', $content_reserved);
        
        $forbidden_keywords = ['cabi', 'admin', 'root', 'system', 'moderator', 'support', 'test'];
        $login = strtolower($login);
        
        if (strlen($login) < 4 || strlen($login) > 16) {
            $errors['login'] = 'Логин должен быть от 4 до 16 символов';
        } elseif (!preg_match('/^[a-zA-Z0-9_-]+$/', $login)) {
            $errors['login'] = 'Логин: только буквы, цифры, - и _';
        } elseif ($this->userRepo->loginExists($login)) {
            $errors['login'] = 'Логин уже занят';
        } elseif (in_array(strtolower($login), $reserved) || array_filter($forbidden_keywords, fn($s) => str_contains(strtolower($login), $s))) {
            $errors['login'] = 'Этот логин зарезервирован';
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Некорректный email';
        } elseif ($this->userRepo->emailExists($email)) {
            $errors['email'] = 'Email уже зарегистрирован';
        }
        
        if (strlen($password) < 8) {
            $errors['password'] = 'Пароль минимум 8 символов';
        }
        
        if (mb_strlen($username) < 2 || mb_strlen($username) > 30) {
            $errors['username'] = 'Имя от 2 до 30 символов';
        } elseif (!preg_match('/^[\p{L}\p{N}\s]+$/u', $username)) {
            $errors['username'] = 'Имя содержит недопустимые спецсимволы';
        }
        
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

		$passwordHash = password_hash($password, PASSWORD_ARGON2ID);
		$userId = $this->userRepo->create($login, $email, $passwordHash, $username);

		if (!$userId) {
			return ['success' => false, 'errors' => ['general' => 'Ошибка создания']];
		}
		
		$tokens = $this->generateTokens($userId, $ip, $userAgent);
		$this->logAuth($userId, 'register', true, $ip, $userAgent);

		return ['success' => true, 'user_id' => $userId, 'tokens' => $tokens];
    }

	public function login(
		string $loginOrEmail, 
		string $password, 
		string $ip, 
		string $userAgent,
		?string $captchaToken = null
	): array {
		
		// Проверка капчи
		$captchaResult = $this->turnstile->validate($captchaToken, $ip);
		if (!$captchaResult['success']) {
			return ['success' => false, 'errors' => ['captcha' => $captchaResult['error']]];
		}

		$user = $this->userRepo->findForAuth($loginOrEmail);

		if (!$user || !password_verify($password, $user['password_hash'])) {
			usleep(random_int(500000, 1000000));
			return ['success' => false, 'errors' => ['general' => 'Неверный логин или пароль']];
		}

		$fullUser = $this->userRepo->findById($user['id']);
		
		if (!$fullUser['is_active']) {
			return ['success' => false, 'errors' => ['general' => 'Аккаунт деактивирован']];
		}

		$tokens = $this->generateTokens($user['id'], $ip, $userAgent);
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
	
	public function refresh(string $refreshToken, ?string $ip = null, ?string $userAgent = null): array
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

		// Проверяем fingerprint (если он был сохранён)
		if ($stored['fingerprint']) {
			$currentFingerprint = $this->getFingerprint($ip, $userAgent);
			if ($stored['fingerprint'] !== $currentFingerprint) {
				// Подозрительная активность - отзываем ВСЕ токены пользователя
				$this->tokenRepo->revokeAllForUser($payload['user_id']);
				$this->logAuth($payload['user_id'], 'suspicious_refresh', false, $ip, $userAgent);
				return ['success' => false, 'error' => 'Session invalidated'];
			}
		}

		$this->tokenRepo->revoke($tokenHash);
		$tokens = $this->generateTokens($payload['user_id'], $ip, $userAgent);

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

		$user = $this->userRepo->findById($payload['user_id']);
		if (!$user || !$user['is_active']) return null;
		
		// Проверка версии токена (если используется)
		if (isset($payload['version']) && isset($user['token_version'])) {
			if ($payload['version'] !== $user['token_version']) return null;
		}

		return $user;
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

	private function generateTokens(int $userId, ?string $ip = null, ?string $userAgent = null): array
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

		$fingerprint = $this->getFingerprint($ip, $userAgent);
		
		$this->tokenRepo->create(
			$userId, 
			hash('sha256', $refreshToken), 
			$this->config['jwt_refresh_lifetime'],
			$fingerprint
		);

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
	
	private function getFingerprint(?string $ip = null, ?string $userAgent = null): string
	{
		$ip = $ip ?? ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
		$userAgent = $userAgent ?? ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown');
		
		// Берём только основную часть User-Agent (браузер + ОС)
		// чтобы минорные обновления браузера не ломали сессию
		$uaShort = preg_replace('/[\d._]+/', '', substr($userAgent, 0, 100));
		
		return hash('sha256', $ip . '|' . $uaShort);
	}
}
