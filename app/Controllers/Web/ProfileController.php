<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Http\Request;
use App\Repository\UserRepository;

class ProfileController extends BaseController
{
    private UserRepository $userRepo;

    public function __construct()
    {
        $this->userRepo = new UserRepository();
    }

    public function show(Request $request, string $username): void
    {
        $profileUser = $this->userRepo->findByLogin($username);
        
        if (!$profileUser) {
            http_response_code(404);
            $this->render('pages/error', [
                'title' => 'Пользователь не найден',
                'message' => 'Запрашиваемый пользователь не найден',
            ]);
            return;
        }
        
        // Проверка существования файлов
        $uploadPath = ROOT_PATH . '/public';
        $fileUpdates = [];
        
        if ($profileUser['avatar'] && !file_exists($uploadPath . $profileUser['avatar'])) {
            $fileUpdates['avatar'] = null;
            $profileUser['avatar'] = null;
        }
        if ($profileUser['banner'] && !file_exists($uploadPath . $profileUser['banner'])) {
            $fileUpdates['banner'] = null;
            $profileUser['banner'] = null;
        }
        if (!empty($fileUpdates)) {
            $this->userRepo->update($profileUser['id'], $fileUpdates);
        }
        
        $user = $request->user();
        $isOwner = $user && $user['id'] === $profileUser['id'];
        
        $this->render('pages/profile/index', [
            'title' => $profileUser['username'] . ' — cabi.world',
            'profileUser' => $profileUser,
            'user' => $user,
            'isOwner' => $isOwner,
        ]);
    }
}
