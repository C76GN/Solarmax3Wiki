<?php
namespace App\Repositories;
use App\Models\WikiPage;
use App\Models\WikiCategory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
class WikiPageRepository
{
    public function getPagesList(array $filters, ?int $userId): LengthAwarePaginator
    {
        $query = WikiPage::with(['creator', 'lastEditor', 'categories']);
        
        if (isset($filters['search']) && $filters['search']) {
            $query->where(function ($q) use ($filters) {
                $q->where('title', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('content', 'like', '%' . $filters['search'] . '%');
            });
        }
        
        if (isset($filters['status']) && $filters['status']) {
            $query->where('status', $filters['status']);
        }
        
        if (isset($filters['category']) && $filters['category']) {
            $query->whereHas('categories', function ($q) use ($filters) {
                $q->where('wiki_categories.id', $filters['category']);
            });
        }
        
        $userId = $userId ?: 0;
        
        // 简化查询，移除审核条件
        $query->whereRaw(
            "( ((status='draft') and created_by = ?) or (status = 'published') )",
            [$userId]
        );
        
        // 处理排序
        if (isset($filters['sort'])) {
            if ($filters['sort'] === 'created_at') {
                $query->orderBy('created_at', 'desc');
            } elseif ($filters['sort'] === 'updated_at') {
                $query->orderBy('updated_at', 'desc');
            } elseif ($filters['sort'] === 'view_count') {
                $query->orderBy('view_count', 'desc');
            }
        } else {
            $query->orderBy('view_count', 'desc');
        }
        
        return $query->paginate(10);
    }
    
    /**
     * 获取回收站中的页面列表
     *
     * @return LengthAwarePaginator 分页后的已删除页面列表
     */
    public function getTrashedPages(): LengthAwarePaginator
    {
        return WikiPage::onlyTrashed()
            ->with(['creator', 'lastEditor', 'categories'])
            ->latest('deleted_at')
            ->paginate(10);
    }
    
    /**
     * 获取所有分类并缓存结果
     *
     * @return Collection 分类集合
     */
    public function getAllCategories(): Collection
    {
        return Cache::remember('wiki_categories', 60 * 60, function () {
            return WikiCategory::orderBy('order')
                ->withCount('pages')
                ->get();
        });
    }
    
    /**
     * 从回收站恢复页面
     *
     * @param int $id 页面ID
     * @return bool 恢复是否成功
     */
    public function restorePage(int $id): bool
    {
        $page = WikiPage::onlyTrashed()->findOrFail($id);
        return $page->restore();
    }
    
    /**
     * 永久删除页面及其关联数据
     *
     * @param int $id 页面ID
     * @return bool 删除是否成功
     */
    public function forceDeletePage(int $id): bool
    {
        $page = WikiPage::onlyTrashed()->findOrFail($id);
        $page->outgoingReferences()->forceDelete();
        $page->incomingReferences()->forceDelete();
        return $page->forceDelete();
    }
    
    /**
     * 查找标题相似的页面
     *
     * @param string $title 标题
     * @param int $limit 结果数量限制
     * @return Collection 相似页面集合
     */
    public function findSimilarPages(string $title, int $limit = 5): Collection
    {
        return WikiPage::where('status', WikiPage::STATUS_PUBLISHED)
            ->where('title', 'like', '%' . $title . '%')
            ->orderByRaw('CASE WHEN title = ? THEN 1 WHEN title LIKE ? THEN 2 ELSE 3 END', [$title, $title . '%'])
            ->limit($limit)
            ->get(['id', 'title', 'slug']);
    }
    
    /**
     * 生成唯一的页面slug
     *
     * @param string $title 页面标题
     * @return string 生成的唯一slug
     */
    public function generateUniqueSlug(string $title): string
    {
        $baseSlug = Str::slug($title);
        $slug = $baseSlug;
        $counter = 1;
        // 循环检查slug是否存在，如果存在则添加数字后缀
        while (WikiPage::where('slug', $slug)->exists()) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }
        return $slug;
    }
    
    /**
     * 清除页面编辑者的缓存
     *
     * @param int $pageId 页面ID
     * @return void
     */
    public function clearEditorsCache(int $pageId): void
    {
        Cache::forget("editing_page:{$pageId}");
    }
}