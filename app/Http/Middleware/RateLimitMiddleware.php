<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\Request;
use App\Http\Response;
use App\Services\RateLimitService;

class RateLimitMiddleware implements MiddlewareInterface
{
    private RateLimitService $rateLimitService;
    private int $maxAttempts;
    private int $windowSeconds;

    public function __construct(int $maxAttempts = 60, int $windowSeconds = 60)
    {
        $this->rateLimitService = new RateLimitService();
        $this->maxAttempts = $maxAttempts;
        $this->windowSeconds = $windowSeconds;
    }

    public function handle(Request $request, callable $next)
    {
        $key = $this->getKey($request);
        
        if (!$this->rateLimitService->check($key, $this->maxAttempts, $this->windowSeconds)) {
            Response::json(['error' => 'Too many requests'], 429);
            return null;
        }
        
        return $next($request);
    }

    private function getKey(Request $request): string
    {
        $ip = $request->ip();
        $route = $request->uri();
        return "rate_limit:{$ip}:{$route}";
    }
}
