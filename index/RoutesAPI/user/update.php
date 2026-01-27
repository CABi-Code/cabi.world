<?php

use App\Repository\UserRepository;

if (!$user) json(['error' => 'Unauthorized'], 401);
$userRepo = new UserRepository();
$allowed = ['username', 'bio', 'discord', 'telegram', 'vk', 
			'banner_bg_value', 'avatar_bg_value', 'banner_bg_type', 'avatar_bg_type'];
$data = array_intersect_key($input, array_flip($allowed));


$result = $userRepo->update($user['id'], $data);

if (!empty($result['errors'])) {
	foreach ($result['errors'] as $key => $error) {	
		json(['error' => $key . ' error: ' . $error['msg']], $error['code']);
	}
}

json($result);

?>