<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Http\Request;
use App\Http\Response;
use App\Repository\ApplicationRepository;
use App\Service\ImageService;
use App\Validators\ApplicationValidator;

class ApplicationController
{
    private ApplicationRepository $appRepo;
    private ImageService $imageService;
    private ApplicationValidator $validator;

    public function __construct()
    {
        $this->appRepo = new ApplicationRepository();
        $this->imageService = new ImageService();
        $this->validator = new ApplicationValidator();
    }

    public function create(Request $request): void
    {
        $user = $request->user();
        if (!$user) {
            Response::error('Unauthorized', 401);
            return;
        }

        $data = $request->all();
        $modpackId = (int)($data['modpack_id'] ?? 0);

        // Валидация modpack ID
        if ($modpackId <= 0) {
            Response::error('Invalid modpack ID', 400);
            return;
        }

        // Проверка на существующую заявку
        if ($this->appRepo->userHasApplied($modpackId, $user['id'])) {
            Response::error('Вы уже подали заявку на этот модпак', 400);
            return;
        }

        // Валидация данных
        $errors = $this->validator->validateCreate($data);
        $contactsErrors = $this->validateContacts($data, $user);
        $errors = array_merge($errors, $contactsErrors);

        if (!empty($errors)) {
            Response::errors($errors, 400);
            return;
        }

        // Обработка контактов
        [$discord, $telegram, $vk] = $this->processContacts($data, $user);

        // Валидация изображений
        $imagesToUpload = $this->validateImages($request);
        if (is_array($imagesToUpload) && isset($imagesToUpload['error'])) {
            Response::errors(['images' => $imagesToUpload['error']], 400);
            return;
        }

        // Создание заявки
        try {
            $appId = $this->appRepo->create(
                $modpackId,
                $user['id'],
                trim($data['message']),
                $discord,
                $telegram,
                $vk,
                $data['relevant_until'] ?? null
            );

            // Загрузка изображений
            if (!empty($imagesToUpload)) {
                foreach ($imagesToUpload as $i => $file) {
                    $path = $this->imageService->uploadApplicationImage($file, $appId);
                    if ($path) {
                        $this->appRepo->addImage($appId, $path, $i);
                    }
                }
            }

            Response::json(['success' => true, 'id' => $appId]);
        } catch (\InvalidArgumentException $e) {
            Response::errors(['relevant_until' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            Response::error('Ошибка сохранения', 500);
        }
    }

    public function update(Request $request, int $id): void
    {
        $user = $request->user();
        if (!$user) {
            Response::error('Unauthorized', 401);
            return;
        }

        if ($id <= 0) {
            Response::error('Invalid ID', 400);
            return;
        }

        // Проверяем, что заявка принадлежит пользователю
        $existingApp = $this->appRepo->findById($id);
        if (!$existingApp || $existingApp['user_id'] != $user['id']) {
            Response::error('Заявка не найдена', 404);
            return;
        }

        $data = $request->all();

        // Валидация данных
        $errors = $this->validator->validateUpdate($data);
        $contactsErrors = $this->validateContacts($data, $user, true);
        $errors = array_merge($errors, $contactsErrors);

        if (!empty($errors)) {
            Response::errors($errors, 400);
            return;
        }

        // Обработка контактов
        [$discord, $telegram, $vk] = $this->processContacts($data, $user);

        // Валидация новых изображений
        $currentImagesCount = $this->appRepo->countImages($id);
        $imagesToUpload = $this->validateImages($request, $currentImagesCount);
        if (is_array($imagesToUpload) && isset($imagesToUpload['error'])) {
            Response::errors(['images' => $imagesToUpload['error']], 400);
            return;
        }

        // Обновление заявки
        try {
            $updated = $this->appRepo->update(
                $id,
                $user['id'],
                trim($data['message']),
                $discord,
                $telegram,
                $vk,
                $data['relevant_until'] ?? null
            );

            if (!$updated) {
                Response::error('Не удалось обновить заявку', 500);
                return;
            }

            // Загрузка новых изображений
            if (!empty($imagesToUpload)) {
                foreach ($imagesToUpload as $i => $file) {
                    $path = $this->imageService->uploadApplicationImage($file, $id);
                    if ($path) {
                        $this->appRepo->addImage($id, $path, $currentImagesCount + $i);
                    }
                }
            }

            Response::json(['success' => true]);
        } catch (\InvalidArgumentException $e) {
            Response::errors(['relevant_until' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            Response::error('Ошибка сохранения', 500);
        }
    }

    public function delete(Request $request, int $id): void
    {
        $user = $request->user();
        if (!$user) {
            Response::error('Unauthorized', 401);
            return;
        }

        $app = $this->appRepo->findById($id);
        if (!$app || $app['user_id'] != $user['id']) {
            Response::error('Заявка не найдена', 404);
            return;
        }

        $this->appRepo->delete($id, $user['id']);
        Response::json(['success' => true]);
    }

    public function toggleHidden(Request $request, int $id): void
    {
        $user = $request->user();
        if (!$user) {
            Response::error('Unauthorized', 401);
            return;
        }

        $this->appRepo->toggleHidden($id, $user['id']);
        Response::json(['success' => true]);
    }

    public function deleteImage(Request $request, int $imageId): void
    {
        $user = $request->user();
        if (!$user) {
            Response::error('Unauthorized', 401);
            return;
        }

        $deleted = $this->appRepo->deleteImage($imageId, $user['id']);
        if (!$deleted) {
            Response::error('Изображение не найдено', 404);
            return;
        }

        Response::json(['success' => true]);
    }

    private function validateContacts(array $data, array $user, bool $isUpdate = false): array
    {
        $errors = [];
        $contactsMode = $data['contacts_mode'] ?? 'default';

        if (!in_array($contactsMode, ['default', 'custom'])) {
            $errors['contacts_mode'] = 'Неверный режим контактов';
            return $errors;
        }

        if ($contactsMode === 'default') {
            if (empty($user['discord']) && empty($user['telegram']) && empty($user['vk'])) {
                $errors['contacts_mode'] = 'Добавьте контакты в профиле или выберите "На выбор"';
            }
        } else {
            $discord = trim($data['discord'] ?? '');
            $telegram = trim($data['telegram'] ?? '');
            $vk = trim($data['vk'] ?? '');

            if (empty($discord) && empty($telegram) && empty($vk)) {
                $errors['discord'] = 'Укажите хотя бы один способ связи';
            }

            if ($discord && mb_strlen($discord) > 100) {
                $errors['discord'] = 'Discord слишком длинный (максимум 100 символов)';
            }
            if ($telegram && mb_strlen($telegram) > 100) {
                $errors['telegram'] = 'Telegram слишком длинный (максимум 100 символов)';
            }
            if ($vk && mb_strlen($vk) > 100) {
                $errors['vk'] = 'VK слишком длинный (максимум 100 символов)';
            }
        }

        return $errors;
    }

    private function processContacts(array $data, array $user): array
    {
        $contactsMode = $data['contacts_mode'] ?? 'default';

        if ($contactsMode === 'default') {
            return [null, null, null]; // Используются контакты из профиля
        }

        $discord = trim($data['discord'] ?? '');
        $telegram = trim($data['telegram'] ?? '');
        $vk = trim($data['vk'] ?? '');

        return [
            $discord ?: null,
            $telegram ?: null,
            $vk ?: null
        ];
    }

    private function validateImages(Request $request, int $currentCount = 0): array|array
    {
        if (!$request->hasFile('images')) {
            return [];
        }

        $files = $request->files('images');
        $newCount = 0;

        foreach ($files as $file) {
            if ($file['error'] === UPLOAD_ERR_OK) {
                $newCount++;
            }
        }

        // Проверяем общий лимит
        if ($currentCount + $newCount > ApplicationRepository::MAX_IMAGES) {
            $canAdd = ApplicationRepository::MAX_IMAGES - $currentCount;
            return ['error' => 'Можно добавить ещё максимум ' . $canAdd . ' изображений'];
        }

        $validFiles = [];
        foreach ($files as $file) {
            if ($file['error'] !== UPLOAD_ERR_OK) {
                continue;
            }

            // Проверка размера (5 МБ)
            if ($file['size'] > 5 * 1024 * 1024) {
                return ['error' => 'Файл слишком большой (максимум 5 МБ)'];
            }

            // Проверка типа
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!in_array($mimeType, $allowedTypes)) {
                return ['error' => 'Недопустимый тип файла. Разрешены: JPG, PNG, GIF, WebP'];
            }

            $validFiles[] = $file;
        }

        return $validFiles;
    }
}
