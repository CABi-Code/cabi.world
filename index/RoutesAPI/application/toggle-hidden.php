<?php

use App\Repository\ApplicationRepository;

if (!$user) json(['error' => 'Unauthorized'], 401);
$appId = (int) ($input['id'] ?? 0);
$appRepo = new ApplicationRepository();
$appRepo->toggleHidden($appId, $user['id']);
json(['success' => true]);

?>