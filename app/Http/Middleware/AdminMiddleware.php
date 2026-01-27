<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\Request;
use App\Http\Response;
use App\Core\Role;

class AdminMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next)
    {
        $user = $request->user();
        
        if (!$user) {
            if ($request->isJson() || str_starts_with($request->uri(), '/api/')) {
                Response::json(['error' => 'Unauthorized'], 401);
                return null;
            }
            Response::redirect('/login');
            return null;
        }
        
        if (!Role::isModerator($user['role'] ?? null)) {
            if ($request->isJson() || str_starts_with($request->uri(), '/api/')) {
                Response::json(['error' => 'Доступ запрещён'], 403);
                return null;
            }
            Response::redirect('/');
            return null;
        }
        
        return $next($request);
    }
}
