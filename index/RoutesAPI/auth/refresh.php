<?php

use App\Auth\AuthManager;

$refreshToken = $_COOKIE['refresh_token'] ?? '';
if (!$refreshToken) json(['error' => 'No token'], 401);
$authManager = new AuthManager();
$result = $authManager->refresh($refreshToken);
if ($result['success']) {
	$authManager->setTokenCookies($result['tokens']);
	json(['success' => true]);
}
json($result, 401);

?>