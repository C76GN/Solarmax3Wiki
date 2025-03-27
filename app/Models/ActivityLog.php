<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * 活动日志模型
 * 
 * 记录系统中各种用户活动，包括创建、更新、删除等操作
 * 支持多态关联到不同类型的主体对象
 */
class ActivityLog extends Model
{
    /**
     * 可批量赋值的属性
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
        'user_agent'
    ];

    /**
     * 属性的类型转换
     *
     * @var array<string, string>
     */
    protected $casts = [
        'properties' => 'array'
    ];

    /**
     * 获取创建日志的用户
     *
     * @return BelongsTo 用户关联
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 获取日志关联的主体对象（多态关联）
     *
     * @return MorphTo 主体对象关联
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * 获取操作类型的文本表示
     * 
     * 将操作代码转换为可读的中文描述
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
                ];
                return $actions[$attributes['action']] ?? $attributes['action'];
            }
        );
    }

    /**
     * 获取主体类型的文本表示
     * 
     * 将主体类型代码转换为可读的中文描述
     *
     * @return Attribute
     */
    protected function subjectTypeText(): Attribute
    {
        return Attribute::make(
            get: function ($value, array $attributes) {
                $types = [
                    'Template' => '模板',
                    'Page' => '页面',
                    'Role' => '角色',
                    'User' => '用户',
                ];
                return $types[$attributes['subject_type']] ?? $attributes['subject_type'];
            }
        );
    }

    /**
     * 创建一条新的活动日志
     * 
     * 记录用户、操作类型、操作主体和相关属性等信息
     *
     * @param string $action 操作类型
     * @param Model $subject 操作主体对象
     * @param array|null $properties 相关属性
     * @return static 创建的日志实例
     */
    public static function log(string $action, Model $subject, ?array $properties = null): self
    {
        $request = request();
        
        return static::create([
            'user_id' => $request->user()?->id,
            'action' => $action,
            'subject_type' => get_class($subject),
            'subject_id' => $subject->id,
            'properties' => $properties,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);
    }
}