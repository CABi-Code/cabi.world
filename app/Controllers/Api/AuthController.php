<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Http\Request;
use App\Http\Response;
use App\Services\AuthService;
use App\Repository\UserRepository;

class AuthController
{
    private AuthService $authService;
    private UserRepository $userRepo;

    public function __construct()
    {
        $this->authService = new AuthService();
        $this->userRepo = new UserRepository();
    }

	public function login(Request $request): void
	{
		if (!$request->isMethod('POST')) {
			Response::error('Method not allowed', 405);
			return;
		}

		$result = $this->authService->login(
			$request->get('login', ''),
			$request->get('password', ''),
			$request->ip(),
			$request->userAgent(),
			$request->get('cf-turnstile-response')
		);

		if ($result['success']) {
			$this->authService->setTokenCookies($result['tokens']);
			Response::json([
				'success' => true,
				'redirect' => '/@' . $result['user']['login']
			]);
		} else {
			Response::json($result, 401);
		}
	}

	public function register(Request $request): void
	{
		if (!$request->isMethod('POST')) {
			Response::error('Method not allowed', 405);
			return;
		}

		$result = $this->authService->register(
			$request->get('login', ''),
			$request->get('email', ''),
			$request->get('password', ''),
			$request->get('username', ''),
			$request->ip(),
			$request->userAgent(),
			$request->get('cf-turnstile-response')
		);

		if ($result['success']) {
			$this->authService->setTokenCookies($result['tokens']);
			$newUser = $this->userRepo->findById($result['user_id']);
			Response::json([
				'success' => true,
				'redirect' => '/@' . $newUser['login']
			]);
		} else {
			Response::json($result, 400);
		}
	}

	public function refresh(Request $request): void
	{
		if (!$request->isMethod('POST')) {
			Response::error('Method not allowed', 405);
			return;
		}

		$refreshToken = $_COOKIE['refresh_token'] ?? $request->get('refresh_token', '');
		
		if (empty($refreshToken)) {
			Response::error('Refresh token required', 400);
			return;
		}

		// Передаём ip и userAgent для проверки fingerprint
		$result = $this->authService->refresh(
			$refreshToken,
			$request->ip(),
			$request->userAgent()
		);

		if ($result['success']) {
			$this->authService->setTokenCookies($result['tokens']);
			Response::json(['success' => true, 'tokens' => $result['tokens']]);
		} else {
			$this->authService->clearTokenCookies();
			Response::json($result, 401);
		}
	}
}
