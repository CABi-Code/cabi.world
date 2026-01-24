<?php

use App\Repository\UserRepository;
use App\Service\ImageService;

if (!$user) json(['error' => 'Unauthorized'], 401);
if (empty($_FILES['avatar'])) json(['error' => 'No file'], 400);

// Parse crop data
$crop = null;
if (isset($_POST['crop_x'], $_POST['crop_y'], $_POST['crop_width'], $_POST['crop_height'])) {
    $crop = [
        'x' => (int) $_POST['crop_x'],
        'y' => (int) $_POST['crop_y'],
        'width' => (int) $_POST['crop_width'],
        'height' => (int) $_POST['crop_height']
    ];
}

$imgService = new ImageService();
$paths = $imgService->uploadAvatar($_FILES['avatar'], $user['id'], $crop);
if (!$paths) json(['error' => 'Upload failed'], 400);

$userRepo = new UserRepository();
$userRepo->update($user['id'], ['avatar' => $paths['medium']]);
json(['success' => true, 'paths' => $paths]);

?>
