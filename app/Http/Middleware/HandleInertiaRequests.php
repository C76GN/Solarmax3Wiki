<?php
// FileName: /var/www/Solarmax3Wiki/app/Http/Middleware/HandleInertiaRequests.php


namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'csrf' => csrf_token(),
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    'permissions' => $request->user()->getAllPermissionsAttribute(),
                    'roles' => $request->user()->roles->pluck('name'),
                ] : null,
            ],
        ]);
    }
}