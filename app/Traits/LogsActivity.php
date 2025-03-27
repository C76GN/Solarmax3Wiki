<?php

namespace App\Traits;

use App\Models\ActivityLog;

/**
* 日志记录特性
* 
* 使模型能够记录创建、更新和删除操作的活动日志。
* 添加此特性到模型中，可以自动跟踪模型的生命周期事件。
*/
trait LogsActivity
{
   /**
    * 引导特性，注册模型生命周期的事件监听器
    *
    * @return void
    */
   protected static function bootLogsActivity()
   {
       // 监听模型创建事件
       static::created(function ($model) {
           static::logActivity('create', $model);
       });

       // 监听模型更新事件
       static::updated(function ($model) {
           $changes = $model->getChanges();
           // 忽略仅更新时间戳的变更
           unset($changes['updated_at']);
           
           // 只记录有实际变更的更新
           if (count($changes) > 0) {
               static::logActivity('update', $model, [
                   'old' => array_intersect_key($model->getOriginal(), $changes),
                   'new' => $changes
               ]);
           }
       });

       // 监听模型删除事件
       static::deleted(function ($model) {
           static::logActivity('delete', $model);
       });
   }

   /**
    * 记录模型活动
    *
    * @param string $action 活动类型（create, update, delete等）
    * @param mixed $model 相关模型实例
    * @param mixed|null $properties 要记录的额外属性
    * @return mixed|null 日志记录结果
    */
   protected static function logActivity($action, $model, $properties = null)
   {
       // 检查模型是否设置了要跳过记录的动作
       if (isset($model->skipLogging) && in_array($action, $model->skipLogging)) {
           return;
       }
       
       // 调用ActivityLog类的log方法记录活动
       return ActivityLog::log($action, $model, $properties);
   }

   /**
    * 记录自定义活动
    * 
    * 允许开发者记录标准生命周期事件之外的自定义活动
    *
    * @param string $action 活动类型
    * @param mixed|null $properties 要记录的额外属性
    * @return mixed|null 日志记录结果
    */
   public function logCustomActivity($action, $properties = null)
   {
       return static::logActivity($action, $this, $properties);
   }
}