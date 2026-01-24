<?php

use App\Repository\UserRepository;
use App\Service\ImageService;

if (!$user) json(['error' => 'Unauthorized'], 401);
if (empty($_FILES['banner'])) json(['error' => 'No file'], 400);
$imgService = new ImageService();
$path = $imgService->uploadBanner($_FILES['banner'], $user['id']);
if (!$path) json(['error' => 'Upload failed'], 400);
$userRepo = new UserRepository();
$userRepo->update($user['id'], ['banner' => $path]);
json(['success' => true, 'path' => $path]);

?>