<?php

use App\Repository\ApplicationRepository;

if (!$user) json(['error' => 'Unauthorized'], 401);
$appId = (int) ($input['id'] ?? 0);
if ($appId <= 0) json(['error' => 'Invalid ID'], 400);
$appRepo = new ApplicationRepository();
$appRepo->deleteAllImages($appId);
$deleted = $appRepo->delete($appId, $user['id']);
if (!$deleted) json(['error' => 'Not found'], 404);
json(['success' => true]);

?>