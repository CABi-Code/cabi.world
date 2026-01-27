<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\Request;
use App\Http\Response;
use App\Services\AuthService;

class GuestMiddleware implements MiddlewareInterface
{
    private AuthService $auth;

    public function __construct()
    {
        $this->auth = new AuthService();
    }

    public function handle(Request $request, callable $next)
    {
        $user = $this->auth->getCurrentUser();
        
        if ($user) {
            Response::redirect('/@' . $user['login']);
            return null;
        }
        
        return $next($request);
    }
}
