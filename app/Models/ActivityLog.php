<?php
// FileName: /var/www/Solarmax3Wiki/app/Models/ActivityLog.php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class ActivityLog extends Model
{
    protected $fillable = [
        'user_id',
        'action',
        'subject_type',
        'subject_id',
        'properties',
        'ip_address',
        'user_agent'
    ];

    protected $casts = [
        'properties' => 'array'
    ];

    // 用户关联
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // 多态关联到操作对象
    public function subject()
    {
        return $this->morphTo();
    }

    // 获取格式化的操作类型
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

    // 获取格式化的对象类型
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

    // 记录日志的静态方法
    public static function log($action, $subject, $properties = null)
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