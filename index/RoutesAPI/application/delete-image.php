<?php

use App\Repository\ApplicationRepository;

if (!$user) json(['error' => 'Unauthorized'], 401);

$imageId = (int) ($input['image_id'] ?? 0);

if ($imageId <= 0) {
    json(['error' => 'Invalid image ID'], 400);
}

$appRepo = new ApplicationRepository();

// Удаляем изображение (метод проверит владельца)
$deleted = $appRepo->deleteImage($imageId, $user['id']);

if ($deleted) {
    json(['success' => true]);
} else {
    json(['error' => 'Изображение не найдено или нет доступа'], 404);
}
