<?php

use App\Repository\UserRepository;

if (!$user) json(['error' => 'Unauthorized'], 401);
$userRepo = new UserRepository();
$avatarDir = UPLOADS_PATH . '/avatars/' . $user['id'];
if (is_dir($avatarDir)) {
	array_map('unlink', glob("$avatarDir/*"));
}
$userRepo->update($user['id'], ['avatar' => null]);
json(['success' => true]);

?>