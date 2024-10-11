<?php

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
        return [
            ...parent::share($request),
            'auth.user' => fn() => $request->user()
                ? $request->user()->only('id', 'name', 'email') // 只共享 id, name, email
                : null,
        ];
    }
}
