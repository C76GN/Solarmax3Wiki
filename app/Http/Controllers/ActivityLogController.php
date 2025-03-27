<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * 活动日志控制器
 * 
 * 负责处理系统活动日志的查询和展示功能
 */
class ActivityLogController extends Controller
{
    /**
     * 显示活动日志列表
     * 
     * 根据请求的过滤条件获取活动日志，并通过Inertia进行渲染
     *
     * @param Request $request 请求对象，包含过滤参数
     * @return Response
     */
    public function index(Request $request): Response
    {
        // 构建基础查询，预加载用户信息
        $query = ActivityLog::with('user')->latest();
        
        // 应用各种过滤条件
        $this->applyFilters($query, $request);
        
        // 分页并格式化日志数据
        $logs = $query->paginate(15)
            ->through(function ($log) {
                return $this->formatLogData($log);
            });
        
        // 返回Inertia视图
        return Inertia::render('ActivityLogs/Index', [
            'logs' => $logs,
            'filters' => $request->only(['action', 'subject_type', 'start_date', 'end_date', 'user_id'])
        ]);
    }
    
    /**
     * 应用查询过滤条件
     *
     * @param \Illuminate\Database\Eloquent\Builder $query 查询构建器
     * @param Request $request 请求对象
     * @return void
     */
    private function applyFilters($query, Request $request): void
    {
        // 按操作类型过滤
        if ($request->has('action')) {
            $query->where('action', $request->action);
        }
        
        // 按对象类型过滤
        if ($request->has('subject_type')) {
            $query->where('subject_type', $request->subject_type);
        }
        
        // 按开始日期过滤
        if ($request->has('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        
        // 按结束日期过滤
        if ($request->has('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }
        
        // 按用户过滤
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }
    }
    
    /**
     * 格式化日志数据以便前端展示
     *
     * @param ActivityLog $log 日志对象
     * @return array 格式化后的日志数据
     */
    private function formatLogData(ActivityLog $log): array
    {
        return [
            'id' => $log->id,
            'user' => $log->user ? [
                'name' => $log->user->name,
                'email' => $log->user->email
            ] : null,
            'action' => $log->action,
            'action_text' => $log->action_text,
            'subject_type' => $log->subject_type,
            'subject_type_text' => $log->subject_type_text,
            'subject_id' => $log->subject_id,
            'properties' => $log->properties,
            'ip_address' => $log->ip_address,
            'created_at' => $log->created_at->format('Y-m-d H:i:s'),
        ];
    }
}