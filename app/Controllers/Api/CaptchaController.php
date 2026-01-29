<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Http\Request;
use App\Http\Response;
use App\Services\TurnstileService;
use App\Services\RateLimitService;

class CaptchaController
{
    private TurnstileService $turnstile;
    private RateLimitService $rateLimit;

    public function __construct()
    {
        $this->turnstile = new TurnstileService();
        $this->rateLimit = new RateLimitService();
    }

    public function solve(Request $request): void
    {
        $token = $request->get('cf-turnstile-response');
        $ip = $request->ip();
        
        // Валидируем капчу
        $result = $this->turnstile->validate($token, $ip);
        
        if (!$result['success']) {
            Response::json([
                'success' => false,
                'error' => $result['error'] ?? 'Проверка не пройдена'
            ], 400);
            return;
        }
        
        // Определяем идентификатор
        $user = $request->user();
        $identifier = $user ? 'user:' . $user['id'] : 'ip:' . $ip;
        
        // Снимаем блокировку
        $this->rateLimit->solveCaptcha($identifier);
        
        Response::json([
            'success' => true,
            'message' => 'Проверка пройдена'
        ]);
    }
}