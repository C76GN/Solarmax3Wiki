<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Builder;
use App\Traits\LogsActivity;
use App\Services\WikiContentService;
use Carbon\Carbon;

/**
 * Wiki页面模型
 * 
 * 表示Wiki系统中的页面，包含内容、版本控制、分类关联、引用关系等功能
 * 支持软删除和活动日志记录
 */
class WikiPage extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    /**
     * 页面状态常量
     */
    const STATUS_DRAFT = 'draft';
    const STATUS_PENDING = 'pending';
    const STATUS_PUBLISHED = 'published';
    const STATUS_AUDIT_FAILURE = 'audit_failure';

    /**
     * 可批量赋值的属性
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',           // 页面标题
        'slug',            // URL友好的页面标识
        'content',         // 页面内容
        'status',          // 页面状态
        'created_by',      // 创建者ID
        'last_edited_by',  // 最后编辑者ID
        'published_at',    // 发布时间
        'view_count',      // 浏览次数
        'current_version', // 当前版本号
        'status_message',  // 状态消息（如审核失败原因）
    ];

    /**
     * 属性的类型转换
     *
     * @var array<string, string>
     */
    protected $casts = [
        'published_at' => 'datetime',
        'view_count' => 'integer',
        'current_version' => 'integer',
    ];
    
    /**
     * 获取所有可用的页面状态
     *
     * @return array<string, string> 状态标识符和对应的中文名称
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_DRAFT => '草稿',
            self::STATUS_PENDING => '等待审核',
            self::STATUS_PUBLISHED => '已发布',
            self::STATUS_AUDIT_FAILURE => '审核失败',
        ];
    }

    /**
     * 获取页面创建者
     *
     * @return BelongsTo 创建者关联
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * 获取页面最后编辑者
     *
     * @return BelongsTo 最后编辑者关联
     */
    public function lastEditor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_edited_by');
    }

    /**
     * 限制查询仅包含已发布的页面
     *
     * @param Builder $query 查询构建器
     * @return Builder 修改后的查询构建器
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PUBLISHED)
                    ->whereNotNull('published_at');
    }

    /**
     * 增加页面浏览次数
     *
     * @return void
     */
    public function incrementViewCount(): void
    {
        $this->increment('view_count');
    }

    /**
     * 获取页面所属的分类
     *
     * @return BelongsToMany 分类关联
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(WikiCategory::class, 'wiki_page_category');
    }

    /**
     * 获取页面的所有历史版本
     *
     * @return HasMany 版本关联
     */
    public function revisions(): HasMany
    {
        return $this->hasMany(WikiPageRevision::class, 'wiki_page_id')->orderBy('version', 'desc');
    }
    
    /**
     * 获取页面相关的问题报告
     *
     * @return HasMany 问题报告关联
     */
    public function issues(): HasMany
    {
        return $this->hasMany(WikiPageIssue::class, 'wiki_page_id');
    }

    /**
     * 创建页面的新版本
     *
     * @param string|null $comment 版本备注
     * @return WikiPageRevision 创建的版本
     */
    public function createRevision(?string $comment = null): WikiPageRevision
    {
        // 计算新版本号
        $latestVersion = $this->current_version + 1;
        
        // 获取之前的版本内容（用于计算变更）
        $previousRevision = $this->revisions()->latest('version')->first();
        $previousContent = $previousRevision ? $previousRevision->content : null;
        
        // 创建新版本
        $revision = $this->revisions()->create([
            'title' => $this->title,
            'content' => $this->content,
            'comment' => $comment,
            'created_by' => auth()->id(),
            'version' => $latestVersion
        ]);
        
        // 计算与上一版本的变更
        $revision->changes = $revision->calculateChanges($previousContent);
        $revision->save();
        
        // 更新页面的当前版本号
        $this->update(['current_version' => $latestVersion]);
        
        return $revision;
    }

    /**
     * 将页面回滚到指定版本
     *
     * @param int $version 要回滚到的版本号
     * @return WikiPageRevision 创建的新版本
     */
    public function revertToVersion(int $version): WikiPageRevision
    {
        // 获取指定版本
        $revision = $this->revisions()->where('version', $version)->firstOrFail();
        
        // 使用该版本的内容更新页面
        $this->update([
            'title' => $revision->title,
            'content' => $revision->content,
            'last_edited_by' => auth()->id()
        ]);
        
        // 创建新版本并记录回滚信息
        return $this->createRevision("Reverted to version {$version}");
    }

    /**
     * 更新页面的引用关系
     * 
     * 从页面内容中解析出Wiki链接，并创建对应的引用关系
     *
     * @return void
     */
    public function updateReferences(): void
    {
        $contentService = app(WikiContentService::class);
        
        // 删除现有的引用关系
        $this->outgoingReferences()->delete();
        
        // 解析页面内容中的Wiki链接
        $links = $contentService->parseWikiLinks($this->content);
        
        // 为每个链接创建引用关系
        foreach ($links as $title) {
            $referencedPage = self::where('title', $title)->first();
            
            // 只为存在的页面创建引用，且不包括自引用
            if ($referencedPage && $referencedPage->id !== $this->id) {
                WikiPageReference::create([
                    'source_page_id' => $this->id,
                    'target_page_id' => $referencedPage->id,
                    'context' => $contentService->extractReferenceContext($this->content, $title)
                ]);
            }
        }
    }

    /**
     * 获取当前页面引用的其他页面
     *
     * @return HasMany 出站引用关联
     */
    public function outgoingReferences(): HasMany
    {
        return $this->hasMany(WikiPageReference::class, 'source_page_id');
    }

    /**
     * 获取引用当前页面的其他页面
     *
     * @return HasMany 入站引用关联
     */
    public function incomingReferences(): HasMany
    {
        return $this->hasMany(WikiPageReference::class, 'target_page_id');
    }

    /**
     * 获取当前页面引用的页面集合
     *
     * @return BelongsToMany 引用页面关联
     */
    public function referencedPages(): BelongsToMany
    {
        return $this->belongsToMany(
            WikiPage::class,
            'wiki_page_references',
            'source_page_id',
            'target_page_id'
        )->withTimestamps();
    }

    /**
     * 获取引用当前页面的页面集合
     *
     * @return BelongsToMany 引用当前页面的页面关联
     */
    public function referencedByPages(): BelongsToMany
    {
        return $this->belongsToMany(
            WikiPage::class,
            'wiki_page_references',
            'target_page_id',
            'source_page_id'
        )->withTimestamps();
    }

    /**
     * 获取关注此页面的用户
     *
     * @return BelongsToMany 关注用户关联
     */
    public function followers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'wiki_page_follows')
            ->withTimestamps();
    }

    /**
     * 检查指定用户是否关注了此页面
     *
     * @param int|null $userId 用户ID
     * @return bool 是否关注
     */
    public function isFollowedByUser(?int $userId): bool
    {
        if (!$userId) return false;
        return $this->followers()->where('user_id', $userId)->exists();
    }

    /**
     * 获取与当前页面相关的页面
     * 
     * 相关页面包括：
     * 1. 具有相同分类的页面
     * 2. 引用了引用当前页面的页面的页面
     *
     * @param int $limit 返回结果数量限制
     * @return Collection 相关页面集合
     */
    public function getRelatedPages(int $limit = 5): Collection
    {
        return WikiPage::where('id', '!=', $this->id)
            ->where(function ($query) {
                // 同分类页面
                $query->whereHas('categories', function ($q) {
                    $q->whereIn('wiki_categories.id', $this->categories->pluck('id'));
                })
                // 或通过引用关系相关联的页面
                ->orWhereIn('id', function ($q) {
                    $q->select('target_page_id')
                        ->from('wiki_page_references')
                        ->whereIn('source_page_id', $this->referencedByPages->pluck('id'));
                });
            })
            // 统计相关程度（共同分类数和被引用次数）
            ->withCount([
                'incomingReferences',
                'categories' => function ($query) {
                    $query->whereIn('wiki_categories.id', $this->categories->pluck('id'));
                }
            ])
            // 按相关程度排序
            ->orderByDesc('categories_count')
            ->orderByDesc('incoming_references_count')
            ->limit($limit)
            ->get();
    }
    
    /**
     * 获取格式化的创建时间
     *
     * @return string|null 格式化的创建时间
     */
    public function getFormattedCreatedAtAttribute(): ?string
    {
        return $this->created_at ? Carbon::parse($this->created_at)->format('Y-m-d H:i:s') : null;
    }

    /**
     * 获取格式化的更新时间
     *
     * @return string|null 格式化的更新时间
     */
    public function getFormattedUpdatedAtAttribute(): ?string
    {
        return $this->updated_at ? Carbon::parse($this->updated_at)->format('Y-m-d H:i:s') : null;
    }

    /**
     * 获取格式化的发布时间
     *
     * @return string|null 格式化的发布时间
     */
    public function getFormattedPublishedAtAttribute(): ?string
    {
        return $this->published_at ? Carbon::parse($this->published_at)->format('Y-m-d H:i:s') : null;
    }

    /**
     * 获取状态的中文文本表示
     *
     * @return string 状态文本
     */
    public function getStatusTextAttribute(): string
    {
        return self::getStatuses()[$this->status] ?? $this->status;
    }
}