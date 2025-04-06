<?php

namespace App\Http\Controllers;

use App\Models\WikiPage;
use App\Models\WikiComment;
use Illuminate\Http\Request;

class WikiCommentController extends Controller
{
    // 存储新评论
    public function store(Request $request, WikiPage $page)
    {
        $this->authorize('wiki.comment');
        
        $validated = $request->validate([
            'content' => 'required|string',
            'parent_id' => 'nullable|exists:wiki_comments,id'
        ]);
        
        $comment = WikiComment::create([
            'wiki_page_id' => $page->id,
            'user_id' => auth()->id(),
            'parent_id' => $validated['parent_id'],
            'content' => $validated['content']
        ]);
        
        return redirect()->back()
            ->with('flash', [
                'message' => [
                    'type' => 'success',
                    'text' => '评论发布成功！'
                ]
            ]);
    }
    
    // 更新评论
    public function update(Request $request, WikiComment $comment)
    {
        // 只有评论作者可以编辑
        if ($comment->user_id !== auth()->id() && !auth()->user()->hasPermission('wiki.resolve_conflict')) {
            return response()->json(['message' => '您无权编辑此评论'], 403);
        }
        
        $validated = $request->validate([
            'content' => 'required|string'
        ]);
        
        $comment->update([
            'content' => $validated['content']
        ]);
        
        return redirect()->back()
            ->with('flash', [
                'message' => [
                    'type' => 'success',
                    'text' => '评论更新成功！'
                ]
            ]);
    }
    
    // 删除评论
    public function destroy(WikiComment $comment)
    {
        // 只有评论作者或管理员可以删除
        if ($comment->user_id !== auth()->id() && !auth()->user()->hasPermission('wiki.resolve_conflict')) {
            return response()->json(['message' => '您无权删除此评论'], 403);
        }
        
        $comment->update([
            'is_hidden' => true
        ]);
        
        return redirect()->back()
            ->with('flash', [
                'message' => [
                    'type' => 'success',
                    'text' => '评论已删除！'
                ]
            ]);
    }
}