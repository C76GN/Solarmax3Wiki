<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
* WikiPageReference 模型 - 管理 Wiki 页面之间的引用关系
*
* @property int $id 主键ID
* @property int $source_page_id 来源页面ID
* @property int $target_page_id 目标页面ID
* @property string|null $context 引用上下文内容
* @property \Carbon\Carbon $created_at 创建时间
* @property \Carbon\Carbon $updated_at 更新时间
* 
* @property-read \App\Models\WikiPage $sourcePage 引用的来源页面
* @property-read \App\Models\WikiPage $targetPage 被引用的目标页面
*/
class WikiPageReference extends Model
{
   /**
    * 可批量赋值的属性
    *
    * @var array<int, string>
    */
   protected $fillable = [
       'source_page_id',
       'target_page_id',
       'context'
   ];

   /**
    * 获取引用的来源页面
    *
    * @return BelongsTo
    */
   public function sourcePage(): BelongsTo
   {
       return $this->belongsTo(WikiPage::class, 'source_page_id');
   }

   /**
    * 获取被引用的目标页面
    *
    * @return BelongsTo
    */
   public function targetPage(): BelongsTo
   {
       return $this->belongsTo(WikiPage::class, 'target_page_id');
   }
}