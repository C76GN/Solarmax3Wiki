<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

/**
 * 首页控制器
 *
 * 负责处理网站主页相关的请求
 */
class HomeController extends Controller
{
    /**
     * 显示网站首页
     *
     * 渲染Solarmax3Wiki的主页视图
     *
     * @return Response Inertia渲染的响应
     */
    public function index(): Response
    {
        return Inertia::render('Solarmax3Wiki/Home');
    }
}
