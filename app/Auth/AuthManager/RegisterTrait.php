<?php

namespace App\Auth\AuthManager;

trait RegisterTrait {

	public function register(string $login, string $email, string $password, string $username): array
	{
		$errors = [];
		
		$content_reserved = file_get_contents(__DIR__ . '/../login-reserved.txt');
		$reserved = explode(', ', $content_reserved);
		
		$reserved = array_merge($reserved, ['admin', 'root', 'system', 'moderator', 'support', 'test']);
		
		if (strlen($login) < 4 || strlen($login) > 16) {
			$errors['login'] = 'Логин должен быть от 4 до 16 символов';
		} elseif (!preg_match('/^[a-zA-Z0-9_-]+$/', $login)) {
			$errors['login'] = 'Логин: только буквы, цифры, - и _';
		} elseif ($this->userRepo->loginExists($login)) {
			$errors['login'] = 'Логин уже занят';
		} elseif (in_array(strtolower($login), $reserved)) {
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
}

?>