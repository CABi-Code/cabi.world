<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Http\Request;

class AdminController
{
    public function index(Request $request): void
    {
        $title = 'Админ-панель — cabi.world';
        ob_start();
        require TEMPLATES_PATH . '/pages/admin/index.php';
        $content = ob_get_clean();
        require TEMPLATES_PATH . '/layouts/main.php';
    }
}
