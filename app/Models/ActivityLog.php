<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Log; // 引入 Log facade

class ActivityLog extends Model
{
    protected $fillable = [
        'user_id',
        'action',
        'subject_type',
        'subject_id',
        'properties',
        'ip_address',
        'user_agent',
    ];

    /**
     * 定义属性转换
     *
     * @var array
     */
    protected $casts = [
        'properties' => 'array',
    ];

    /**
     * 定义与 User 模型的关系。
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 定义多态关联，关联到具体的操作对象。
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * 获取操作类型的文本表示。
     */
    protected function actionText(): Attribute
    {
        return Attribute::make(
            get: function ($value, array $attributes) {
                $actions = [
                    'create' => '创建',
                    'update' => '更新',
                    'delete' => '删除',
                    'publish' => '发布',
                    'unpublish' => '取消发布',
                    'revert' => '恢复版本',
                    'conflict_detected' => '检测到冲突',
                    'conflict_resolved' => '解决冲突',
                    'lock' => '锁定页面',
                    'unlock' => '解锁页面',
                    'editor_active' => '编辑活动', // 用于协作服务
                ];

                return $actions[$attributes['action']] ?? $attributes['action'];
            }
        );
    }

    /**
     * 获取操作对象类型的文本表示。
     */
    protected function subjectTypeText(): Attribute
    {
        return Attribute::make(
            get: function ($value, array $attributes) {
                // 从 $attributes['subject_type'] 获取类名
                $fullClassName = $attributes['subject_type'] ?? null;
                if (! $fullClassName) {
                    return '未知类型';
                }
                // 提取短类名
                $shortClassName = class_basename($fullClassName);

                $types = [
                    // 移除 'Template' => '模板',
                    'WikiPage' => '页面', // 使用模型类名
                    'WikiCategory' => '分类',
                    'WikiTag' => '标签',
                    'WikiComment' => '评论',
                    'WikiVersion' => '版本',
                    'Role' => '角色',
                    'User' => '用户',
                ];

                return $types[$shortClassName] ?? $shortClassName; // 如果未定义，返回短类名
            }
        );
    }

    /**
     * 静态方法，用于快速记录日志。
     *
     * @param  string  $action  操作类型 (e.g., 'create', 'update')
     * @param  Model  $subject  操作对象
     * @param  array|null  $properties  额外属性 (e.g., 更改详情)
     * @return static|null 返回创建的日志记录或null（如果失败）
     */
    public static function log(string $action, Model $subject, ?array $properties = null): ?self
    {
        // 确保在测试等环境中 request() 和 auth() 可用
        if (! app()->runningInConsole() || app()->runningUnitTests()) { // 在测试中也可能需要记录
            try {
                $request = request(); // 获取当前请求
                $userId = $request->user()?->id; // 获取当前登录用户ID
                $ip = $request->ip();
                $userAgent = $request->userAgent();
            } catch (\Exception $e) {
                // 在无法获取 request 或 user 的场景下（例如队列、命令行的非用户触发操作）
                Log::warning("ActivityLog: Could not get request context. Action: {$action}, Subject: ".get_class($subject).":{$subject->id}. Error: ".$e->getMessage());
                $userId = null; // 或者根据业务逻辑设置系统用户ID
                $ip = null;
                $userAgent = null;
            }
        } else {
            // 命令行环境下的处理，可能没有 request 或 user
            $userId = null; // 明确设置为 null 或系统用户 ID
            $ip = 'console';
            $userAgent = 'console';
        }

        try {
            return static::create([
                'user_id' => $userId,
                'action' => $action,
                'subject_type' => get_class($subject),
                'subject_id' => $subject->getKey(), // 使用 getKey() 更健壮
                'properties' => $properties,
                'ip_address' => $ip,
                'user_agent' => $userAgent,
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to create ActivityLog entry: {$e->getMessage()}");

            return null;
        }
    }
}
