<?php
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\WikiController;
use App\Http\Controllers\WikiCommentController;
use App\Http\Controllers\WikiCategoryController;
use App\Http\Controllers\WikiTagController;
use App\Http\Controllers\WikiTemplateController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('', [HomeController::class, 'index'])->name('home');
Route::prefix('wiki')->name('wiki.')->group(function () {
    Route::get('', [WikiController::class, 'index'])->name('index');
    Route::middleware(['auth'])->group(function () {
        Route::middleware('permission:wiki.create')->get('create', [WikiController::class, 'create'])->name('create');
        Route::middleware('permission:wiki.create')->post('', [WikiController::class, 'store'])->name('store');
        Route::middleware('permission:wiki.edit')->get('{page:slug}/edit', [WikiController::class, 'edit'])->name('edit');
        Route::middleware('permission:wiki.edit')->put('{page:slug}', [WikiController::class, 'update'])->name('update');
        Route::middleware('permission:wiki.delete')->delete('{page:slug}', [WikiController::class, 'destroy'])->name('destroy');
        Route::middleware('permission:wiki.edit')->post('{page:slug}/draft', [WikiController::class, 'saveDraft'])->name('save-draft');
        Route::middleware('permission:wiki.edit')->post('unlock', [WikiController::class, 'unlock'])->name('unlock');
        Route::middleware('permission:wiki.edit')->post('refresh-lock', [WikiController::class, 'refreshLock'])->name('refresh-lock');
        Route::middleware('permission:wiki.edit')->post('{page:slug}/revert/{version}', [WikiController::class, 'revertToVersion'])->name('revert-version');
        Route::middleware('permission:wiki.resolve_conflict')->get('{page:slug}/conflicts', [WikiController::class, 'showConflicts'])->name('show-conflicts');
        Route::middleware('permission:wiki.resolve_conflict')->post('{page:slug}/resolve-conflict', [WikiController::class, 'resolveConflict'])->name('resolve-conflict');
        Route::middleware('permission:wiki.comment')->post('{page:slug}/comments', [WikiCommentController::class, 'store'])->name('comments.store');
        Route::put('comments/{comment}', [WikiCommentController::class, 'update'])->name('comments.update');
        Route::delete('comments/{comment}', [WikiCommentController::class, 'destroy'])->name('comments.destroy');
        Route::middleware('permission:wiki.manage_categories')->prefix('categories')->name('categories.')->group(function () {
            Route::get('', [WikiCategoryController::class, 'index'])->name('index');
            Route::get('create', [WikiCategoryController::class, 'create'])->name('create');
            Route::post('', [WikiCategoryController::class, 'store'])->name('store');
            Route::get('{category}/edit', [WikiCategoryController::class, 'edit'])->name('edit');
            Route::put('{category}', [WikiCategoryController::class, 'update'])->name('update');
            Route::delete('{category}', [WikiCategoryController::class, 'destroy'])->name('destroy');
        });
        Route::middleware('permission:wiki.manage_tags')->prefix('tags')->name('tags.')->group(function () {
            Route::get('', [WikiTagController::class, 'index'])->name('index');
            Route::post('', [WikiTagController::class, 'store'])->name('store');
            Route::put('{tag}', [WikiTagController::class, 'update'])->name('update');
            Route::delete('{tag}', [WikiTagController::class, 'destroy'])->name('destroy');
        });
        Route::middleware('permission:wiki.manage_templates')->prefix('templates')->name('templates.')->group(function () {
            Route::get('', [WikiTemplateController::class, 'index'])->name('index');
            Route::get('create', [WikiTemplateController::class, 'create'])->name('create');
            Route::post('', [WikiTemplateController::class, 'store'])->name('store');
            Route::get('{template}/edit', [WikiTemplateController::class, 'edit'])->name('edit');
            Route::put('{template}', [WikiTemplateController::class, 'update'])->name('update');
            Route::delete('{template}', [WikiTemplateController::class, 'destroy'])->name('destroy');
        });
        Route::get('{page:id}/editors', [WikiController::class, 'getEditors'])->name('editors');
        Route::post('{page:id}/editors/register', [WikiController::class, 'registerEditor'])->name('editors.register');
        Route::post('{page:id}/editors/unregister', [WikiController::class, 'unregisterEditor'])->name('editors.unregister');
        Route::get('{page:id}/discussion', [WikiController::class, 'getDiscussionMessages'])->name('discussion');
        Route::post('{page:id}/discussion', [WikiController::class, 'sendDiscussionMessage'])->name('discussion.send');
    });
    Route::get('{page:slug}', [WikiController::class, 'show'])->name('show');
    Route::get('{page:slug}/history', [WikiController::class, 'history'])->name('history');
    Route::get('{page:slug}/versions/{version}', [WikiController::class, 'showVersion'])->name('show-version');
    Route::get('{page:slug}/compare/{fromVersion}/{toVersion}', [WikiController::class, 'compareVersions'])->name('compare-versions');
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
    Route::get('dashboard', function () {
        return Inertia::render('Dashboard');
    })->middleware('verified')->name('dashboard');
    Route::prefix('profile')->group(function() {
        Route::get('', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('', [ProfileController::class, 'destroy'])->name('profile.destroy');
    });
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

Route::get('/debug-permissions', function () {
    $user = auth()->user();
    if (!$user) {
        return "未登录";
    }
    
    return [
        'user' => $user->only(['id', 'name', 'email']),
        'roles' => $user->roles->map(fn($role) => [
            'id' => $role->id,
            'name' => $role->name,
            'display_name' => $role->display_name
        ]),
        'permissions' => $user->getAllPermissionsAttribute(),
        'has_wiki_create' => $user->hasPermission('wiki.create'),
        'auth_check' => auth()->check()
    ];
});