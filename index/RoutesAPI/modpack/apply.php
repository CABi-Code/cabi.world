<?php

use App\Repository\ApplicationRepository;
use App\Repository\UserRepository;
use App\Service\ImageService;

if (!$user) json(['error' => 'Unauthorized'], 401);

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

if ($modpackId <= 0) {
    json(['error' => 'Invalid modpack ID'], 400);
}

$appRepo = new ApplicationRepository();

// Проверка на существующую заявку
if ($appRepo->userHasApplied($modpackId, $user['id'])) {
    json(['error' => 'Вы уже подали заявку на этот модпак'], 400);
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
    // При выводе будут подставляться актуальные данные из профиля
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
    $appId = $appRepo->create(
        $modpackId,
        $user['id'],
        $message,
        $finalDiscord,
        $finalTelegram,
        $finalVk,
        $relevantUntil
    );
    
    // Загрузка изображений (если есть)
    if (!empty($_FILES['images']) && is_array($_FILES['images']['name'])) {
        $imgService = new ImageService();
        $files = $_FILES['images'];
        $count = min(2, count($files['name'])); // Максимум 2 изображения
        
        for ($i = 0; $i < $count; $i++) {
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
                    $appRepo->addImage($appId, $path, $i);
                }
            }
        }
    }
    
    json(['success' => true, 'id' => $appId]);
} catch (\InvalidArgumentException $e) {
    json(['errors' => ['relevant_until' => $e->getMessage()]], 400);
}

?>
