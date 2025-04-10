<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use App\Models\WikiComment; // <-- 确保 User 模型被 use
use App\Models\WikiPage; // 如果使用了 logActivity
use Illuminate\Database\Eloquent\Model; // 如果使用了 logActivity 的基类
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse; // 引入 Log Facade

class WikiCommentController extends Controller
{
    /**
     * 存储新评论。
     */
    public function store(Request $request, WikiPage $page): RedirectResponse|JsonResponse
    {
        // 确保用户已登录
        if (! Auth::check()) {
            // 可以根据需要返回 JSON 或重定向
            return response()->json(['message' => '请先登录'], SymfonyResponse::HTTP_UNAUTHORIZED);
        }

        // 确认用户有评论权限
        $this->authorize('wiki.comment');

        $validated = $request->validate([
            'content' => 'required|string',
            'parent_id' => 'nullable|exists:wiki_comments,id', // 验证父评论 ID 是否存在
        ]);

        try {
            /** @var User $user */ // <-- 添加 PHPDoc 类型提示 (创建时用户肯定存在)
            $user = Auth::user();

            $comment = WikiComment::create([
                'wiki_page_id' => $page->id,
                'user_id' => $user->id,
                'parent_id' => $validated['parent_id'],
                'content' => $validated['content'],
                // is_hidden 默认为 false，无需在此设置
            ]);

            // 记录活动日志（可选）
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

            return redirect()->back()
                ->with('flash', [
                    'message' => [
                        'type' => 'error',
                        'text' => '评论发布失败，请稍后再试。',
                    ],
                ])->withInput(); // 保留输入内容
        }
    }

    /**
     * 更新评论。
     */
    public function update(Request $request, WikiComment $comment): RedirectResponse|JsonResponse
    {
        /** @var User|null $user */ // <-- 添加 PHPDoc 类型提示 (包括 null)
        $user = Auth::user();
        if (! $user) {
            return response()->json(['message' => '请先登录'], SymfonyResponse::HTTP_UNAUTHORIZED);
        }

        // 权限检查：只有评论作者或有特定权限的用户可以编辑
        if ($comment->user_id !== $user->id && ! $user->hasPermission('wiki.moderate_comments')) {
            // 返回 HTTP Forbidden 状态码
            return response()->json(['message' => '您无权编辑此评论'], SymfonyResponse::HTTP_FORBIDDEN);
        }

        $validated = $request->validate([
            'content' => 'required|string',
        ]);

        try {
            $comment->update([
                'content' => $validated['content'],
            ]);

            // 记录活动日志（可选）
            $this->logActivity('update', $comment);

            return redirect()->back()
                ->with('flash', [
                    'message' => [
                        'type' => 'success',
                        'text' => '评论更新成功！',
                    ],
                ]);
        } catch (\Exception $e) {
            Log::error("Error updating comment {$comment->id}: ".$e->getMessage());

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
     * “删除”（隐藏）评论。
     */
    public function destroy(WikiComment $comment): RedirectResponse|JsonResponse
    {
        /** @var User|null $user */ // <-- 添加 PHPDoc 类型提示 (包括 null)
        $user = Auth::user();
        if (! $user) {
            return response()->json(['message' => '请先登录'], SymfonyResponse::HTTP_UNAUTHORIZED);
        }

        // 权限检查：只有评论作者或有特定权限的用户可以删除
        if ($comment->user_id !== $user->id && ! $user->hasPermission('wiki.moderate_comments')) {
            return response()->json(['message' => '您无权删除此评论'], SymfonyResponse::HTTP_FORBIDDEN);
        }

        try {
            // 软删除，标记为隐藏
            $comment->update([
                'is_hidden' => true,
            ]);

            // 记录活动日志（可选）
            $this->logActivity('delete', $comment, ['hidden' => true]); // 可以记录下是隐藏操作

            return redirect()->back()
                ->with('flash', [
                    'message' => [
                        'type' => 'success',
                        'text' => '评论已隐藏！', // 修改提示信息更准确
                    ],
                ]);
        } catch (\Exception $e) {
            Log::error("Error deleting (hiding) comment {$comment->id}: ".$e->getMessage());

            return redirect()->back()
                ->with('flash', [
                    'message' => [
                        'type' => 'error',
                        'text' => '删除评论失败，请稍后再试。',
                    ],
                ]);
        }
    }

    /**
     * 统一记录活动日志的方法 (如果基类 Controller 没有，可以在这里实现)
     *
     * @param  string  $action  操作类型 (e.g., 'create', 'update', 'delete')
     * @param  Model  $subject  操作的对象
     * @param  array|null  $properties  额外的属性
     */
    protected function logActivity(string $action, Model $subject, ?array $properties = null): void
    {
        // 不在控制台运行时记录
        if (app()->runningInConsole()) {
            return;
        }

        try {
            // 使用 ActivityLog 模型提供的静态方法
            ActivityLog::log($action, $subject, $properties);
        } catch (\Exception $e) {
            // 记录日志失败时，在 Laravel 日志中记录错误，避免中断主流程
            Log::error("Failed to log activity: Action={$action}, Subject={$subject->getTable()}:{$subject->getKey()}, Error: ".$e->getMessage());
        }
    }
}
