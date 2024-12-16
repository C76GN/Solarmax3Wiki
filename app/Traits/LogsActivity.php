<?php
// FileName: /var/www/Solarmax3Wiki/app/Traits/LogsActivity.php


namespace App\Traits;

use App\Models\ActivityLog;

trait LogsActivity
{
    protected static function bootLogsActivity()
    {
        // 创建事件
        static::created(function ($model) {
            static::logActivity('create', $model);
        });

        // 更新事件
        static::updated(function ($model) {
            $changes = $model->getChanges();
            // 排除updated_at字段
            unset($changes['updated_at']);
            
            if (count($changes) > 0) {
                static::logActivity('update', $model, [
                    'old' => array_intersect_key($model->getOriginal(), $changes),
                    'new' => $changes
                ]);
            }
        });

        // 删除事件
        static::deleted(function ($model) {
            static::logActivity('delete', $model);
        });
    }

    protected static function logActivity($action, $model, $properties = null)
    {
        // 如果模型不想记录某些操作，可以通过该属性控制
        if (isset($model->skipLogging) && in_array($action, $model->skipLogging)) {
            return;
        }

        ActivityLog::log($action, $model, $properties);
    }

    // 自定义日志记录
    public function logCustomActivity($action, $properties = null)
    {
        return static::logActivity($action, $this, $properties);
    }
}