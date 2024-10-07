<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        // 返回给 Inertia 渲染的首页组件
        return Inertia::render('Solarmax3WikiHome/Home');
    }
}
