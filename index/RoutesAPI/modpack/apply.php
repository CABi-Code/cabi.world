<?php

use App\Repository\ApplicationRepository;
use App\Service\ImageService;

if (!$user) json(['error' => 'Unauthorized'], 401);

$appRepo = new ApplicationRepository();

// Поддержка как JSON, так и FormData
$isFormData = strpos($_SERVER['CONTENT_TYPE'] ?? '', 'multipart/form-data') !== false;

if ($isFormData) {
    $modpackId = (int) ($_POST['modpack_id'] ?? 0);
    $message = $_POST['message'] ?? '';
    $relevantUntil = $_POST['relevant_until'] ?? null;
    $contactsMode = $_POST['contacts_mode'] ?? 'default';
    $discord = $_POST['discord'] ?? null;
    $telegram = $_POST['telegram'] ?? null;
    $vk = $_POST['vk'] ?? null;
} else {
    $modpackId = (int) ($input['modpack_id'] ?? 0);
    $message = $input['message'] ?? '';
    $relevantUntil = $input['relevant_until'] ?? null;
    $contactsMode = $input['contacts_mode'] ?? 'default';
    $discord = $input['discord'] ?? null;
    $telegram = $input['telegram'] ?? null;
    $vk = $input['vk'] ?? null;
}

// === ВАЛИДАЦИЯ ===

// Modpack ID
if ($modpackId <= 0) {
    json(['error' => 'Invalid modpack ID'], 400);
}

// Проверка на существующую заявку
if ($appRepo->userHasApplied($modpackId, $user['id'])) {
    json(['error' => 'Вы уже подали заявку на этот модпак'], 400);
}

// Валидация сообщения
$message = trim($message);
if (empty($message)) {
    json(['errors' => ['message' => 'Введите сообщение']], 400);
}

$messageLength = mb_strlen($message);
if ($messageLength > ApplicationRepository::MAX_MESSAGE_LENGTH) {
    json(['errors' => ['message' => 'Сообщение слишком длинное (максимум ' . ApplicationRepository::MAX_MESSAGE_LENGTH . ' символов, у вас ' . $messageLength . ')']], 400);
}

// Валидация даты
$dateValidation = $appRepo->validateRelevantUntil($relevantUntil);
if (!$dateValidation['valid']) {
    json(['errors' => ['relevant_until' => $dateValidation['error']]], 400);
}

// Валидация режима контактов
if (!in_array($contactsMode, ['default', 'custom'])) {
    json(['errors' => ['contacts_mode' => 'Неверный режим контактов']], 400);
}

// Обработка контактов
$finalDiscord = null;
$finalTelegram = null;
$finalVk = null;

if ($contactsMode === 'default') {
    // Проверяем, что у пользователя есть хотя бы один контакт в профиле
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
    
    // Валидация длины контактов
    if ($finalDiscord && mb_strlen($finalDiscord) > 100) {
        json(['errors' => ['discord' => 'Discord слишком длинный (максимум 100 символов)']], 400);
    }
    if ($finalTelegram && mb_strlen($finalTelegram) > 100) {
        json(['errors' => ['telegram' => 'Telegram слишком длинный (максимум 100 символов)']], 400);
    }
    if ($finalVk && mb_strlen($finalVk) > 100) {
        json(['errors' => ['vk' => 'VK слишком длинный (максимум 100 символов)']], 400);
    }
    
    // Если поле пустое - сохраняем null
    $finalDiscord = $finalDiscord ?: null;
    $finalTelegram = $finalTelegram ?: null;
    $finalVk = $finalVk ?: null;
}

// Валидация изображений
$imagesToUpload = [];
if (!empty($_FILES['images']) && is_array($_FILES['images']['name'])) {
    $files = $_FILES['images'];
    $count = count($files['name']);
    
    if ($count > ApplicationRepository::MAX_IMAGES) {
        json(['errors' => ['images' => 'Максимум ' . ApplicationRepository::MAX_IMAGES . ' изображения']], 400);
    }
    
    for ($i = 0; $i < $count; $i++) {
        if ($files['error'][$i] === UPLOAD_ERR_OK) {
            // Проверка размера (5 МБ)
            if ($files['size'][$i] > 5 * 1024 * 1024) {
                json(['errors' => ['images' => 'Файл слишком большой (максимум 5 МБ)']], 400);
            }
            
            // Проверка типа
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $files['tmp_name'][$i]);
            finfo_close($finfo);
            
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!in_array($mimeType, $allowedTypes)) {
                json(['errors' => ['images' => 'Недопустимый тип файла. Разрешены: JPG, PNG, GIF, WebP']], 400);
            }
            
            $imagesToUpload[] = [
                'tmp_name' => $files['tmp_name'][$i],
                'size' => $files['size'][$i],
                'error' => $files['error'][$i]
            ];
        }
    }
}

// === СОЗДАНИЕ ЗАЯВКИ ===
try {
    $appId = $appRepo->create(
        $modpackId,
        $user['id'],
        $message,
        $finalDiscord,
        $finalTelegram,
        $finalVk,
        $relevantUntil
    );
    
    // Загрузка изображений
    if (!empty($imagesToUpload)) {
        $imgService = new ImageService();
        
        foreach ($imagesToUpload as $i => $file) {
            $path = $imgService->uploadApplicationImage($file, $appId);
            if ($path) {
                $appRepo->addImage($appId, $path, $i);
            }
        }
    }
    
    json(['success' => true, 'id' => $appId]);
} catch (\InvalidArgumentException $e) {
    json(['errors' => ['relevant_until' => $e->getMessage()]], 400);
} catch (\Exception $e) {
    json(['error' => 'Ошибка сохранения'], 500);
}
