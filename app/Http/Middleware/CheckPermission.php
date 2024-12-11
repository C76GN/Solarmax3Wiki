<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CheckPermission
{
    public function handle(Request $request, Closure $next, $permission)
    {
        if (!$request->user() || !$request->user()->hasPermission($permission)) {
            if ($request->wantsJson()) {
                return response()->json([
                    'message' => '权限不足',
                ], 403);
            }
            
            return Inertia::render('Error/Unauthorized', [
                'message' => '您没有权限执行此操作'
            ])->toResponse($request)->setStatusCode(403);
        }

        return $next($request);
    }
}