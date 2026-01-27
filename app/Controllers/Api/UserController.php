<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Http\Request;
use App\Http\Response;
use App\Repository\UserRepository;
use App\Service\ImageService;
use App\Validators\UserValidator;

class UserController
{
    private UserRepository $userRepo;
    private ImageService $imageService;
    private UserValidator $validator;

    public function __construct()
    {
        $this->userRepo = new UserRepository();
        $this->imageService = new ImageService();
        $this->validator = new UserValidator();
    }

    public function update(Request $request): void
    {
        $user = $request->user();
        if (!$user) {
            Response::error('Unauthorized', 401);
            return;
        }

        $allowed = ['username', 'bio', 'discord', 'telegram', 'vk', 
                    'banner_bg_value', 'avatar_bg_value', 'banner_bg_type', 'avatar_bg_type'];
        $data = array_intersect_key($request->all(), array_flip($allowed));

        // Валидация
        $errors = $this->validator->validateUpdate($data);
        if (!empty($errors)) {
            Response::errors($errors, 400);
            return;
        }

        $result = $this->userRepo->update($user['id'], $data);

        if (!empty($result['errors'])) {
            foreach ($result['errors'] as $key => $error) {
                Response::error($key . ' error: ' . $error['msg'], $error['code']);
                return;
            }
        }

        Response::json($result);
    }

    public function uploadAvatar(Request $request): void
    {
        $user = $request->user();
        if (!$user) {
            Response::error('Unauthorized', 401);
            return;
        }

        if (!$request->hasFile('avatar')) {
            Response::error('No file uploaded', 400);
            return;
        }

        $file = $request->file('avatar');
        $crop = $request->get('crop');
        $cropData = $crop ? json_decode($crop, true) : null;

        $paths = $this->imageService->uploadAvatar($file, $user['id'], $cropData);

        if (!$paths) {
            Response::error('Failed to upload avatar', 400);
            return;
        }

        $this->userRepo->update($user['id'], ['avatar' => $paths['medium']]);
        Response::json(['success' => true, 'paths' => $paths]);
    }

    public function deleteAvatar(Request $request): void
    {
        $user = $request->user();
        if (!$user) {
            Response::error('Unauthorized', 401);
            return;
        }

        $this->userRepo->update($user['id'], ['avatar' => null]);
        Response::json(['success' => true]);
    }

    public function uploadBanner(Request $request): void
    {
        $user = $request->user();
        if (!$user) {
            Response::error('Unauthorized', 401);
            return;
        }

        if (!$request->hasFile('banner')) {
            Response::error('No file uploaded', 400);
            return;
        }

        $file = $request->file('banner');
        $crop = $request->get('crop');
        $cropData = $crop ? json_decode($crop, true) : null;

        $path = $this->imageService->uploadBanner($file, $user['id'], $cropData);

        if (!$path) {
            Response::error('Failed to upload banner', 400);
            return;
        }

        $this->userRepo->update($user['id'], ['banner' => $path]);
        Response::json(['success' => true, 'path' => $path]);
    }

    public function deleteBanner(Request $request): void
    {
        $user = $request->user();
        if (!$user) {
            Response::error('Unauthorized', 401);
            return;
        }

        $this->userRepo->update($user['id'], ['banner' => null]);
        Response::json(['success' => true]);
    }
}
