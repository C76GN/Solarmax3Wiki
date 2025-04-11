<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use App\Models\WikiComment;
use App\Models\WikiPage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse; // 1. 引入 AuthorizesRequests Trait

class WikiCommentController extends Controller
{
    use AuthorizesRequests; // 2. 使用 AuthorizesRequests Trait

    /**
     * 存储新评论
     */
    public function store(Request $request, WikiPage $page): RedirectResponse|JsonResponse
    {
        // 可选：如果你在 Policy 中定义了 create 方法
        // $this->authorize('create', WikiComment::class);

        // 登录检查仍然是必要的，因为 Policy 可能假设用户已登录
        if (! Auth::check()) {
            return response()->json(['message' => '请先登录'], SymfonyResponse::HTTP_UNAUTHORIZED);
        }

        try {
            $validated = $request->validate([
                'content' => 'required|string',
                'parent_id' => ['nullable', 'exists:wiki_comments,id', function ($attribute, $value, $fail) use ($page) {
                    if ($value && ! WikiComment::where('id', $value)->where('wiki_page_id', $page->id)->exists()) {
                        $fail('回复的评论不存在或不属于当前页面。');
                    }
                }],
            ]);
        } catch (ValidationException $e) {
            // 让 Laravel 的异常处理器处理 Inertia 的验证错误返回
            throw $e;
        }

        try {
            /** @var User $user */ // 保留类型提示，好习惯
            $user = Auth::user();
            $comment = WikiComment::create([
                'wiki_page_id' => $page->id,
                'user_id' => $user->id,
                'parent_id' => $validated['parent_id'] ?? null,
                'content' => $validated['content'],
                'is_hidden' => false,
            ]);

            $this->logActivity('create', $comment);

            return redirect()->back()
                ->with('flash', [
                    'message' => [
                        'type' => 'success',
                        'text' => '评论发布成功！',
                    ],
                ]);
        } catch (\Exception $e) {
            Log::error("Error storing comment for page {$page->id}: ".$e->getMessage());

            if ($request->inertia() || $request->wantsJson()) {
                // 返回通用错误给Inertia
                return back()->withErrors(['general' => '评论发布失败，请稍后再试。'])->withInput();
            }

            return redirect()->back()
                ->with('flash', [
                    'message' => [
                        'type' => 'error',
                        'text' => '评论发布失败，请稍后再试。',
                    ],
                ])->withInput();
        }
    }

    /**
     * 更新评论
     */
    public function update(Request $request, WikiComment $comment): RedirectResponse|JsonResponse
    {
        // 3. 使用 Policy 进行授权检查
        // 如果未登录或无权限，这里会自动抛出 AuthorizationException
        $this->authorize('update', $comment);

        // --- 移除原有的手动权限检查 ---

        try {
            $validated = $request->validate([
                'content' => 'required|string',
            ]);
        } catch (ValidationException $e) {
            throw $e; // 让 Laravel 处理
        }

        try {
            $oldContent = $comment->content;
            $comment->update([
                'content' => $validated['content'],
            ]);

            // 记录活动日志，包含变更
            $this->logActivity('update', $comment, ['old_content' => $oldContent, 'new_content' => $validated['content']]);

            return redirect()->back()
                ->with('flash', [
                    'message' => [
                        'type' => 'success',
                        'text' => '评论更新成功！',
                    ],
                ]);
        } catch (\Exception $e) {
            Log::error("Error updating comment {$comment->id}: ".$e->getMessage());
            if ($request->inertia() || $request->wantsJson()) {
                return back()->withErrors(['general' => '评论更新失败，请稍后再试。'])->withInput();
            }

            return redirect()->back()
                ->with('flash', [
                    'message' => [
                        'type' => 'error',
                        'text' => '评论更新失败，请稍后再试。',
                    ],
                ])->withInput();
        }
    }

    /**
     * 隐藏评论 (软删除)
     */
    public function destroy(Request $request, WikiComment $comment): RedirectResponse|JsonResponse
    {
        // 4. 使用 Policy 进行授权检查
        $this->authorize('delete', $comment);

        // --- 移除原有的手动权限检查 ---

        try {
            $comment->update([
                'is_hidden' => true,
            ]);

            $this->logActivity('delete', $comment, ['hidden' => true]); // 记录为隐藏操作

            return redirect()->back()
                ->with('flash', [
                    'message' => [
                        'type' => 'success',
                        'text' => '评论已隐藏！',
                    ],
                ]);
        } catch (\Exception $e) {
            Log::error("Error deleting (hiding) comment {$comment->id}: ".$e->getMessage());
            if ($request->inertia() || $request->wantsJson()) {
                return back()->withErrors(['general' => '删除评论失败，请稍后再试。']);
            }

            return redirect()->back()
                ->with('flash', [
                    'message' => [
                        'type' => 'error',
                        'text' => '删除评论失败，请稍后再试。',
                    ],
                ]);
        }
    }

    // 日志记录辅助方法 (保持不变)
    protected function logActivity(string $action, Model $subject, ?array $properties = null): void
    {
        if (app()->runningInConsole() && ! app()->runningUnitTests()) {
            return;
        }
        try {
            ActivityLog::log($action, $subject, $properties);
        } catch (\Exception $e) {
            Log::error("Failed to log activity: Action={$action}, Subject={$subject->getTable()}:{$subject->getKey()}, Error: ".$e->getMessage());
        }
    }
}
