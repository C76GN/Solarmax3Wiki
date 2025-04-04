<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\WikiSearchController;
use App\Http\Controllers\WikiPageController;

/**
 * API 路由配置
 * 
 * 本文件定义了系统的API接口路由
 */

/**
 * 用户认证相关API
 * 需要 Sanctum 认证才能访问
 */
Route::middleware('auth:sanctum')->group(function () {
    // 获取当前认证用户信息
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});

/**
 * Wiki内容搜索API
 * 公开接口，无需认证
 */
Route::get('/wiki/search', [WikiSearchController::class, 'search']);

/**
 * Wiki页面协作编辑相关API
 * 使用web中间件以支持会话状态
 */
Route::middleware('web')->prefix('wiki')->group(function () {
    // 获取页面编辑状态
    Route::get('/{page}/status', [WikiPageController::class, 'getPageStatus']);
    
    // 通知开始编辑页面
    Route::post('/{page}/editing', [WikiPageController::class, 'notifyEditing']);
    
    // 通知结束编辑页面
    Route::post('/{page}/stopped-editing', [WikiPageController::class, 'notifyStoppedEditing']);
    
    // 获取页面实时对比
    Route::get('/{page}/compare-live', [WikiPageController::class, 'compareLive']);
});

Route::middleware('auth:sanctum')->post('/upload-image', function (Request $request) {
    if ($request->hasFile('image') && $request->file('image')->isValid()) {
        $path = $request->file('image')->store('uploads/images', 'public');
        return response()->json(['url' => asset('storage/' . $path)]);
    }
    
    return response()->json(['error' => '图片上传失败'], 400);
});