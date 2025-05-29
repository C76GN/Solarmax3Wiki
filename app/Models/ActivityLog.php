<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Log;

/**
 * 活动日志模型
 *
 * 存储用户在系统中的操作记录，包括操作类型、操作对象、执行者及相关属性。
 */
class ActivityLog extends Model
{
    /**
     * 可批量赋值的属性。
     *
     * @var array<int, string>
     */
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
     * 模型属性的类型转换。
     *
     * @var array<string, string>
     */
    protected $casts = [
        'properties' => 'array', // 将 properties 字段自动转换为数组
    ];

    /**
     * 定义与 User 模型的关系。
     *
     * 一个活动日志属于一个用户。
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 定义多态关联，关联到具体的操作对象。
     *
     * 一个活动日志可以关联到不同类型的模型（例如：页面、分类等）。
     *
     * @return MorphTo
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * 获取操作类型的文本表示。
     *
     * 将存储在数据库中的操作代码转换为用户友好的中文描述。
     *
     * @return Attribute
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
     *
     * 将存储在数据库中的完整模型类名转换为用户友好的中文描述。
     *
     * @return Attribute
     */
    protected function subjectTypeText(): Attribute
    {
        return Attribute::make(
            get: function ($value, array $attributes) {
                $fullClassName = $attributes['subject_type'] ?? null;
                if (! $fullClassName) {
                    return '未知类型';
                }
                // 提取类名中的短名称（不包含命名空间）。
                $shortClassName = class_basename($fullClassName);

                $types = [
                    'WikiPage' => '页面',
                    'WikiCategory' => '分类',
                    'WikiTag' => '标签',
                    'WikiComment' => '评论',
                    'WikiVersion' => '版本',
                    'Role' => '角色',
                    'User' => '用户',
                ];

                return $types[$shortClassName] ?? $shortClassName; // 如果未定义映射，返回短类名本身。
            }
        );
    }

    /**
     * 静态方法，用于快速记录活动日志。
     * 该方法自动获取当前用户ID、IP地址和用户代理，并创建一条日志记录。
     * 适用于控制器、服务等需要记录用户操作的场景。
     * @param  string  $action  操作类型，如 'create', 'update', 'delete' 等。
     * @param  Model  $subject  被操作的对象实例（例如：一个 WikiPage 实例）。
     * @param  array|null  $properties  与操作相关的额外属性，如更改详情。
     * @return static|null 返回创建的活动日志模型实例，或在创建失败时返回 null。
     */
    public static function log(string $action, Model $subject, ?array $properties = null): ?self
    {
        // 在非控制台运行且非单元测试环境下尝试获取请求上下文信息。
        if (! app()->runningInConsole() || app()->runningUnitTests()) {
            try {
                $request = request();
                $userId = $request->user()?->id;
                $ip = $request->ip();
                $userAgent = $request->userAgent();
            } catch (\Exception $e) {
                // 当无法获取请求或用户信息时，记录警告并设置默认值。
                Log::warning("ActivityLog: Could not get request context. Action: {$action}, Subject: ".get_class($subject).":{$subject->id}. Error: ".$e->getMessage());
                $userId = null;
                $ip = null;
                $userAgent = null;
            }
        } else {
            // 在命令行环境下，用户ID、IP和用户代理设置为默认值。
            $userId = null;
            $ip = 'console';
            $userAgent = 'console';
        }
        try {
            return static::create([
                'user_id' => $userId,
                'action' => $action,
                'subject_type' => get_class($subject),
                'subject_id' => $subject->getKey(), // 使用 getKey() 获取模型主键，更具通用性。
                'properties' => $properties,
                'ip_address' => $ip,
                'user_agent' => $userAgent,
            ]);
        } catch (\Exception $e) {
            // 记录日志创建失败的错误。
            Log::error("Failed to create ActivityLog entry: {$e->getMessage()}");
            return null;
        }
    }
}