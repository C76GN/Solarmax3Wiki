<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivity;

class WikiPage extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'title',
        'slug',
        'content',
        'status',
        'created_by',
        'last_edited_by',
        'published_at',
        'view_count',
        'current_version'
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'view_count' => 'integer'
    ];

    // 关联到创建者
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // 关联到最后编辑者
    public function lastEditor()
    {
        return $this->belongsTo(User::class, 'last_edited_by');
    }

    // 范围查询：已发布的页面
    public function scopePublished($query)
    {
        return $query->where('status', 'published')
                    ->whereNotNull('published_at');
    }

    // 增加浏览次数
    public function incrementViewCount()
    {
        $this->increment('view_count');
    }

    public function categories()
    {
        return $this->belongsToMany(WikiCategory::class, 'wiki_page_category');
    }

    public function revisions()
    {
        return $this->hasMany(WikiPageRevision::class)->orderBy('version', 'desc');
    }

    public function createRevision($comment = null)
    {
        // 获取最新版本号
        $latestVersion = $this->current_version + 1;

        // 获取前一个版本的内容
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

        // 计算并保存变更记录
        $revision->changes = $revision->calculateChanges($previousContent);
        $revision->save();

        // 更新当前版本号
        $this->update(['current_version' => $latestVersion]);

        return $revision;
    }

    public function revertToVersion($version)
    {
        $revision = $this->revisions()->where('version', $version)->firstOrFail();
        
        $this->update([
            'title' => $revision->title,
            'content' => $revision->content,
            'last_edited_by' => auth()->id()
        ]);

        return $this->createRevision("Reverted to version {$version}");
    }

    public function getRevisionDiff($fromVersion, $toVersion)
    {
        $fromRevision = $this->revisions()->where('version', $fromVersion)->firstOrFail();
        $toRevision = $this->revisions()->where('version', $toVersion)->firstOrFail();

        // 这里可以使用更复杂的diff算法
        return [
            'from' => [
                'version' => $fromVersion,
                'content' => $fromRevision->content
            ],
            'to' => [
                'version' => $toVersion,
                'content' => $toRevision->content
            ],
            'changes' => $toRevision->changes
        ];
    }

    public function followers()
    {
        return $this->belongsToMany(User::class, 'wiki_page_follows')
            ->withTimestamps();
    }

    public function isFollowedByUser($userId)
    {
        return $this->followers()->where('user_id', $userId)->exists();
    }

    public function outgoingReferences()
    {
        return $this->hasMany(WikiPageReference::class, 'source_page_id');
    }

    public function incomingReferences()
    {
        return $this->hasMany(WikiPageReference::class, 'target_page_id');
    }

    public function referencedPages()
    {
        return $this->belongsToMany(
            WikiPage::class,
            'wiki_page_references',
            'source_page_id',
            'target_page_id'
        )->withTimestamps();
    }

    public function referencedByPages()
    {
        return $this->belongsToMany(
            WikiPage::class,
            'wiki_page_references',
            'target_page_id',
            'source_page_id'
        )->withTimestamps();
    }

    // 自动解析内容中的页面引用并更新引用关系
    public function updateReferences()
    {
        // 清除旧的引用关系
        $this->outgoingReferences()->delete();
        
        // 解析内容中的Wiki链接
        preg_match_all('/\[\[([^\]]+)\]\]/', $this->content, $matches);
        
        if (!empty($matches[1])) {
            foreach ($matches[1] as $title) {
                // 查找被引用的页面
                $referencedPage = WikiPage::where('title', $title)->first();
                
                // 如果页面存在，创建引用关系
                if ($referencedPage && $referencedPage->id !== $this->id) {
                    WikiPageReference::create([
                        'source_page_id' => $this->id,
                        'target_page_id' => $referencedPage->id,
                        'context' => $this->extractReferenceContext($title)
                    ]);
                }
            }
        }
    }

    // 提取引用的上下文内容
    protected function extractReferenceContext($title)
    {
        $pattern = '/[^.]*\[\[' . preg_quote($title, '/') . '\]\][^.]*/';
        if (preg_match($pattern, $this->content, $match)) {
            return trim($match[0]);
        }
        return null;
    }

    // 获取相关页面推荐
    public function getRelatedPages($limit = 5)
    {
        // 基于分类和引用关系推荐相关页面
        return WikiPage::where('id', '!=', $this->id)
            ->where(function ($query) {
                // 同分类的页面
                $query->whereHas('categories', function ($q) {
                    $q->whereIn('wiki_categories.id', $this->categories->pluck('id'));
                })
                // 或者有共同引用的页面
                ->orWhereIn('id', function ($q) {
                    $q->select('target_page_id')
                        ->from('wiki_page_references')
                        ->whereIn('source_page_id', $this->referencedByPages->pluck('id'));
                });
            })
            ->withCount([
                'incomingReferences', // 引用计数
                'categories' => function ($query) {
                    $query->whereIn('wiki_categories.id', $this->categories->pluck('id'));
                } // 共同分类计数
            ])
            ->orderByDesc('categories_count')
            ->orderByDesc('incoming_references_count')
            ->limit($limit)
            ->get();
    }
}