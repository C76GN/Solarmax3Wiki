<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WikiPage;
use Illuminate\Http\Request;

class WikiSearchController extends Controller
{
    public function search(Request $request)
    {
        $query = $request->get('q');
        
        if (empty($query)) {
            return response()->json([]);
        }

        $pages = WikiPage::query()
            ->where(function($q) use ($query) {
                // 标题匹配
                $q->where('title', 'like', "%{$query}%")
                  // 或者内容匹配
                  ->orWhere('content', 'like', "%{$query}%")
                  // 或者通过分类匹配
                  ->orWhereHas('categories', function($q) use ($query) {
                      $q->where('name', 'like', "%{$query}%");
                  });
            })
            ->with(['categories' => function($q) {
                $q->select('id', 'name');
            }])
            ->select(['id', 'title', 'slug'])
            ->orderByRaw("
                CASE 
                    WHEN title LIKE ? THEN 1
                    WHEN title LIKE ? THEN 2
                    ELSE 3
                END
            ", ["{$query}%", "%{$query}%"])
            ->orderBy('view_count', 'desc')
            ->limit(10)
            ->get();

        return response()->json($pages);
    }
}