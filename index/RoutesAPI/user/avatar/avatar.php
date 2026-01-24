<?php

use App\Repository\UserRepository;
use App\Service\ImageService;

if (!$user) json(['error' => 'Unauthorized'], 401);
if (empty($_FILES['avatar'])) json(['error' => 'No file'], 400);
$imgService = new ImageService();
$paths = $imgService->uploadAvatar($_FILES['avatar'], $user['id']);
if (!$paths) json(['error' => 'Upload failed'], 400);
$userRepo = new UserRepository();
$userRepo->update($user['id'], ['avatar' => $paths['medium']]);
json(['success' => true, 'paths' => $paths]);

?>