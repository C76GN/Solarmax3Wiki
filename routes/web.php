<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\GameWikiController;
use App\Http\Controllers\TextController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\WikiArticleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ActivityLogController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', [HomeController::class, 'index'])->name('home');

// Wiki 路由
Route::middleware(['auth'])->prefix('wiki')->name('wiki.')->group(function () {
    Route::get('/', [WikiArticleController::class, 'index'])->name('index');
    Route::get('/create', [WikiArticleController::class, 'create'])
        ->middleware('permission:wiki.create')->name('create');
    Route::post('/', [WikiArticleController::class, 'store'])
        ->middleware('permission:wiki.create')->name('store');
    Route::get('/{article}/edit', [WikiArticleController::class, 'edit'])
        ->middleware('permission:wiki.edit')->name('edit');
    Route::put('/{article}', [WikiArticleController::class, 'update'])
        ->middleware('permission:wiki.edit')->name('update');
    Route::delete('/{article}', [WikiArticleController::class, 'destroy'])
        ->middleware('permission:wiki.delete')->name('destroy');
    Route::post('/{article}/publish', [WikiArticleController::class, 'publish'])
        ->middleware('permission:wiki.publish')->name('publish');
    Route::get('/{article}', [WikiArticleController::class, 'show'])->name('show');
});

Route::middleware(['auth'])->group(function () {
    // 系统日志路由
    Route::get('/activity-logs', [ActivityLogController::class, 'index'])
        ->middleware('permission:log.view')
        ->name('activity-logs.index');

    // 用户管理路由
    Route::middleware('permission:user.view')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        
        Route::middleware('permission:user.edit')->group(function () {
            Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
            Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        });
    });

    // 角色管理路由
    Route::middleware('permission:role.view')->group(function () {
        Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
        
        Route::middleware('permission:role.create')->group(function () {
            Route::get('/roles/create', [RoleController::class, 'create'])->name('roles.create');
            Route::post('/roles', [RoleController::class, 'store'])->name('roles.store');
        });
        
        Route::middleware('permission:role.edit')->group(function () {
            Route::get('/roles/{role}/edit', [RoleController::class, 'edit'])->name('roles.edit');
            Route::put('/roles/{role}', [RoleController::class, 'update'])->name('roles.update');
        });
        
        Route::delete('/roles/{role}', [RoleController::class, 'destroy'])
            ->middleware('permission:role.delete')
            ->name('roles.destroy');
    });


});

Route::get('/gamewiki', [GameWikiController::class, 'index'])->name('gamewiki');
Route::get('/text', [TextController::class, 'index']);
Route::get('/text2', [TextController::class, 'index2']);

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';