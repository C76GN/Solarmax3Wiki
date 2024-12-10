<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $query = ActivityLog::with('user')
            ->latest();

        // 按操作类型筛选
        if ($request->has('action')) {
            $query->where('action', $request->action);
        }

        // 按对象类型筛选
        if ($request->has('subject_type')) {
            $query->where('subject_type', $request->subject_type);
        }

        // 按日期范围筛选
        if ($request->has('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->has('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        // 按用户筛选
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $logs = $query->paginate(15)
            ->through(function ($log) {
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
            });

        return Inertia::render('ActivityLogs/Index', [
            'logs' => $logs,
            'filters' => $request->all(['action', 'subject_type', 'start_date', 'end_date', 'user_id'])
        ]);
    }
}