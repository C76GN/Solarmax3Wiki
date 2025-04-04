<?php

namespace App\Http\Controllers;

use App\Models\WikiPageDraft;
use App\Models\WikiPage;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class WikiPageDraftController extends Controller
{
    /**
     * 保存页面草稿
     */
    public function saveDraft(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'page_id' => 'required|exists:wiki_pages,id',
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'categories' => 'nullable|array',
            'categories.*' => 'exists:wiki_categories,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // 查找现有草稿或创建新草稿
        $draft = WikiPageDraft::updateOrCreate(
            [
                'wiki_page_id' => $request->page_id,
                'user_id' => Auth::id()
            ],
            [
                'title' => $request->title,
                'content' => $request->content,
                'categories' => $request->categories
            ]
        );

        return response()->json([
            'success' => true,
            'draft_id' => $draft->id,
            'saved_at' => $draft->updated_at->toIso8601String()
        ]);
    }

    /**
     * 获取用户的页面草稿
     */
    public function getDraft(Request $request, WikiPage $page): JsonResponse
    {
        $draft = WikiPageDraft::where('wiki_page_id', $page->id)
            ->where('user_id', Auth::id())
            ->first();

        if (!$draft) {
            return response()->json([
                'success' => false,
                'message' => '未找到草稿'
            ]);
        }

        return response()->json([
            'success' => true,
            'draft' => [
                'id' => $draft->id,
                'title' => $draft->title,
                'content' => $draft->content,
                'categories' => $draft->categories,
                'updated_at' => $draft->updated_at->toIso8601String()
            ]
        ]);
    }

    /**
     * 删除用户的页面草稿
     */
    public function deleteDraft(Request $request, WikiPage $page): JsonResponse
    {
        $draft = WikiPageDraft::where('wiki_page_id', $page->id)
            ->where('user_id', Auth::id())
            ->first();

        if (!$draft) {
            return response()->json([
                'success' => false,
                'message' => '未找到草稿'
            ]);
        }

        $draft->delete();

        return response()->json([
            'success' => true,
            'message' => '草稿已删除'
        ]);
    }
    
    /**
     * 获取用户的所有草稿列表
     */
    public function listDrafts(): JsonResponse
    {
        $drafts = WikiPageDraft::where('user_id', Auth::id())
            ->with('page:id,title,slug')
            ->latest()
            ->get()
            ->map(function ($draft) {
                return [
                    'id' => $draft->id,
                    'page' => $draft->page ? [
                        'id' => $draft->page->id,
                        'title' => $draft->page->title,
                        'slug' => $draft->page->slug
                    ] : null,
                    'title' => $draft->title,
                    'updated_at' => $draft->updated_at->diffForHumans(),
                    'timestamp' => $draft->updated_at->toIso8601String()
                ];
            });

        return response()->json([
            'success' => true,
            'drafts' => $drafts
        ]);
    }
}