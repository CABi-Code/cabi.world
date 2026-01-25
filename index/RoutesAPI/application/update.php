<?php

use App\Repository\ApplicationRepository;
use App\Service\ImageService;

if (!$user) json(['error' => 'Unauthorized'], 401);

// Поддержка как JSON, так и FormData
$isFormData = strpos($_SERVER['CONTENT_TYPE'] ?? '', 'multipart/form-data') !== false;

if ($isFormData) {
    $appId = (int) ($_POST['id'] ?? 0);
    $message = $_POST['message'] ?? '';
    $relevantUntil = $_POST['relevant_until'] ?? null;
    $contactsMode = $_POST['contacts_mode'] ?? 'default';
    $discord = $_POST['discord'] ?? null;
    $telegram = $_POST['telegram'] ?? null;
    $vk = $_POST['vk'] ?? null;
} else {
    $appId = (int) ($input['id'] ?? 0);
    $message = $input['message'] ?? '';
    $relevantUntil = $input['relevant_until'] ?? null;
    $contactsMode = $input['contacts_mode'] ?? 'default';
    $discord = $input['discord'] ?? null;
    $telegram = $input['telegram'] ?? null;
    $vk = $input['vk'] ?? null;
}

if ($appId <= 0) {
    json(['error' => 'Invalid ID'], 400);
}

$appRepo = new ApplicationRepository();

// Проверяем, что заявка принадлежит пользователю
$existingApp = $appRepo->findById($appId);
if (!$existingApp || $existingApp['user_id'] != $user['id']) {
    json(['error' => 'Заявка не найдена'], 404);
}

// Валидация даты
$dateValidation = $appRepo->validateRelevantUntil($relevantUntil);
if (!$dateValidation['valid']) {
    json(['errors' => ['relevant_until' => $dateValidation['error']]], 400);
}

// Валидация сообщения
$message = trim($message);
if (empty($message)) {
    json(['errors' => ['message' => 'Введите сообщение']], 400);
}

if (mb_strlen($message) > 2000) {
    json(['errors' => ['message' => 'Сообщение слишком длинное (максимум 2000 символов)']], 400);
}

// Обработка контактов
$finalDiscord = null;
$finalTelegram = null;
$finalVk = null;

if ($contactsMode === 'default') {
    // Используем контакты из профиля (null - означает "по умолчанию")
    $finalDiscord = null;
    $finalTelegram = null;
    $finalVk = null;
    
    // Проверяем, что у пользователя есть хотя бы один контакт
    if (empty($user['discord']) && empty($user['telegram']) && empty($user['vk'])) {
        json(['errors' => ['contacts_mode' => 'Добавьте контакты в профиле или выберите "На выбор"']], 400);
    }
} else {
    // Используем указанные контакты
    $finalDiscord = trim($discord ?? '');
    $finalTelegram = trim($telegram ?? '');
    $finalVk = trim($vk ?? '');
    
    // Валидация - должен быть хотя бы один контакт
    if (empty($finalDiscord) && empty($finalTelegram) && empty($finalVk)) {
        json(['errors' => ['discord' => 'Укажите хотя бы один способ связи']], 400);
    }
    
    // Если поле пустое - сохраняем null, а не пустую строку
    $finalDiscord = $finalDiscord ?: null;
    $finalTelegram = $finalTelegram ?: null;
    $finalVk = $finalVk ?: null;
}

try {
    $updated = $appRepo->update(
        $appId,
        $user['id'],
        $message,
        $finalDiscord,
        $finalTelegram,
        $finalVk,
        $relevantUntil
    );
    
    if (!$updated) {
        json(['error' => 'Не удалось обновить заявку'], 500);
    }
    
    // Обработка новых изображений (если есть)
    if (!empty($_FILES['images']) && is_array($_FILES['images']['name'])) {
        $imgService = new ImageService();
        
        // Получаем текущие изображения
        $currentImages = $appRepo->getImages($appId);
        $currentCount = count($currentImages);
        
        $files = $_FILES['images'];
        $newCount = 0;
        
        // Считаем сколько новых файлов можно добавить
        foreach ($files['error'] as $error) {
            if ($error === UPLOAD_ERR_OK) $newCount++;
        }
        
        // Максимум 2 изображения всего
        $canAdd = max(0, 2 - $currentCount);
        
        if ($canAdd > 0) {
            $added = 0;
            for ($i = 0; $i < count($files['name']) && $added < $canAdd; $i++) {
                if ($files['error'][$i] === UPLOAD_ERR_OK) {
                    // Проверка размера (5 МБ)
                    if ($files['size'][$i] > 5 * 1024 * 1024) {
                        continue;
                    }
                    
                    $file = [
                        'tmp_name' => $files['tmp_name'][$i],
                        'size' => $files['size'][$i],
                        'error' => $files['error'][$i]
                    ];
                    
                    $path = $imgService->uploadApplicationImage($file, $appId);
                    if ($path) {
                        $appRepo->addImage($appId, $path, $currentCount + $added);
                        $added++;
                    }
                }
            }
        }
    }
    
    json(['success' => true]);
} catch (\InvalidArgumentException $e) {
    json(['errors' => ['relevant_until' => $e->getMessage()]], 400);
}

?>
