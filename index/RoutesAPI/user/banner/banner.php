<?php

use App\Repository\UserRepository;
use App\Service\ImageService;

if (!$user) json(['error' => 'Unauthorized'], 401);
if (empty($_FILES['banner'])) json(['error' => 'No file'], 400);

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
$path = $imgService->uploadBanner($_FILES['banner'], $user['id'], $crop);
if (!$path) json(['error' => 'Upload failed'], 400);

$userRepo = new UserRepository();
$userRepo->update($user['id'], ['banner' => $path]);
json(['success' => true, 'path' => $path]);

?>
