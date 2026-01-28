<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Http\Request;

class SettingsController extends BaseController
{
    public function index(Request $request): void
    {
        $user = $request->user();
        
        $this->render('pages/settings/index', [
            'title' => 'Настройки — cabi.world',
            'user' => $user,
        ]);
    }
}
