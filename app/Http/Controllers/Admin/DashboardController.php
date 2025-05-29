<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use App\Models\WikiCategory;
use App\Models\WikiPage;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;

/**
 * 后台仪表盘控制器
 *
 * 负责处理后台管理面板的请求，提供概览统计数据和最近的系统活动日志。
 * 此控制器通常需要通过权限中间件（如 `role:admin` 或 `permission:log.view`）
 * 来限制只有具备相应权限的用户才能访问。
 */
class DashboardController extends Controller
{
    /**
     * 显示后台仪表盘页面。
     *
     * 收集关键业务统计数据（如用户数、页面数、分类数）以及最近的活动日志，
     * 并将其传递给 Inertia 视图进行渲染。
     *
     * @return \Inertia\Response Inertia 响应，渲染 'Dashboard' 页面。
     */
    public function index()
    {
        // 统计各项数据以供仪表盘展示
        $stats = [
            'user_count' => User::count(), // 统计注册用户总数
            'page_count' => WikiPage::where('status', WikiPage::STATUS_PUBLISHED)->count(), // 统计已发布的 Wiki 页面总数
            'category_count' => WikiCategory::count(), // 统计 Wiki 分类总数
            // 根据需求可在此处添加更多统计指标
        ];

        // 查询最近的活动日志
        $recent_activities = ActivityLog::with('user:id,name') // 预加载关联的用户信息，仅获取 ID 和 name 字段以优化性能
            ->latest() // 按创建时间倒序排序，获取最新记录
            ->take(5) // 限制只获取最近的 5 条日志
            ->get() // 执行查询并获取结果集
            ->map(function ($log) { // 遍历日志记录并进行格式化处理
                return [
                    'id' => $log->id,
                    'user_name' => $log->user->name ?? '系统', // 操作用户名称，如果用户不存在则显示“系统”
                    'action_text' => $log->action_text, // 通过访问器获取操作的中文描述
                    'subject_type_text' => $log->subject_type_text, // 通过访问器获取操作对象类型的中文描述
                    'subject_id' => $log->subject_id, // 操作对象的 ID
                    'created_at_relative' => $log->created_at->diffForHumans(), // 格式化为相对时间（如“3小时前”）
                    'created_at' => $log->created_at->format('Y-m-d H:i:s'), // 格式化为精确时间
                    'subject_link' => $this->getSubjectLink($log->subject_type, $log->subject_id), // 尝试生成指向操作对象详情页面的链接
                ];
            });

        // 渲染 Inertia 页面 'Dashboard' 并传递统计数据和最近活动日志
        return Inertia::render('Dashboard', [
            'stats' => $stats, // 传递统计数据
            'recent_activities' => $recent_activities, // 传递格式化后的最近活动日志
            'is_admin' => true, // 标识当前是管理员仪表盘视图，用于前端判断显示内容
        ]);
    }

    /**
     * 根据活动日志的主题类型和ID，尝试生成一个可访问的链接。
     *
     * 该方法用于为活动日志中的“操作对象”提供一个可点击的链接，方便管理员快速跳转查看详情。
     *
     * @param string|null $type 活动日志主题的完整类名（例如 `App\Models\WikiPage`）。
     * @param int|null $id 活动日志主题的 ID。
     * @return string|null 如果成功生成链接则返回链接字符串，否则返回 null。
     */
    private function getSubjectLink(?string $type, ?int $id): ?string
    {
        // 如果类型或ID为空，则无法生成链接
        if (! $type || ! $id) {
            return null;
        }

        try {
            // 获取类名的短名称，以便进行更简洁的判断
            $shortType = class_basename($type);

            // 根据不同的模型类型生成相应的路由链接
            switch ($shortType) {
                case 'WikiPage':
                    // WikiPage 的详情页通常使用 slug 而不是 ID，这里无法直接生成链接。
                    // 如果需要，需要 ActivityLog 记录 slug 或通过 ID 查询 slug，目前暂不提供链接。
                    return null;
                case 'WikiCategory':
                    // WikiCategory 通常链接到其编辑页面或列表页
                    return route('wiki.categories.edit', $id);
                case 'WikiTag':
                    // WikiTag 通常链接到其列表页面，因为标签本身没有详情或编辑页
                    return route('wiki.tags.index');
                case 'WikiComment':
                    // WikiComment 属于某个 WikiPage，要链接到评论本身比较困难，通常链接到其所属页面。
                    // 但需要获取所属页面的 slug，此处无法直接获取，目前暂不提供链接。
                    return null;
                case 'User':
                    // 链接到用户的编辑页面
                    return route('users.edit', $id);
                case 'Role':
                    // 链接到角色的编辑页面
                    return route('roles.edit', $id);
                default:
                    // 对于其他未定义的类型，不生成链接
                    return null;
            }
        } catch (\Exception $e) {
            // 如果在生成路由时发生异常（例如路由不存在），记录警告日志
            Log::warning("为活动日志主题 {$type}:{$id} 生成链接时出错: ".$e->getMessage());

            return null;
        }
    }
}