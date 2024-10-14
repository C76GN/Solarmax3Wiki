<?php

namespace App\Http\Controllers; // 定义命名空间

use Inertia\Inertia; // 导入 Inertia 类，用于处理 Inertia.js 的渲染
use Illuminate\Http\Request; // 导入 Request 类，用于处理 HTTP 请求

class HomeController extends Controller // 定义 HomeController 类，继承自基类 Controller
{
    public function index() // 定义 index 方法，处理首页请求
    {
        // 返回给 Inertia 渲染的首页组件
        return Inertia::render('Solarmax3Wiki/Home'); // 使用 Inertia 渲染指定的 Vue 组件
    }
}
