<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Inertia\Inertia;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    protected function unauthorized($message = '您没有权限执行此操作')
    {
        return Inertia::render('Error/Unauthorized', [
            'message' => $message
        ]);
    }
}