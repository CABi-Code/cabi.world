<?php

use App\Auth\AuthManager;
use App\Repository\UserRepository;

if ($method !== 'POST') json(['error' => 'Method not allowed'], 405);
$authManager = new AuthManager();
$result = $authManager->register(
	$input['login'] ?? '',
	$input['email'] ?? '',
	$input['password'] ?? '',
	$input['username'] ?? ''
);
if ($result['success']) {
	$authManager->setTokenCookies($result['tokens']);
	$userRepo = new UserRepository();
	$newUser = $userRepo->findById($result['user_id']);
	json(['success' => true, 'redirect' => '/profile/@' . $newUser['login']]);
}
json($result, 400);

?>