<?php

/**
 * Web Routes
 *
 * 这里定义了应用的所有web路由，包括主页、Wiki、用户、角色等管理功能
 * 路由按照功能模块进行分组，并使用适当的中间件保护
 */

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\WikiPageController;
use App\Http\Controllers\WikiCategoryController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\WikiPageRevisionController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| 公共路由
|--------------------------------------------------------------------------
|
| 这些路由对所有用户开放，包括未登录的访客
|
*/

// 首页路由
Route::get('/', [HomeController::class, 'index'])->name('home');

// Wiki公共页面路由
Route::prefix('wiki')->group(function () {
    Route::get('', [WikiPageController::class, 'index'])->name('wiki.index');
    Route::get('{page}', [WikiPageController::class, 'show'])
        ->name('wiki.show')
        ->where('page', '[0-9]+'); // 确保page参数是数字
});

/*
|--------------------------------------------------------------------------
| Wiki功能路由组
|--------------------------------------------------------------------------
|
| 这些路由用于Wiki的创建、编辑、管理等功能，需要用户登录并有相应权限
|
*/
Route::middleware(['auth'])->prefix('wiki')->name('wiki.')->group(function () {
    // Wiki页面创建相关路由
    Route::middleware('permission:wiki.create')->group(function () {
        Route::get('create', [WikiPageController::class, 'create'])->name('create');
        Route::post('', [WikiPageController::class, 'store'])->name('store');
    });
    
    Route::post('preview', [WikiPageController::class, 'preview'])->name('preview');
    Route::get('{page}/edit-conflict', [WikiPageController::class, 'showEditConflict'])->name('edit-conflict');
    Route::get('{page}/revisions/diff/{fromVersion}/{toVersion}', [WikiPageRevisionController::class, 'diffView'])
        ->name('revisions-diff');

    // Wiki页面编辑相关路由
    Route::middleware('permission:wiki.edit')->group(function () {
        Route::get('{page}/edit', [WikiPageController::class, 'edit'])->name('edit');
        Route::put('{page}', [WikiPageController::class, 'update'])->name('update');
        Route::post('{page}/revert/{version}', [WikiPageRevisionController::class, 'revert'])
            ->name('revert-version');
    });
    
    // Wiki页面删除路由
    Route::middleware('permission:wiki.delete')
        ->delete('{page}', [WikiPageController::class, 'destroy'])
        ->name('destroy');
    
    // Wiki页面发布路由
    Route::middleware('permission:wiki.publish')
        ->post('{page}/publish', [WikiPageController::class, 'publish'])
        ->name('publish');
    
    // Wiki页面关注与问题管理
    Route::post('{page}/follow', [WikiPageController::class, 'toggleFollow'])->name('follow');
    Route::post('issue', [WikiPageController::class, 'issue'])->name('issue');
    Route::post('issue_handle', [WikiPageController::class, 'issue_handle'])->name('issue_handle');
    Route::post('audit', [WikiPageController::class, 'audit'])->name('audit');
    
    // Wiki页面版本和比较相关路由
    Route::prefix('pages')->group(function () {
        Route::get('{page}/revisions', [WikiPageRevisionController::class, 'index'])->name('revisions');
        Route::get('{page}/revisions/{version}', [WikiPageRevisionController::class, 'show'])
            ->name('show-revision');
        Route::get('{page}/compare/{fromVersion}/{toVersion}', [WikiPageRevisionController::class, 'compare'])
            ->name('compare-revisions');
        Route::get('{page}/compare-live', [WikiPageRevisionController::class, 'compareLive'])
            ->name('compare-live');
    });
    
    // Wiki回收站管理路由
    Route::prefix('trash')->middleware('permission:wiki.manage_trash')->group(function () {
        Route::get('', [WikiPageController::class, 'trash'])->name('trash');
        Route::post('{id}/restore', [WikiPageController::class, 'restore'])->name('restore');
        Route::post('restore-selected', [WikiPageController::class, 'restoreSelected'])
            ->name('restore-selected');
        Route::delete('{id}', [WikiPageController::class, 'forceDelete'])->name('force-delete');
        Route::delete('force-delete-selected', [WikiPageController::class, 'forceDeleteSelected'])
            ->name('force-delete-selected');
    });
    
    // Wiki分类管理路由
    Route::prefix('categories')->name('categories.')->group(function () {
        Route::get('', [WikiCategoryController::class, 'index'])->name('index');
        
        // 分类创建路由
        Route::middleware('permission:wiki.category.create')->group(function () {
            Route::get('create', [WikiCategoryController::class, 'create'])->name('create');
            Route::post('', [WikiCategoryController::class, 'store'])->name('store');
        });
        
        // 分类编辑路由
        Route::middleware('permission:wiki.category.edit')->group(function () {
            Route::get('{category}/edit', [WikiCategoryController::class, 'edit'])->name('edit');
            Route::put('{category}', [WikiCategoryController::class, 'update'])->name('update');
        });
        
        // 分类删除路由
        Route::middleware('permission:wiki.category.delete')
            ->delete('{category}', [WikiCategoryController::class, 'destroy'])
            ->name('destroy');
    });
});

