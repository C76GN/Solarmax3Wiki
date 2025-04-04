<?php
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

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::prefix('wiki')->group(function () {
    Route::get('', [WikiPageController::class, 'index'])->name('wiki.index');
    Route::get('{page}', [WikiPageController::class, 'show'])
        ->name('wiki.show')
        ->where('page', '[0-9]+');
});
Route::post('lock', [WikiPageController::class, 'lockPage'])->name('lock');
    Route::post('unlock', [WikiPageController::class, 'unlockPage'])->name('unlock');
    Route::get('{page}/lock-status', [WikiPageController::class, 'checkPageLock'])->name('lock-status');

Route::middleware(['auth'])->prefix('wiki')->name('wiki.')->group(function () {
    Route::middleware('permission:wiki.create')->group(function () {
        Route::get('create', [WikiPageController::class, 'create'])->name('create');
        Route::post('', [WikiPageController::class, 'store'])->name('store');
    });

    // 草稿相关路由
    Route::post('drafts', [WikiPageDraftController::class, 'saveDraft'])->name('drafts.save');
    Route::get('{page}/drafts', [WikiPageDraftController::class, 'getDraft'])->name('drafts.get');
    Route::delete('{page}/drafts', [WikiPageDraftController::class, 'deleteDraft'])->name('drafts.delete');
    Route::get('drafts', [WikiPageDraftController::class, 'listDrafts'])->name('drafts.list');

    Route::get('{page}/discussions', [WikiPageDiscussionController::class, 'getMessages'])
        ->name('discussions');
    Route::post('{page}/discussions', [WikiPageDiscussionController::class, 'sendMessage'])
        ->name('discussions.send');

    Route::get('{page}/section-locks', [WikiPageSectionLockController::class, 'getSectionLocks'])
        ->name('section-locks');
    Route::post('{page}/section-locks', [WikiPageSectionLockController::class, 'lockSection'])
        ->name('section-locks.lock');
    Route::delete('{page}/section-locks', [WikiPageSectionLockController::class, 'unlockSection'])
        ->name('section-locks.unlock');
    
    Route::post('preview', [WikiPageController::class, 'preview'])->name('preview');
    Route::get('{page}/edit-conflict', [WikiPageController::class, 'showEditConflict'])->name('edit-conflict');
    Route::get('{page}/revisions/diff/{fromVersion}/{toVersion}', [WikiPageRevisionController::class, 'diffView'])
        ->name('revisions-diff');
        
    Route::middleware('permission:wiki.edit')->group(function () {
        Route::get('{page}/edit', [WikiPageController::class, 'edit'])->name('edit');
        Route::put('{page}', [WikiPageController::class, 'update'])->name('update');
        Route::post('{page}/revert/{version}', [WikiPageRevisionController::class, 'revert'])
            ->name('revert-version');
    });
    
    Route::middleware('permission:wiki.delete')
        ->delete('{page}', [WikiPageController::class, 'destroy'])
        ->name('destroy');
        
    Route::middleware('permission:wiki.publish')
        ->post('{page}/publish', [WikiPageController::class, 'publish'])
        ->name('publish');
        
    Route::post('{page}/follow', [WikiPageController::class, 'toggleFollow'])->name('follow');
    Route::post('issue', [WikiPageController::class, 'issue'])->name('issue');
    Route::post('issue_handle', [WikiPageController::class, 'issue_handle'])->name('issue_handle');
    
    Route::prefix('pages')->group(function () {
        Route::get('{page}/revisions', [WikiPageRevisionController::class, 'index'])->name('revisions');
        Route::get('{page}/revisions/{version}', [WikiPageRevisionController::class, 'show'])
            ->name('show-revision');
        Route::get('{page}/compare/{fromVersion}/{toVersion}', [WikiPageRevisionController::class, 'compare'])
            ->name('compare-revisions');
        Route::get('{page}/compare-live', [WikiPageRevisionController::class, 'compareLive'])
            ->name('compare-live');
    });
    
    Route::prefix('trash')->middleware('permission:wiki.manage_trash')->group(function () {
        Route::get('', [WikiPageController::class, 'trash'])->name('trash');
        Route::post('{id}/restore', [WikiPageController::class, 'restore'])->name('restore');
        Route::post('restore-selected', [WikiPageController::class, 'restoreSelected'])
            ->name('restore-selected');
        Route::delete('{id}', [WikiPageController::class, 'forceDelete'])->name('force-delete');
        Route::delete('force-delete-selected', [WikiPageController::class, 'forceDeleteSelected'])
            ->name('force-delete-selected');
    });
    
    Route::prefix('categories')->name('categories.')->group(function () {
        Route::get('', [WikiCategoryController::class, 'index'])->name('index');
        Route::middleware('permission:wiki.category.create')->group(function () {
            Route::get('create', [WikiCategoryController::class, 'create'])->name('create');
            Route::post('', [WikiCategoryController::class, 'store'])->name('store');
        });
        Route::middleware('permission:wiki.category.edit')->group(function () {
            Route::get('{category}/edit', [WikiCategoryController::class, 'edit'])->name('edit');
            Route::put('{category}', [WikiCategoryController::class, 'update'])->name('update');
        });
        Route::middleware('permission:wiki.category.delete')
            ->delete('{category}', [WikiCategoryController::class, 'destroy'])
            ->name('destroy');
    });
});

Route::middleware(['auth'])->group(function () {
    Route::middleware('permission:log.view')
        ->get('activity-logs', [ActivityLogController::class, 'index'])
        ->name('activity-logs.index');
        
    Route::prefix('users')->name('users.')->middleware('permission:user.view')->group(function () {
        Route::get('', [UserController::class, 'index'])->name('index');
        Route::middleware('permission:user.edit')->group(function () {
            Route::get('{user}/edit', [UserController::class, 'edit'])->name('edit');
            Route::put('{user}', [UserController::class, 'update'])->name('update');
        });
    });
    
    Route::prefix('roles')->name('roles.')->middleware('permission:role.view')->group(function () {
        Route::get('', [RoleController::class, 'index'])->name('index');
        Route::middleware('permission:role.create')->group(function () {
            Route::get('create', [RoleController::class, 'create'])->name('create');
            Route::post('', [RoleController::class, 'store'])->name('store');
        });
        Route::middleware('permission:role.edit')->group(function () {
            Route::get('{role}/edit', [RoleController::class, 'edit'])->name('edit');
            Route::put('{role}', [RoleController::class, 'update'])->name('update');
        });
        Route::middleware('permission:role.delete')
            ->delete('{role}', [RoleController::class, 'destroy'])
            ->name('destroy');
    });
    
    Route::prefix('page')->name('page.')->middleware('permission:page.view')->group(function () {
        Route::get('index', [WikiPageController::class, 'page'])->name('index');
        Route::middleware('permission:page.delete')
            ->delete('delete', [WikiPageController::class, 'page_delete'])
            ->name('destroy');
    });
});

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';

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