<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WikiPage;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Wiki页面搜索控制器
 * 
 * 提供Wiki页面的搜索功能API
 */
class WikiSearchController extends Controller
{
    /**
     * 搜索Wiki页面
     *
     * 根据查询字符串搜索Wiki页面的标题、内容和分类
     * 
     * @param  Request  $request 包含搜索参数的请求对象
     * @return JsonResponse 搜索结果的JSON响应
     */
    public function search(Request $request): JsonResponse
    {
        // 获取搜索关键词
        $query = $request->get('q');
        
        // 如果查询为空，返回空数组
        if (empty($query)) {
            return response()->json([]);
        }
        
        // 执行搜索查询
        $pages = $this->performSearch($query);
        
        return response()->json($pages);
    }
    
    /**
     * 执行搜索逻辑
     *
     * @param  string  $query 搜索关键词
     * @return \Illuminate\Database\Eloquent\Collection 搜索结果集合
     */
    private function performSearch(string $query)
    {
        return WikiPage::query()
            ->where(function($q) use ($query) {
                // 搜索标题、内容和分类
                $q->where('title', 'like', "%{$query}%")
                  ->orWhere('content', 'like', "%{$query}%")
                  ->orWhereHas('categories', function($subQuery) use ($query) {
                      $subQuery->where('name', 'like', "%{$query}%");
                  });
            })
            // 预加载分类关系，只选择需要的字段
            ->with(['categories' => function($q) {
                $q->select('id', 'name');
            }])
            // 只选择需要的字段
            ->select(['id', 'title', 'slug'])
            // 按匹配程度排序（精确匹配优先）
            ->orderByRaw("
                CASE
                    WHEN title LIKE ? THEN 1  -- 标题以查询词开头
                    WHEN title LIKE ? THEN 2  -- 标题包含查询词
                    ELSE 3                    -- 其他匹配
                END
            ", ["{$query}%", "%{$query}%"])
            // 其次按浏览量排序
            ->orderBy('view_count', 'desc')
            // 限制结果数量
            ->limit(10)
            ->get();
    }
}