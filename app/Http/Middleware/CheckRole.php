<?php
// FileName: /var/www/Solarmax3Wiki/app/Http/Middleware/CheckRole.php


namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CheckRole
{
    public function handle(Request $request, Closure $next, $role)
    {
        if (!$request->user() || !$request->user()->hasRole($role)) {
            if ($request->wantsJson()) {
                return response()->json([
                    'message' => '权限不足',
                ], 403);
            }
            
            return Inertia::render('Error/Unauthorized', [
                'message' => '您没有所需的角色权限'
            ])->toResponse($request)->setStatusCode(403);
        }

        return $next($request);
    }
}