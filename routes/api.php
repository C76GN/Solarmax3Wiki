<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\WikiSearchController;
use App\Http\Controllers\WikiPageController;

// 使用 Sanctum 令牌认证
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/wiki/search', [WikiSearchController::class, 'search']);

// 使用 web 中间件组进行会话认证，而不是 API 令牌认证
Route::middleware('web')->group(function () {
    Route::prefix('wiki')->group(function () {
        Route::get('/{page}/status', [WikiPageController::class, 'getPageStatus']);
        Route::post('/{page}/editing', [WikiPageController::class, 'notifyEditing']);
        Route::post('/{page}/stopped-editing', [WikiPageController::class, 'notifyStoppedEditing']);
        Route::get('/{page}/compare-live', [WikiPageController::class, 'compareLive']);
    });
});