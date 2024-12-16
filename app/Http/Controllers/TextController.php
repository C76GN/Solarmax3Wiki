<?php
// FileName: /var/www/Solarmax3Wiki/app/Http/Controllers/TextController.php


namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;

class TextController extends Controller
{
    public function index() 
    {
        // 返回给 Inertia 渲染的首页组件
        return Inertia::render('Test/text'); 
    }
    public function index2()
    {
        // 返回给 Inertia 渲染的首页组件
        return Inertia::render('Test/text2');
    }
}
