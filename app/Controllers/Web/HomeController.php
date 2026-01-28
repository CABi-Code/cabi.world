<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Http\Request;

class HomeController extends BaseController
{
    public function index(Request $request): void
    {
        $user = $request->user();
        
        $this->render('pages/feed/index', [
            'title' => 'Найди компанию — cabi.world',
            'user' => $user,
        ]);
    }
}
