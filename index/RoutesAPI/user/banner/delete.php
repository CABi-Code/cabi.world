<?php

use App\Repository\UserRepository;

if (!$user) json(['error' => 'Unauthorized'], 401);
$userRepo = new UserRepository();
$bannerDir = UPLOADS_PATH . '/banners/' . $user['id'];
if (is_dir($bannerDir)) {
	array_map('unlink', glob("$bannerDir/*"));
}
$userRepo->update($user['id'], ['banner' => null]);
json(['success' => true]);

?>