<?php

use App\Repository\UserRepository;

if (!$user) json(['error' => 'Unauthorized'], 401);
$userRepo = new UserRepository();
$allowed = ['username', 'bio', 'discord', 'telegram', 'vk', 
			'banner_bg_value', 'avatar_bg_value', 'banner_bg_type', 'avatar_bg_type'];
$data = array_intersect_key($input, array_flip($allowed));
$userRepo->update($user['id'], $data);
json(['success' => true]);

?>