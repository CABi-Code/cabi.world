<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\Request;
use App\Http\Response;
use App\Services\RateLimitService;

class RateLimitMiddleware implements MiddlewareInterface
{
    private RateLimitService $rateLimitService;
    private string $type;

    public function __construct(string $type = 'api')
    {
        $this->rateLimitService = new RateLimitService();
        $this->type = $type;
    }

    public function handle(Request $request, callable $next)
    {
        $identifier = $this->getIdentifier($request);
        $result = $this->rateLimitService->check($identifier, $this->type);
        
        if (!$result['allowed']) {
            $response = [
                'error' => 'Too many requests',
                'retry_after' => $result['retry_after'] ?? 60
            ];
            
            if ($result['requires_captcha'] ?? false) {
                $response['requires_captcha'] = true;
                $response['captcha_endpoint'] = '/api/captcha/solve';
            }
            
            // Добавляем заголовки
            header('Retry-After: ' . ($result['retry_after'] ?? 60));
            header('X-RateLimit-Remaining: 0');
            
            Response::json($response, 429);
            return null;
        }
        
        // Добавляем информацию о лимитах в заголовки
        if (isset($result['remaining'])) {
            header('X-RateLimit-Remaining: ' . $result['remaining']);
        }
        
        return $next($request);
    }

    private function getIdentifier(Request $request): string
    {
        $user = $request->user();
        if ($user) {
            return 'user:' . $user['id'];
        }
        return 'ip:' . $request->ip();
    }
}