<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\Request;
use App\Http\Response;
use App\Services\RateLimitService;

class RateLimitBlockMiddleware implements MiddlewareInterface
{
    private RateLimitService $rateLimitService;

    public function __construct()
    {
        $this->rateLimitService = new RateLimitService();
    }

    public function handle(Request $request, callable $next)
    {
        $identifier = $this->getIdentifier($request);
        
        // Пропускаем эндпоинт решения капчи
        if ($request->uri() === '/api/captcha/solve') {
            return $next($request);
        }
        
        // Проверяем блокировку
        $block = $this->rateLimitService->getBlockInfo($identifier);
        
        if ($block) {
            // Пользователь заблокирован — требуется капча
            if ($request->isJson() || str_starts_with($request->uri(), '/api/')) {
                Response::json([
                    'error' => 'Rate limit exceeded',
                    'requires_captcha' => true,
                    'retry_after' => (int)$block['remaining'],
                    'captcha_endpoint' => '/api/captcha/solve'
                ], 429);
                return null;
            }
            
            // Для веб-страниц показываем страницу с капчей
            $this->showCaptchaPage($block);
            return null;
        }
        
        return $next($request);
    }

    private function getIdentifier(Request $request): string
    {
        // Если пользователь авторизован — по user_id, иначе по IP
        $user = $request->user();
        if ($user) {
            return 'user:' . $user['id'];
        }
        return 'ip:' . $request->ip();
    }

    private function showCaptchaPage(array $block): void
    {
        http_response_code(429);
        
        $config = require CONFIG_PATH . '/app.php';
        $siteKey = $config['turnstile']['site_key'] ?? '';
        $retryAfter = (int)$block['remaining'];
        
        $title = 'Проверка безопасности';
        
        require TEMPLATES_PATH . '/pages/captcha-required.php';
        exit;
    }
}