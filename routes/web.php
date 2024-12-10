<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\GameWikiController;
use App\Http\Controllers\TextController;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\PageController;


Route::get('/', [HomeController::class, 'index'])->name('home'); //主页路由

Route::middleware(['auth'])->group(function () {
    // 系统日志路由
    Route::get('/activity-logs', [ActivityLogController::class, 'index'])
        ->middleware('permission:log.view')
        ->name('activity-logs.index');
});

Route::middleware(['auth'])->group(function () {
    // 用户管理路由
    Route::middleware('permission:user.view')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        
        Route::middleware('permission:user.edit')->group(function () {
            Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
            Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        });
    });
});

Route::middleware(['auth'])->group(function () {
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

Route::middleware(['auth'])->group(function () {
    // 模板管理路由
    Route::middleware('permission:template.view')->group(function () {
        Route::get('/templates', [TemplateController::class, 'index'])->name('templates.index');
        Route::get('/templates/{template}', [TemplateController::class, 'show'])->name('templates.show');
        Route::get('/templates/export/{template}', [TemplateController::class, 'export'])->name('templates.export');
        
        // 需要额外权限的操作
        Route::middleware('permission:template.create')->group(function () {
            Route::get('/templates/create', [TemplateController::class, 'create'])->name('templates.create');
            Route::post('/templates', [TemplateController::class, 'store'])->name('templates.store');
            Route::post('/templates/import', [TemplateController::class, 'import'])->name('templates.import');
        });
        
        Route::middleware('permission:template.edit')->group(function () {
            Route::get('/templates/{template}/edit', [TemplateController::class, 'edit'])->name('templates.edit');
            Route::put('/templates/{template}', [TemplateController::class, 'update'])->name('templates.update');
        });
        
        Route::delete('/templates/{template}', [TemplateController::class, 'destroy'])
            ->middleware('permission:template.delete')
            ->name('templates.destroy');
    });

    // 页面管理路由
    Route::middleware('permission:page.view')->group(function () {
        Route::get('/pages', [PageController::class, 'index'])->name('pages.index');
        Route::get('/pages/{page}', [PageController::class, 'show'])->name('pages.show');
        
        // 需要额外权限的操作
        Route::middleware('permission:page.create')->group(function () {
            Route::get('/pages/create', [PageController::class, 'create'])->name('pages.create');
            Route::post('/pages', [PageController::class, 'store'])->name('pages.store');
        });
        
        Route::middleware('permission:page.edit')->group(function () {
            Route::get('/pages/{page}/edit', [PageController::class, 'edit'])->name('pages.edit');
            Route::put('/pages/{page}', [PageController::class, 'update'])->name('pages.update');
        });
        
        Route::delete('/pages/{page}', [PageController::class, 'destroy'])
            ->middleware('permission:page.delete')
            ->name('pages.destroy');
            
        Route::middleware('permission:page.publish')->group(function () {
            Route::post('/pages/{page}/publish', [PageController::class, 'publish'])->name('pages.publish');
            Route::post('/pages/{page}/unpublish', [PageController::class, 'unpublish'])->name('pages.unpublish');
        });
    });
});

Route::get('/gamewiki', [GameWikiController::class, 'index'])->name('gamewiki');


Route::get('/text', [TextController::class, 'index']);
Route::get('/text2', [TextController::class, 'index2']);

// Route::get('/', function () {
//     return Inertia::render('Welcome', [
//         'canLogin' => Route::has('login'),
//         'canRegister' => Route::has('register'),
//         'laravelVersion' => Application::VERSION,
//         'phpVersion' => PHP_VERSION,
//     ]);
// });

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';
