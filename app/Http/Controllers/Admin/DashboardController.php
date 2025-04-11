<?php

namespace App\Http\Controllers\Admin; // 注意命名空间

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use App\Models\WikiCategory;
use App\Models\WikiPage;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        // 确保只有管理员能访问 (如果需要)
        // 使用 'role:admin' 中间件 或 在这里检查权限
        // if (!request()->user()->hasRole('admin')) {
        //     abort(403);
        // }

        $stats = [
            'user_count' => User::count(),
            'page_count' => WikiPage::where('status', WikiPage::STATUS_PUBLISHED)->count(),
            'category_count' => WikiCategory::count(),
            // 可以添加更多统计信息...
        ];

        $recent_activities = ActivityLog::with('user:id,name')
            ->latest()
            ->take(5) // 获取最近 5 条
            ->get()
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'user_name' => $log->user->name ?? '系统',
                    'action_text' => $log->action_text, // 使用访问器
                    'subject_type_text' => $log->subject_type_text, // 使用访问器
                    'subject_id' => $log->subject_id,
                    'created_at_relative' => $log->created_at->diffForHumans(), // 相对时间
                    'created_at' => $log->created_at->format('Y-m-d H:i:s'), // 精确时间
                    // 尝试生成对象链接
                    'subject_link' => $this->getSubjectLink($log->subject_type, $log->subject_id),
                ];
            });

        // 如果用户不是管理员，可能只想显示欢迎信息
        // if (!request()->user()->hasRole('admin')) {
        //    return Inertia::render('Dashboard', ['is_admin' => false]);
        // }

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'recent_activities' => $recent_activities,
            'is_admin' => true, // 假设当前就是管理员仪表盘
        ]);
    }

    /**
     * 尝试生成指向活动日志主题的链接
     */
    private function getSubjectLink(?string $type, ?int $id): ?string
    {
        if (! $type || ! $id) {
            return null;
        }

        try {
            $shortType = class_basename($type);
            switch ($shortType) {
                case 'WikiPage':
                    // 注意：WikiPage 显示路由用的是 slug，这里只有 id，可能无法直接链接
                    // 如果需要链接，需要修改 ActivityLog 记录更多信息或通过 id 查找 slug
                    // 暂时返回 null 或管理页面的链接 (如果存在)
                    // $page = WikiPage::find($id);
                    // return $page ? route('wiki.show', $page->slug) : null;
                    return null; // 暂不链接页面
                case 'WikiCategory':
                    // 分类通常没有 'show' 页面，链接到编辑页或列表页
                    return route('wiki.categories.edit', $id); // 假设有编辑页
                case 'WikiTag':
                    // 标签通常没有 'show' 页面，链接到列表页
                    return route('wiki.tags.index'); // 假设链接到列表页
                case 'WikiComment':
                    // 评论通常属于某个页面，链接到页面可能更合适，但需要知道页面 slug
                    return null; // 暂不链接评论
                case 'User':
                    return route('users.edit', $id); // 假设有用户编辑页
                case 'Role':
                    return route('roles.edit', $id); // 假设有角色编辑页
                default:
                    return null;
            }
        } catch (\Exception $e) {
            // 路由不存在或其他错误
            \Illuminate\Support\Facades\Log::warning("Error generating subject link for {$type}:{$id} - ".$e->getMessage());

            return null;
        }
    }
}
