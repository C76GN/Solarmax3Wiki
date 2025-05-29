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
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * 维基评论控制器
 *
 * 负责处理维基页面评论的发布、更新和删除（隐藏）等操作。
 * 包含用户权限验证和操作日志记录。
 */
class WikiCommentController extends Controller
{
    use AuthorizesRequests; // 启用控制器中的授权功能。

    /**
     * 发布新的维基评论。
     *
     * 接收评论内容和父评论ID（如果为回复），并存储到数据库。
     * 进行登录检查、数据验证，并记录活动日志。
     *
     * @param Request $request HTTP请求实例。
     * @param WikiPage $page 评论所属的维基页面实例。
     * @return RedirectResponse|JsonResponse 重定向响应或JSON响应。
     */
    public function store(Request $request, WikiPage $page): RedirectResponse|JsonResponse
    {
        // 检查用户是否已认证。
        if (! Auth::check()) {
            return response()->json(['message' => '请先登录'], SymfonyResponse::HTTP_UNAUTHORIZED);
        }

        try {
            // 验证请求数据，包括评论内容和父评论ID的有效性。
            $validated = $request->validate([
                'content' => 'required|string',
                'parent_id' => ['nullable', 'exists:wiki_comments,id', function ($attribute, $value, $fail) use ($page) {
                    if ($value && ! WikiComment::where('id', $value)->where('wiki_page_id', $page->id)->exists()) {
                        $fail('回复的评论不存在或不属于当前页面。');
                    }
                }],
            ]);
        } catch (ValidationException $e) {
            // 验证失败，抛出异常交由框架处理。
            throw $e;
        }

        try {
            // 获取当前认证用户。
            $user = Auth::user();
            // 创建新的评论记录。
            $comment = WikiComment::create([
                'wiki_page_id' => $page->id,
                'user_id' => $user->id,
                'parent_id' => $validated['parent_id'] ?? null,
                'content' => $validated['content'],
                'is_hidden' => false, // 默认评论不隐藏。
            ]);

            // 记录评论创建活动。
            $this->logActivity('create', $comment);

            // 返回成功响应并重定向。
            return redirect()->back()
                ->with('flash', [
                    'message' => [
                        'type' => 'success',
                        'text' => '评论发布成功！',
                    ],
                ]);
        } catch (\Exception $e) {
            // 记录异常日志。
            Log::error("Error storing comment for page {$page->id}: ".$e->getMessage());

            // 根据请求类型返回错误响应。
            if ($request->inertia() || $request->wantsJson()) {
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
     * 更新指定维基评论的内容。
     *
     * 检查用户权限，验证内容，更新评论，并记录活动日志。
     *
     * @param Request $request HTTP请求实例。
     * @param WikiComment $comment 待更新的评论实例。
     * @return RedirectResponse|JsonResponse 重定向响应或JSON响应。
     */
    public function update(Request $request, WikiComment $comment): RedirectResponse|JsonResponse
    {
        // 授权检查：确保用户有权更新此评论。
        $this->authorize('update', $comment);

        try {
            // 验证评论内容。
            $validated = $request->validate([
                'content' => 'required|string',
            ]);
        } catch (ValidationException $e) {
            // 验证失败，抛出异常。
            throw $e;
        }

        try {
            // 保存旧评论内容以便记录日志。
            $oldContent = $comment->content;
            // 更新评论内容。
            $comment->update([
                'content' => $validated['content'],
            ]);

            // 记录评论更新活动。
            $this->logActivity('update', $comment, ['old_content' => $oldContent, 'new_content' => $validated['content']]);

            // 返回成功响应。
            return redirect()->back()
                ->with('flash', [
                    'message' => [
                        'type' => 'success',
                        'text' => '评论更新成功！',
                    ],
                ]);
        } catch (\Exception $e) {
            // 记录异常日志。
            Log::error("Error updating comment {$comment->id}: ".$e->getMessage());

            // 根据请求类型返回错误响应。
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
     * 隐藏（软删除）指定的维基评论。
     *
     * 检查用户权限，并将评论的 `is_hidden` 字段设为 `true`。
     * 记录隐藏操作的活动日志。
     *
     * @param Request $request HTTP请求实例。
     * @param WikiComment $comment 待隐藏的评论实例。
     * @return RedirectResponse|JsonResponse 重定向响应或JSON响应。
     */
    public function destroy(Request $request, WikiComment $comment): RedirectResponse|JsonResponse
    {
        // 授权检查：确保用户有权删除（隐藏）此评论。
        $this->authorize('delete', $comment);

        try {
            // 将评论标记为隐藏。
            $comment->update([
                'is_hidden' => true,
            ]);

            // 记录评论隐藏活动。
            $this->logActivity('delete', $comment, ['hidden' => true]);

            // 返回成功响应。
            return redirect()->back()
                ->with('flash', [
                    'message' => [
                        'type' => 'success',
                        'text' => '评论已隐藏！',
                    ],
                ]);
        } catch (\Exception $e) {
            // 记录异常日志。
            Log::error("Error deleting (hiding) comment {$comment->id}: ".$e->getMessage());

            // 根据请求类型返回错误响应。
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

    /**
     * 记录操作到活动日志。
     *
     * 辅助方法，用于统一记录各种操作到活动日志系统。
     * 在控制台环境和单元测试中会跳过日志记录。
     *
     * @param string $action 执行的操作类型。
     * @param Model $subject 操作涉及的模型实例。
     * @param array|null $properties 与操作相关的额外属性。
     * @return void
     */
    protected function logActivity(string $action, Model $subject, ?array $properties = null): void
    {
        // 如果在控制台运行且不是单元测试，则跳过日志记录。
        if (app()->runningInConsole() && ! app()->runningUnitTests()) {
            return;
        }

        try {
            // 调用 ActivityLog 模型的静态方法记录日志。
            ActivityLog::log($action, $subject, $properties);
        } catch (\Exception $e) {
            // 记录日志失败时的错误信息。
            Log::error("Failed to log activity: Action={$action}, Subject={$subject->getTable()}:{$subject->getKey()}, Error: ".$e->getMessage());
        }
    }
}