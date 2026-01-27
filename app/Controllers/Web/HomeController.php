<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Http\Request;
use App\Http\Response;

class HomeController
{
    public function index(Request $request): void
    {
        $title = 'Найди компанию — cabi.world';
        ob_start();
        require TEMPLATES_PATH . '/pages/feed/index.php';
        $content = ob_get_clean();
        require TEMPLATES_PATH . '/layouts/main.php';
    }
}