/*
|--------------------------------------------------------------------------
| 管理功能路由组
|--------------------------------------------------------------------------
|
| 这些路由用于系统管理功能，如日志查看、用户管理、角色管理等
| 所有路由都需要用户登录并具有相应权限
|
*/
Route::middleware(['auth'])->group(function () {
    // 活动日志查看
    Route::middleware('permission:log.view')
        ->get('activity-logs', [ActivityLogController::class, 'index'])
        ->name('activity-logs.index');
    
    // 用户管理路由
    Route::prefix('users')->name('users.')->middleware('permission:user.view')->group(function () {
        Route::get('', [UserController::class, 'index'])->name('index');
        
        // 用户编辑路由
        Route::middleware('permission:user.edit')->group(function () {
            Route::get('{user}/edit', [UserController::class, 'edit'])->name('edit');
            Route::put('{user}', [UserController::class, 'update'])->name('update');
        });
    });
    
    // 角色管理路由
    Route::prefix('roles')->name('roles.')->middleware('permission:role.view')->group(function () {
        Route::get('', [RoleController::class, 'index'])->name('index');
        
        // 角色创建路由
        Route::middleware('permission:role.create')->group(function () {
            Route::get('create', [RoleController::class, 'create'])->name('create');
            Route::post('', [RoleController::class, 'store'])->name('store');
        });
        
        // 角色编辑路由
        Route::middleware('permission:role.edit')->group(function () {
            Route::get('{role}/edit', [RoleController::class, 'edit'])->name('edit');
            Route::put('{role}', [RoleController::class, 'update'])->name('update');
        });
        
        // 角色删除路由
        Route::middleware('permission:role.delete')
            ->delete('{role}', [RoleController::class, 'destroy'])
            ->name('destroy');
    });
    
    // 页面管理路由
    Route::prefix('page')->name('page.')->middleware('permission:page.view')->group(function () {
        Route::get('index', [WikiPageController::class, 'page'])->name('index');
        
        // 页面删除路由
        Route::middleware('permission:page.delete')
            ->delete('delete', [WikiPageController::class, 'page_delete'])
            ->name('destroy');
    });
});

/*
|--------------------------------------------------------------------------
| 用户仪表盘和个人资料路由
|--------------------------------------------------------------------------
*/
// 用户仪表盘
Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// 用户个人资料管理
Route::middleware('auth')->group(function () {
    Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// 引入认证相关路由
require __DIR__ . '/auth.php';

// 开发环境下的调试路由
if (config('app.env') !== 'production') {
    Route::get('routes', function () {
        $routes = collect(Route::getRoutes())->map(function ($route) {
            return [
                'uri' => $route->uri(),
                'name' => $route->getName(),
                'methods' => $route->methods(),
            ];
        });
        return response()->json($routes);
    });
}