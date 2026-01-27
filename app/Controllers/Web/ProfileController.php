<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Http\Request;
use App\Http\Response;
use App\Repository\UserRepository;

class ProfileController
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
            $title = 'Пользователь не найден';
            $content = '<div class="alert alert-error">Пользователь не найден</div>';
            require TEMPLATES_PATH . '/layouts/main.php';
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
        $title = $profileUser['username'] . ' — cabi.world';
        
        ob_start();
        require TEMPLATES_PATH . '/pages/profile/index.php';
        $content = ob_get_clean();
        require TEMPLATES_PATH . '/layouts/main.php';
    }
}
