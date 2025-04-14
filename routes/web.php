<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WikiCategoryController;
use App\Http\Controllers\WikiCommentController;
use App\Http\Controllers\WikiController;
use App\Http\Controllers\WikiTagController;
use Illuminate\Support\Facades\Route;

Route::get('', [HomeController::class, 'index'])->name('home');

Route::prefix('wiki')->name('wiki.')->group(function () {
    Route::get('', [WikiController::class, 'index'])->name('index');

    Route::middleware(['auth'])->group(function () {

        Route::middleware('permission:wiki.create')->group(function () {
            Route::get('create', [WikiController::class, 'create'])->name('create');
            Route::post('', [WikiController::class, 'store'])->name('store');
        });

        Route::prefix('trash')->name('trash.')->middleware('permission:wiki.trash.view')->group(function () {
            Route::get('', [WikiController::class, 'trashIndex'])->name('index');
            Route::put('{pageId}/restore', [WikiController::class, 'restore'])
                ->where('pageId', '[0-9]+')
                ->middleware('permission:wiki.trash.restore') // 添加恢复权限
                ->name('restore');
            Route::delete('{pageId}/force-delete', [WikiController::class, 'forceDelete'])
                ->where('pageId', '[0-9]+')
                ->middleware('permission:wiki.trash.force_delete') // 添加永久删除权限
                ->name('force-delete');
        });
        Route::middleware('permission:wiki.delete')
            ->delete('{page:slug}', [WikiController::class, 'destroy'])
            ->name('destroy');

        Route::middleware('permission:wiki.edit')->group(function () {
            Route::get('{page:slug}/edit', [WikiController::class, 'edit'])->name('edit');
            Route::put('{page:slug}', [WikiController::class, 'update'])->name('update');
            Route::post('{page}/draft', [WikiController::class, 'saveDraft'])->where('page', '[0-9]+')->name('save-draft');
            Route::post('{page:slug}/revert/{version}', [WikiController::class, 'revertToVersion'])->where('version', '[0-9]+')->name('revert-version');
        });

        Route::middleware('permission:wiki.resolve_conflict')->group(function () {
            Route::get('{page:slug}/conflicts', [WikiController::class, 'showConflicts'])->name('show-conflicts');
            Route::post('{page:slug}/resolve-conflict', [WikiController::class, 'resolveConflict'])->name('resolve-conflict');
        });

        Route::middleware('permission:wiki.comment')->post('{page:slug}/comments', [WikiCommentController::class, 'store'])->name('comments.store');
        Route::put('comments/{comment}', [WikiCommentController::class, 'update'])->where('comment', '[0-9]+')->name('comments.update');
        Route::delete('comments/{comment}', [WikiCommentController::class, 'destroy'])->where('comment', '[0-9]+')->name('comments.destroy');

        Route::middleware('permission:wiki.manage_categories')->prefix('categories')->name('categories.')->group(function () {
            Route::get('', [WikiCategoryController::class, 'index'])->name('index');
            Route::get('create', [WikiCategoryController::class, 'create'])->name('create');
            Route::post('', [WikiCategoryController::class, 'store'])->name('store');
            Route::get('{category}/edit', [WikiCategoryController::class, 'edit'])->where('category', '[0-9]+')->name('edit');
            Route::put('{category}', [WikiCategoryController::class, 'update'])->where('category', '[0-9]+')->name('update');
            Route::delete('{category}', [WikiCategoryController::class, 'destroy'])->where('category', '[0-9]+')->name('destroy');
        });

        Route::middleware('permission:wiki.manage_tags')->prefix('tags')->name('tags.')->group(function () {
            Route::get('', [WikiTagController::class, 'index'])->name('index');
            Route::post('', [WikiTagController::class, 'store'])->name('store');
            Route::put('{tag}', [WikiTagController::class, 'update'])->where('tag', '[0-9]+')->name('update');
            Route::delete('{tag}', [WikiTagController::class, 'destroy'])->where('tag', '[0-9]+')->name('destroy');
        });

        Route::get('{page:id}/editors', [WikiController::class, 'getEditors'])->where('page', '[0-9]+')->name('editors');
        Route::post('{page:id}/editors/register', [WikiController::class, 'registerEditor'])->where('page', '[0-9]+')->name('editors.register');
        Route::post('{page:id}/editors/unregister', [WikiController::class, 'unregisterEditor'])->where('page', '[0-9]+')->name('editors.unregister');
        Route::get('{page:id}/discussion', [WikiController::class, 'getDiscussionMessages'])->where('page', '[0-9]+')->name('discussion');
        Route::post('{page:id}/discussion', [WikiController::class, 'sendDiscussionMessage'])->where('page', '[0-9]+')->name('discussion.send');

        Route::middleware('permission:wiki.history')->group(function () {
            Route::get('{page:slug}/history', [WikiController::class, 'history'])->name('history');
            Route::get('{page:slug}/versions/{version}', [WikiController::class, 'showVersion'])->where('version', '[0-9]+')->name('show-version');
            Route::get('{page:slug}/compare/{fromVersion}/{toVersion}', [WikiController::class, 'compareVersions'])
                ->where(['fromVersion' => '[0-9]+', 'toVersion' => '[0-9]+'])
                ->name('compare-versions');
        });
    });

    Route::get('{page:slug}', [WikiController::class, 'show'])->name('show');
});

Route::middleware(['auth'])->group(function () {

    Route::middleware('permission:log.view')
        ->get('activity-logs', [ActivityLogController::class, 'index'])
        ->name('activity-logs.index');

    Route::prefix('users')->name('users.')->middleware('permission:user.view')->group(function () {
        Route::get('', [UserController::class, 'index'])->name('index');
        Route::middleware('permission:user.edit')->group(function () {
            Route::get('{user}/edit', [UserController::class, 'edit'])->where('user', '[0-9]+')->name('edit');
            Route::put('{user}', [UserController::class, 'update'])->where('user', '[0-9]+')->name('update');
        });
    });

    Route::prefix('roles')->name('roles.')->middleware('permission:role.view')->group(function () {
        Route::get('', [RoleController::class, 'index'])->name('index');
        Route::middleware('permission:role.create')->group(function () {
            Route::get('create', [RoleController::class, 'create'])->name('create');
            Route::post('', [RoleController::class, 'store'])->name('store');
        });
        Route::middleware('permission:role.edit')->group(function () {
            Route::get('{role}/edit', [RoleController::class, 'edit'])->where('role', '[0-9]+')->name('edit');
            Route::put('{role}', [RoleController::class, 'update'])->where('role', '[0-9]+')->name('update');
        });
        Route::middleware('permission:role.delete')->delete('{role}', [RoleController::class, 'destroy'])->where('role', '[0-9]+')->name('destroy');
    });

    Route::get('dashboard', [DashboardController::class, 'index']) // 修改这里
        ->middleware('verified')
        ->name('dashboard');

    Route::prefix('profile')->group(function () {
        Route::get('', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('', [ProfileController::class, 'destroy'])->name('profile.destroy');
    });
});

require __DIR__.'/auth.php';
