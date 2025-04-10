<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\WikiCategory;
use App\Models\WikiPage;
use App\Models\WikiPageDraft;
use App\Models\WikiTag;
use App\Models\WikiVersion;
use App\Services\CollaborationService;
use App\Services\DiffService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Mews\Purifier\Facades\Purifier;
use Overtrue\LaravelPinyin\Facades\Pinyin;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class WikiController extends Controller
{
    public function index(Request $request): InertiaResponse
    {
        $query = WikiPage::with(['creator:id,name', 'categories:id,name,slug', 'tags:id,name,slug'])
            ->where('status', WikiPage::STATUS_PUBLISHED);

        if ($request->filled('category')) {
            $query->whereHas('categories', function ($q) use ($request) {
                $q->where('slug', $request->category);
            });
        }
        if ($request->filled('tag')) {
            $query->whereHas('tags', function ($q) use ($request) {
                $q->where('slug', $request->tag);
            });
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        $pages = $query->latest('updated_at')
            ->paginate(15)
            ->withQueryString();

        $categories = WikiCategory::withCount(['pages' => function ($query) {
            $query->where('status', WikiPage::STATUS_PUBLISHED);
        }])
            ->orderBy('order')
            ->select('id', 'name', 'slug')
            ->get();

        $tags = WikiTag::withCount(['pages' => function ($query) {
            $query->where('status', WikiPage::STATUS_PUBLISHED);
        }])
            ->select('id', 'name', 'slug')
            ->get();

        return Inertia::render('Wiki/Index', [
            'pages' => $pages,
            'categories' => $categories,
            'tags' => $tags,
            'filters' => $request->only(['category', 'tag', 'search']),
            'flash' => session('flash'),
        ]);
    }

    public function show(string $slug): InertiaResponse
    {
        $page = WikiPage::where('slug', $slug)
            ->with(['creator:id,name', 'categories:id,name,slug', 'tags:id,name,slug'])
            ->firstOrFail();

        $currentVersion = null;
        if ($page->current_version_id) {
            $currentVersion = WikiVersion::with('creator:id,name')->find($page->current_version_id);
        }

        $canResolveConflict = Auth::check() && Gate::allows('resolve_conflict', $page);
        if ($page->status === WikiPage::STATUS_CONFLICT && ! $canResolveConflict) {
            Log::warning('Access Denied: User (ID: '.Auth::id().") attempted to view conflicted page {$page->id} ('{$page->title}') without resolve permissions.");
            $page->loadMissing('creator:id,name');

            return Inertia::render('Wiki/Conflict', [
                'page' => $page->only('id', 'title', 'slug', 'creator'),
                'message' => '此页面当前存在编辑冲突，需要管理员或指定人员解决。',
                'canResolveConflict' => $canResolveConflict,
            ]);
        }

        if ($page->current_version_id && ! $currentVersion) {
            Log::error("Data Consistency Error: WikiPage ID {$page->id} ('{$page->title}') has current_version_id={$page->current_version_id}, but WikiVersion not found.");
            $page->load(['comments' => function ($query) {
                $query->where('is_hidden', false)
                    ->whereNull('parent_id')
                    ->with(['user:id,name', 'replies' => function ($q) {
                        $q->where('is_hidden', false)->with('user:id,name')->latest('created_at');
                    }])
                    ->latest('created_at');
            }]);

            return Inertia::render('Wiki/Show', [
                'page' => $page,
                'currentVersion' => null,
                'isLocked' => false,
                'lockedBy' => null,
                'draft' => null,
                'canEditPage' => false,
                'canResolveConflict' => $canResolveConflict,
                'error' => '无法加载页面内容，请联系管理员检查数据。',
                'comments' => $page->comments ?? [],
                'flash' => session('flash'),
            ]);
        }

        $page->load(['comments' => function ($query) {
            $query->where('is_hidden', false)
                ->whereNull('parent_id')
                ->with(['user:id,name', 'replies' => function ($q) {
                    $q->where('is_hidden', false)->with('user:id,name')->latest('created_at');
                }])
                ->latest('created_at');
        }]);

        $isLocked = $page->isLocked();
        $lockedBy = null;
        if ($isLocked) {
            $page->loadMissing('locker:id,name');
            $lockedBy = $page->locker;
        }

        $draft = null;
        if (Auth::check()) {
            $draft = WikiPageDraft::where('wiki_page_id', $page->id)
                ->where('user_id', Auth::id())
                ->select('content', 'last_saved_at')
                ->first();
        }

        $canEdit = Auth::check() && ! $isLocked;

        return Inertia::render('Wiki/Show', [
            'page' => $page,
            'currentVersion' => $currentVersion,
            'isLocked' => $isLocked,
            'lockedBy' => $lockedBy ? $lockedBy->only('id', 'name') : null,
            'draft' => $draft,
            'canEditPage' => $canEdit,
            'canResolveConflict' => $canResolveConflict,
            'error' => session('error'),
            'flash' => session('flash'),
            'comments' => $page->comments ?? [],
        ]);
    }

    public function create(): InertiaResponse
    {
        $categories = WikiCategory::select('id', 'name')->orderBy('order')->get();
        $tags = WikiTag::select('id', 'name')->get();

        return Inertia::render('Wiki/Create', [
            'categories' => $categories,
            'tags' => $tags,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255|unique:wiki_pages,title',
            'content' => 'required|string',
            'category_ids' => 'required|array|min:1',
            'category_ids.*' => 'required|exists:wiki_categories,id',
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'exists:wiki_tags,id',
        ]);

        $validated['content'] = Purifier::clean($validated['content']);
        $slug = Str::slug($validated['title']);
        if (empty($slug)) {
            $slug = Pinyin::permalink($validated['title'], '-');
        }
        $originalSlug = $slug;
        $counter = 1;
        while (WikiPage::where('slug', $slug)->exists()) {
            $slug = $originalSlug.'-'.$counter++;
        }

        DB::beginTransaction();
        try {
            $page = WikiPage::create([
                'title' => $validated['title'],
                'slug' => $slug,
                'status' => WikiPage::STATUS_DRAFT,
                'created_by' => Auth::id(),
                'current_version_id' => null,
            ]);

            $version = WikiVersion::create([
                'wiki_page_id' => $page->id,
                'content' => $validated['content'],
                'created_by' => Auth::id(),
                'version_number' => 1,
                'comment' => '初始版本',
                'is_current' => true,
            ]);

            $page->update([
                'current_version_id' => $version->id,
                'status' => WikiPage::STATUS_PUBLISHED,
            ]);

            $page->categories()->attach($validated['category_ids']);
            if (! empty($validated['tag_ids'])) {
                $page->tags()->attach($validated['tag_ids']);
            }

            $this->logActivity('create', $page, ['version' => $version->version_number]);

            DB::commit();
            Log::info("Wiki page {$page->id} ('{$page->title}') created successfully by user ".Auth::id());

            return redirect()->route('wiki.show', $page->slug)
                ->with('flash', ['message' => ['type' => 'success', 'text' => '页面创建成功！']]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating wiki page: '.$e->getMessage());

            return back()->withErrors(['general' => '创建页面时出错，请稍后重试。'])->withInput();
        }
    }

    public function edit(WikiPage $page): InertiaResponse|RedirectResponse
    {
        $user = Auth::user();
        $isInConflict = $page->status === WikiPage::STATUS_CONFLICT;
        $canResolveConflict = $user && Gate::allows('resolve_conflict', $page);

        $draft = WikiPageDraft::where('wiki_page_id', $page->id)
            ->where('user_id', $user->id)
            ->select('content', 'last_saved_at')
            ->orderBy('last_saved_at', 'desc')
            ->first();

        $content = $draft ? $draft->content : ($page->currentVersion ? $page->currentVersion->content : '');

        $page->load(['currentVersion:id,wiki_page_id,version_number', 'categories:id', 'tags:id']);
        $categories = WikiCategory::select('id', 'name')->orderBy('order')->get();
        $tags = WikiTag::select('id', 'name')->orderBy('name')->get();

        app(CollaborationService::class)->registerEditor($page, $user);

        $editPageData = [
            'page' => array_merge(
                $page->only('id', 'title', 'slug', 'current_version_id', 'status'),
                [
                    'category_ids' => $page->categories->pluck('id')->toArray(),
                    'tag_ids' => $page->tags->pluck('id')->toArray(),
                    'current_version' => $page->currentVersion,
                ]
            ),
            'content' => $content,
            'categories' => $categories,
            'tags' => $tags,
            'hasDraft' => ! is_null($draft),
            'lastSaved' => $draft ? $draft->last_saved_at->toIso8601String() : null,
            'canResolveConflict' => $canResolveConflict,
            'isConflict' => $isInConflict,
            'errors' => session('errors') ? session('errors')->getBag('default')->getMessages() : (object) [],
            'flash' => session('flash'),
        ];

        return Inertia::render('Wiki/Edit', $editPageData);
    }

    public function update(Request $request, WikiPage $page): RedirectResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'category_ids' => 'required|array|min:1',
            'category_ids.*' => 'required|exists:wiki_categories,id',
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'exists:wiki_tags,id',
            'comment' => 'nullable|string|max:255',
            'version_id' => [
                'required',
                'integer',
                function ($attribute, $value, $fail) use ($page) {
                    if (! WikiVersion::where('id', $value)->where('wiki_page_id', $page->id)->exists()) {
                        $fail('提交所基于的版本ID无效或不属于此页面。');
                    }
                },
            ],
        ]);

        $newContent = Purifier::clean($validated['content']);
        $currentVersionIdInDb = $page->fresh()->current_version_id;
        $submittedBaseVersionId = $validated['version_id'];

        if ($currentVersionIdInDb != $submittedBaseVersionId) {
            Log::info("Potential Conflict Check: User {$user->id} submitted based on version {$submittedBaseVersionId}, but current DB version is {$currentVersionIdInDb} for page {$page->id}. Running DiffService check.");
            $baseVersion = WikiVersion::find($submittedBaseVersionId);
            $currentDbVersion = WikiVersion::find($currentVersionIdInDb);

            if ($baseVersion && $currentDbVersion) {
                $diffService = app(DiffService::class);
                if ($diffService->hasConflict($baseVersion->content, $currentDbVersion->content, $newContent)) {
                    Log::warning("Conflict DETECTED on page {$page->id} by DiffService. User: {$user->id}, Base: {$submittedBaseVersionId}, Current: {$currentVersionIdInDb}");
                    $latestVersionNumber = $page->versions()->latest('version_number')->value('version_number') ?? 0;
                    WikiVersion::create([
                        'wiki_page_id' => $page->id,
                        'content' => $newContent,
                        'created_by' => $user->id,
                        'version_number' => $latestVersionNumber + 1,
                        'comment' => $validated['comment'] ?: '提交时检测到冲突',
                        'is_current' => false,
                    ]);
                    $page->markAsConflict();
                    WikiPageDraft::where('wiki_page_id', $page->id)->where('user_id', $user->id)->delete();
                    app(CollaborationService::class)->unregisterEditor($page, $user);

                    return redirect()->route('wiki.show-conflicts', $page->slug)
                        ->with('flash', ['message' => ['type' => 'error', 'text' => '编辑冲突：在您编辑期间，其他用户已修改此页面。您的更改已保存为待处理的冲突版本，请解决冲突。']]);
                } else {
                    Log::info("No actual content conflict detected by DiffService for page {$page->id} despite version mismatch. Proceeding with update based on submitted content.");
                }
            } else {
                Log::error("Conflict check failed for page {$page->id}: Base version {$submittedBaseVersionId} or Current DB version {$currentVersionIdInDb} not found.");

                return back()->withErrors(['general' => '无法验证页面版本，请刷新后重试。'])->withInput();
            }
        }

        DB::beginTransaction();
        try {
            if ($page->status === WikiPage::STATUS_CONFLICT) {
                if (Gate::allows('resolve_conflict', $page)) {
                    Log::info("User {$user->id} with resolve permission is implicitly resolving conflict for page {$page->id} by saving a new version.");
                } else {
                    Log::warning("User {$user->id} WITHOUT resolve permission is implicitly resolving conflict for page {$page->id} by saving a new version (potentially overwriting changes).");
                }
                $page->markAsResolved($user);
            }

            $page->update(['title' => $validated['title']]);

            $latestVersionNumber = $page->versions()->latest('version_number')->value('version_number') ?? 0;
            $newVersion = WikiVersion::create([
                'wiki_page_id' => $page->id,
                'content' => $newContent,
                'created_by' => $user->id,
                'version_number' => $latestVersionNumber + 1,
                'comment' => $validated['comment'] ?: '更新页面',
                'is_current' => true,
            ]);

            if ($currentVersionIdInDb) {
                WikiVersion::where('id', $currentVersionIdInDb)->update(['is_current' => false]);
            }
            $page->update(['current_version_id' => $newVersion->id]);

            $page->categories()->sync($validated['category_ids']);
            $page->tags()->sync($validated['tag_ids'] ?? []);

            WikiPageDraft::where('wiki_page_id', $page->id)->where('user_id', $user->id)->delete();
            app(CollaborationService::class)->unregisterEditor($page, $user);
            $this->logActivity('update', $page, ['version' => $newVersion->version_number]);

            DB::commit();
            Log::info("Page {$page->id} updated successfully to version {$newVersion->version_number} by user {$user->id}.");

            return redirect()->route('wiki.show', $page->slug)
                ->with('flash', ['message' => ['type' => 'success', 'text' => '页面更新成功！']]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error updating page {$page->id} by user {$user->id}: ".$e->getMessage()."\n".$e->getTraceAsString());

            return back()->withErrors(['general' => '保存页面时发生内部错误，请稍后重试。'])->withInput();
        }
    }

    public function saveDraft(Request $request, $pageId): JsonResponse
    {
        $page = WikiPage::findOrFail((int) $pageId);
        $user = Auth::user();

        if (! $user) {
            return response()->json(['message' => '需要登录才能执行此操作'], SymfonyResponse::HTTP_UNAUTHORIZED);
        }

        if ($page->status === WikiPage::STATUS_CONFLICT) {
            Log::warning("Draft save denied for page {$page->id} by user {$user->id}. Reason: Page is in conflict status.");

            return response()->json(['message' => '页面处于冲突状态，无法保存草稿'], SymfonyResponse::HTTP_CONFLICT);
        }

        $validated = $request->validate(['content' => 'required|string']);

        try {
            $draft = WikiPageDraft::updateOrCreate(
                ['wiki_page_id' => $page->id, 'user_id' => $user->id],
                [
                    'content' => Purifier::clean($validated['content']),
                    'last_saved_at' => now(),
                ]
            );
            Log::info("Draft saved for page {$page->id} by user {$user->id}. Draft ID: {$draft->id}");

            return response()->json([
                'message' => '草稿已自动保存',
                'saved_at' => $draft->last_saved_at->toIso8601String(),
                'draft_id' => $draft->id,
                'success' => true,
            ]);
        } catch (\Exception $e) {
            Log::error("Error saving draft for page {$page->id} by user {$user->id}: ".$e->getMessage());

            return response()->json(['message' => '保存草稿时出错', 'success' => false], SymfonyResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy(WikiPage $page): RedirectResponse
    {
        DB::beginTransaction();
        try {
            $pageTitle = $page->title;
            $pageId = $page->id;
            $userId = Auth::id();

            $page->delete();
            $this->logActivity('delete', $page, ['title' => $pageTitle]);

            DB::commit();
            Log::info("Wiki page {$pageId} ('{$pageTitle}') deleted by user {$userId}.");

            return redirect()->route('wiki.index')
                ->with('flash', ['message' => ['type' => 'success', 'text' => '页面已删除！']]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error deleting page {$page->id}: ".$e->getMessage());

            return back()->withErrors(['general' => '删除页面时出错，请稍后重试。']);
        }
    }

    public function showVersion(WikiPage $page, int $versionNumber): InertiaResponse
    {
        $versionRecord = WikiVersion::where('wiki_page_id', $page->id)
            ->where('version_number', $versionNumber)
            ->with('creator:id,name')
            ->firstOrFail();

        $page->load(['creator:id,name', 'categories:id,name,slug', 'tags:id,name,slug']);

        return Inertia::render('Wiki/ShowVersion', [
            'page' => $page->only('id', 'title', 'slug', 'creator', 'categories', 'tags'),
            'version' => $versionRecord->only('id', 'version_number', 'content', 'comment', 'created_at', 'creator'),
        ]);
    }

    public function history(WikiPage $page): InertiaResponse
    {
        $versions = WikiVersion::where('wiki_page_id', $page->id)
            ->with('creator:id,name')
            ->orderBy('version_number', 'desc')
            ->select('id', 'version_number', 'comment', 'created_at', 'created_by', 'is_current')
            ->paginate(15);

        return Inertia::render('Wiki/History', [
            'page' => $page->only('id', 'title', 'slug'),
            'versions' => $versions,
        ]);
    }

    public function compareVersions(WikiPage $page, int $fromVersionNumber, int $toVersionNumber): InertiaResponse|RedirectResponse
    {
        if ($fromVersionNumber === $toVersionNumber) {
            return redirect()->route('wiki.history', $page->slug)
                ->with('flash', ['message' => ['type' => 'warning', 'text' => '请选择两个不同的版本进行比较。']]);
        }

        $fromVersion = WikiVersion::where('wiki_page_id', $page->id)
            ->where('version_number', $fromVersionNumber)
            ->with('creator:id,name')
            ->firstOrFail();

        $toVersion = WikiVersion::where('wiki_page_id', $page->id)
            ->where('version_number', $toVersionNumber)
            ->with('creator:id,name')
            ->firstOrFail();

        $diffService = app(DiffService::class);
        $diffHtml = $diffService->generateDiffHtml($fromVersion->content, $toVersion->content);

        $olderVersion = $fromVersion->version_number < $toVersion->version_number ? $fromVersion : $toVersion;
        $newerVersion = $fromVersion->version_number > $toVersion->version_number ? $fromVersion : $toVersion;

        return Inertia::render('Wiki/Compare', [
            'page' => $page->only('id', 'title', 'slug'),
            'fromVersion' => $olderVersion->only('id', 'version_number', 'content', 'comment', 'created_at', 'creator'),
            'toVersion' => $newerVersion->only('id', 'version_number', 'content', 'comment', 'created_at', 'creator'),
            'fromCreator' => $olderVersion->creator,
            'toCreator' => $newerVersion->creator,
            'diffHtml' => $diffHtml,
        ]);
    }

    public function revertToVersion(Request $request, WikiPage $page, int $versionNumberToRevertTo): RedirectResponse
    {
        $user = Auth::user();

        $versionToRevert = WikiVersion::where('wiki_page_id', $page->id)
            ->where('version_number', $versionNumberToRevertTo)
            ->firstOrFail();

        if ($page->status === WikiPage::STATUS_CONFLICT) {
            return back()->withErrors(['general' => '无法恢复版本，页面当前处于冲突状态，请先解决冲突。']);
        }

        DB::beginTransaction();
        try {
            $currentVersionIdInDb = $page->current_version_id;
            $latestVersionNumber = $page->versions()->latest('version_number')->value('version_number') ?? 0;

            $newVersion = WikiVersion::create([
                'wiki_page_id' => $page->id,
                'content' => $versionToRevert->content,
                'created_by' => $user->id,
                'version_number' => $latestVersionNumber + 1,
                'comment' => '恢复自版本 '.$versionNumberToRevertTo,
                'is_current' => true,
            ]);

            if ($currentVersionIdInDb) {
                WikiVersion::where('id', $currentVersionIdInDb)->update(['is_current' => false]);
            }
            $page->update(['current_version_id' => $newVersion->id]);

            $this->logActivity('revert', $page, [
                'reverted_to_version' => $versionNumberToRevertTo,
                'new_version' => $newVersion->version_number,
            ]);

            DB::commit();
            Log::info("Page {$page->id} reverted to version {$versionNumberToRevertTo} by user {$user->id}. New version: {$newVersion->version_number}");

            return redirect()->route('wiki.show', $page->slug)
                ->with('flash', ['message' => ['type' => 'success', 'text' => '页面已恢复到版本 '.$versionNumberToRevertTo]]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error reverting page {$page->id} to version {$versionNumberToRevertTo} by user {$user->id}: ".$e->getMessage());

            return back()->withErrors(['general' => '恢复版本时出错，请稍后重试。']);
        }
    }

    public function showConflicts(WikiPage $page, DiffService $diffService): InertiaResponse|RedirectResponse
    {
        if ($page->status !== WikiPage::STATUS_CONFLICT) {
            Log::info("Attempted to show conflicts for page {$page->id} which is not in conflict.");

            return redirect()->route('wiki.show', $page->slug)
                ->with('flash', ['message' => ['type' => 'info', 'text' => '该页面当前没有冲突需要解决。']]);
        }

        $currentVersion = $page->currentVersion()->with('creator:id,name')->first();
        $conflictingVersion = $page->versions()
            ->where('is_current', false)
            ->orderBy('version_number', 'desc')
            ->with('creator:id,name')
            ->first();

        if (! $currentVersion || ! $conflictingVersion) {
            Log::error("Conflict resolution error for page {$page->id}: Missing current or conflicting version data.");

            return redirect()->route('wiki.show', $page->slug)
                ->withErrors(['general' => '无法加载冲突版本信息，请联系管理员。']);
        }

        $diffHtml = $diffService->generateDiffHtml($conflictingVersion->content, $currentVersion->content);

        return Inertia::render('Wiki/ShowConflicts', [
            'page' => $page->only('id', 'title', 'slug'),
            'conflictVersions' => [
                'current' => $currentVersion->only('id', 'version_number', 'content', 'comment', 'created_at', 'creator'),
                'conflict' => $conflictingVersion->only('id', 'version_number', 'content', 'comment', 'created_at', 'creator'),
            ],
            'diffHtml' => $diffHtml,
            'flash' => session('flash'),
        ]);
    }

    public function resolveConflict(Request $request, WikiPage $page): RedirectResponse
    {
        $user = Auth::user();

        if ($page->status !== WikiPage::STATUS_CONFLICT) {
            Log::info("Conflict resolution submitted for page {$page->id} which is not in conflict status. Redirecting.");

            return redirect()->route('wiki.show', $page->slug)
                ->with('flash', ['message' => ['type' => 'info', 'text' => '该页面当前没有冲突需要解决。']]);
        }

        $validated = $request->validate([
            'content' => 'required|string',
            'resolution_comment' => 'nullable|string|max:255',
        ]);

        DB::beginTransaction();
        try {
            $cleanContent = Purifier::clean($validated['content']);
            $latestVersionNumber = $page->versions()->where('wiki_page_id', $page->id)->latest('version_number')->value('version_number') ?? 0;

            $newVersion = WikiVersion::create([
                'wiki_page_id' => $page->id,
                'content' => $cleanContent,
                'created_by' => $user->id,
                'version_number' => $latestVersionNumber + 1,
                'comment' => $validated['resolution_comment'] ?: '解决编辑冲突',
                'is_current' => true,
            ]);

            WikiVersion::where('wiki_page_id', $page->id)
                ->where('id', '!=', $newVersion->id)
                ->update(['is_current' => false]);

            $page->update(['current_version_id' => $newVersion->id]);
            $page->markAsResolved($user);

            if (method_exists($page, 'logCustomActivity')) {
                $page->logCustomActivity('conflict_resolved', ['resolved_by' => $user->id, 'new_version' => $newVersion->version_number]);
            } else {
                ActivityLog::log('conflict_resolved', $page, ['resolved_by' => $user->id, 'new_version' => $newVersion->version_number]);
            }

            DB::commit();
            Log::info("Conflict for page {$page->id} resolved by user {$user->id}. New current version: {$newVersion->version_number}");

            return redirect()->route('wiki.show', $page->slug)
                ->with('flash', ['message' => ['type' => 'success', 'text' => '页面冲突已成功解决！']]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error resolving conflict for page {$page->id} by user {$user->id}: ".$e->getMessage()."\n".$e->getTraceAsString());

            return back()->withErrors(['general' => '解决冲突时发生内部错误，请稍后重试。'])->withInput();
        }
    }

    public function getEditors(WikiPage $page, CollaborationService $collaborationService): JsonResponse
    {
        $editors = $collaborationService->getEditors($page->id);

        return response()->json(['editors' => array_values($editors)]);
    }

    public function registerEditor(Request $request, WikiPage $page, CollaborationService $collaborationService): JsonResponse
    {
        $user = Auth::user();
        if (! $user) {
            return response()->json(['message' => '需要登录'], SymfonyResponse::HTTP_UNAUTHORIZED);
        }

        $collaborationService->registerEditor($page, $user);

        return response()->json(['success' => true, 'message' => '已注册为页面编辑者或心跳已更新']);
    }

    public function unregisterEditor(Request $request, WikiPage $page, CollaborationService $collaborationService): JsonResponse
    {
        $user = Auth::user();
        if (! $user) {
            return response()->json(['success' => true, 'message' => '用户未登录，无需注销']);
        }
        $collaborationService->unregisterEditor($page, $user);

        return response()->json(['success' => true, 'message' => '已注销编辑状态']);
    }

    public function getDiscussionMessages(WikiPage $page, CollaborationService $collaborationService): JsonResponse
    {
        $messages = $collaborationService->getDiscussionMessages($page->id);

        return response()->json(['messages' => $messages]);
    }

    public function sendDiscussionMessage(Request $request, WikiPage $page, CollaborationService $collaborationService): JsonResponse
    {
        $validated = $request->validate(['message' => 'required|string|max:500']);
        $user = Auth::user();
        if (! $user) {
            return response()->json(['success' => false, 'message' => '请先登录'], SymfonyResponse::HTTP_UNAUTHORIZED);
        }

        try {
            $message = $collaborationService->addDiscussionMessage($page, $user, $validated['message']);

            return response()->json(['success' => true, 'message' => $message]);
        } catch (\Exception $e) {
            Log::error("Error sending discussion message for page {$page->id} by user {$user->id}: ".$e->getMessage());

            return response()->json(['success' => false, 'message' => '发送消息失败'], SymfonyResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    protected function logActivity(string $action, Model $subject, ?array $properties = null): void
    {
        if (app()->runningInConsole()) {
            return;
        }

        try {
            ActivityLog::log($action, $subject, $properties);
        } catch (\Exception $e) {
            Log::error("Failed to log activity: Action={$action}, Subject={$subject->getTable()}:{$subject->getKey()}, Error: ".$e->getMessage());
        }
    }
}
