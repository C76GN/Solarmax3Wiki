<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
* WikiPageRevision 模型 - 管理 Wiki 页面的修订历史
*
* @property int $id 主键ID
* @property int $wiki_page_id 关联的Wiki页面ID
* @property string $title 修订版本的标题
* @property string $content 修订版本的内容
* @property string|null $comment 修订说明
* @property int $created_by 创建者用户ID
* @property array|null $changes 内容变更记录
* @property int $version 版本号
* @property \Carbon\Carbon $created_at 创建时间
* @property \Carbon\Carbon $updated_at 更新时间
* 
* @property-read \App\Models\WikiPage $page 关联的Wiki页面
* @property-read \App\Models\User $creator 创建此修订版本的用户
*/
class WikiPageRevision extends Model
{
   /**
    * 可批量赋值的属性
    *
    * @var array<int, string>
    */
   protected $fillable = [
       'wiki_page_id',
       'title',
       'content',
       'comment',
       'created_by',
       'changes',
       'version'
   ];

   /**
    * 需要类型转换的属性
    *
    * @var array<string, string>
    */
   protected $casts = [
       'changes' => 'json'
   ];

   /**
    * 获取关联的Wiki页面
    *
    * @return BelongsTo
    */
   public function page(): BelongsTo
   {
       return $this->belongsTo(WikiPage::class, 'wiki_page_id');
   }

   /**
    * 获取创建此修订版本的用户
    *
    * @return BelongsTo
    */
   public function creator(): BelongsTo
   {
       return $this->belongsTo(User::class, 'created_by');
   }

   /**
    * 计算与上一版本内容的差异
    *
    * @param string|null $previousContent 上一版本的内容
    * @return array 变更记录
    */
   public function calculateChanges($previousContent): array
   {
       // 如果没有上一版本内容，则视为创建操作
       if (!$previousContent) {
           return ['type' => 'create', 'content' => $this->content];
       }
       
       $changes = [];
       
       // 按行分割内容进行比较
       $oldLines = explode("\n", $previousContent);
       $newLines = explode("\n", $this->content);
       
       // 计算新增和删除的行
       $changes['added'] = array_values(array_diff($newLines, $oldLines));
       $changes['removed'] = array_values(array_diff($oldLines, $newLines));
       
       return $changes;
   }
}