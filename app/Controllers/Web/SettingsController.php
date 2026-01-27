<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Http\Request;

class SettingsController
{
    public function index(Request $request): void
    {
        $title = 'Настройки — cabi.world';
        ob_start();
        require TEMPLATES_PATH . '/pages/settings/index.php';
        $content = ob_get_clean();
        require TEMPLATES_PATH . '/layouts/main.php';
    }
}
