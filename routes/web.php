<?php
// FileName: /var/www/Solarmax3Wiki/routes/web.php


use App\Http\Controllers\ProfileController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\GameWikiController;
use App\Http\Controllers\TextController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\WikiPageController;
use App\Http\Controllers\WikiCategoryController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ActivityLogController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/wiki', [WikiPageController::class, 'index'])->name('wiki.index');
Route::get('/wiki/{page}', [WikiPageController::class, 'show'])->name('wiki.show');


// Wiki 路由组
Route::middleware(['auth'])->prefix('wiki')->name('wiki.')->group(function () {
    Route::get('trash', [WikiPageController::class, 'trash'])
        ->middleware('permission:wiki.manage_trash')
        ->name('trash');


    Route::post('/issue', [WikiPageController::class, 'issue'])->name('issue');
    Route::post('/audit', [WikiPageController::class, 'audit'])->name('audit');
    Route::post('/lock', [WikiPageController::class, 'lock'])->name('lock');
    Route::post('/issue_handle', [WikiPageController::class, 'issue_handle'])->name('issue_handle');

    Route::post('trash/{id}/restore', [WikiPageController::class, 'restore'])
        ->middleware('permission:wiki.manage_trash')
        ->name('restore');

    Route::delete('trash/{id}', [WikiPageController::class, 'forceDelete'])
        ->middleware('permission:wiki.manage_trash')
        ->name('force-delete');
    // 页面路由
//    Route::get('/', [WikiPageController::class, 'index']);
    Route::get('/auth/create', [WikiPageController::class, 'create'])
        ->middleware('permission:wiki.create')->name('create');
    Route::post('/', [WikiPageController::class, 'store'])
        ->middleware('permission:wiki.create')->name('store');
    Route::get('/{page}/edit', [WikiPageController::class, 'edit'])
        ->middleware('permission:wiki.edit')->name('edit');
    Route::put('/{page}', [WikiPageController::class, 'update'])
        ->middleware('permission:wiki.edit')->name('update');
    Route::delete('/{page}', [WikiPageController::class, 'destroy'])
        ->middleware('permission:wiki.delete')->name('destroy');
    Route::post('/{page}/publish', [WikiPageController::class, 'publish'])
        ->middleware('permission:wiki.publish')->name('publish');
    Route::get('/{page}/revisions', [WikiPageController::class, 'revisions'])
        ->name('revisions');
    Route::get('/{page}/revisions/{version}', [WikiPageController::class, 'showRevision'])
        ->name('show-revision');
    Route::get('/{page}/compare/{fromVersion}/{toVersion}', [WikiPageController::class, 'compareRevisions'])
        ->name('compare-revisions');
    Route::post('/{page}/revert/{version}', [WikiPageController::class, 'revertToVersion'])
        ->middleware('permission:wiki.edit')
        ->name('revert-version');
    Route::post('/{page}/follow', [WikiPageController::class, 'toggleFollow'])
    ->middleware('auth')
    ->name('follow');

    // 分类路由
    Route::prefix('categories')->name('categories.')->group(function () {
        Route::get('/index', [WikiCategoryController::class, 'index'])->name('index');
//        Route::get('/', [WikiCategoryController::class, 'index'])->name('index');
        Route::get('/create', [WikiCategoryController::class, 'create'])
            ->middleware('permission:wiki.category.create')->name('create');
        Route::post('/', [WikiCategoryController::class, 'store'])
            ->middleware('permission:wiki.category.create')->name('store');
        Route::get('/{category}/edit', [WikiCategoryController::class, 'edit'])
            ->middleware('permission:wiki.category.edit')->name('edit');
        Route::put('/{category}', [WikiCategoryController::class, 'update'])
            ->middleware('permission:wiki.category.edit')->name('update');
        Route::delete('/{category}', [WikiCategoryController::class, 'destroy'])
            ->middleware('permission:wiki.category.delete')->name('destroy');
    });

    // 回收站相关路由

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

    Route::middleware('permission:page.view')->prefix("wiki")->group(function () {
        Route::get('/page/index', [WikiPageController::class, 'page'])->name('page.index');


        Route::delete('/page_delete', [WikiPageController::class, 'page_delete'])
            ->middleware('permission:page.delete')
            ->name('page.destroy');
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
