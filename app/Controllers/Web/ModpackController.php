<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Http\Request;
use App\Repository\ModpackRepository;

class ModpackController extends BaseController
{
    private ModpackRepository $modpackRepo;

    public function __construct()
    {
        $this->modpackRepo = new ModpackRepository();
    }

    public function showModrinth(Request $request): void
    {
        $user = $request->user();
        
        $this->render('pages/modpacks/index-modrinth', [
            'title' => 'Модпаки Modrinth — cabi.world',
            'user' => $user,
            'platform' => 'modrinth',
        ]);
    }

    public function showCurseforge(Request $request): void
    {
        $user = $request->user();
        
        $this->render('pages/modpacks/index-curseforge', [
            'title' => 'Модпаки CurseForge — cabi.world',
            'user' => $user,
            'platform' => 'curseforge',
        ]);
    }

    public function show(Request $request, string $platform, string $slug): void
    {
        $user = $request->user();
        
        // Передаём platform и slug в шаблон - там идёт загрузка из API если нет в БД
        $this->render('pages/modpack/index', [
            'title' => 'Модпак — cabi.world',
            'platform' => $platform,
            'slug' => $slug,
            'user' => $user,
        ]);
    }
}
