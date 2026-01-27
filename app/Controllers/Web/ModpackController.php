<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Http\Request;
use App\Repository\ModpackRepository;

class ModpackController
{
    private ModpackRepository $modpackRepo;

    public function __construct()
    {
        $this->modpackRepo = new ModpackRepository();
    }

    public function showModrinth(Request $request): void
    {
        $title = 'Модпаки Modrinth — cabi.world';
        ob_start();
        require TEMPLATES_PATH . '/pages/modpacks/index-modrinth.php';
        $content = ob_get_clean();
        require TEMPLATES_PATH . '/layouts/main.php';
    }

    public function showCurseforge(Request $request): void
    {
        $title = 'Модпаки CurseForge — cabi.world';
        ob_start();
        require TEMPLATES_PATH . '/pages/modpacks/index-curseforge.php';
        $content = ob_get_clean();
        require TEMPLATES_PATH . '/layouts/main.php';
    }

    public function show(Request $request, string $platform, string $slug): void
    {
        $modpack = $this->modpackRepo->findBySlug($platform, $slug);
        
        if (!$modpack) {
            http_response_code(404);
            $title = 'Модпак не найден';
            $content = '<div class="alert alert-error">Модпак не найден</div>';
            require TEMPLATES_PATH . '/layouts/main.php';
            return;
        }
        
        $title = ($modpack['name'] ?? $slug) . ' — cabi.world';
        ob_start();
        require TEMPLATES_PATH . '/pages/modpack/index.php';
        $content = ob_get_clean();
        require TEMPLATES_PATH . '/layouts/main.php';
    }
}
