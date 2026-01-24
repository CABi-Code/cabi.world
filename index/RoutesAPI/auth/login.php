<?php

use App\Auth\AuthManager;

if ($method !== 'POST') json(['error' => 'Method not allowed'], 405);
$authManager = new AuthManager();
$result = $authManager->login(
	$input['login'] ?? '',
	$input['password'] ?? '',
	$_SERVER['REMOTE_ADDR'] ?? '',
	$_SERVER['HTTP_USER_AGENT'] ?? ''
);
if ($result['success']) {
	$authManager->setTokenCookies($result['tokens']);
	json(['success' => true, 'redirect' => '/profile/@' . $result['user']['login']]);
}
json($result, 401);

?>