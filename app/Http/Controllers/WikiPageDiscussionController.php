<?php

namespace App\Http\Controllers;

use App\Models\WikiPage;
use App\Models\WikiPageDiscussion;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class WikiPageDiscussionController extends Controller
{
    /**
     * 获取页面的讨论消息
     */
    public function getMessages(WikiPage $page): JsonResponse
    {
        $discussions = WikiPageDiscussion::where('wiki_page_id', $page->id)
            ->with('user:id,name')
            ->orderBy('created_at', 'desc')
            ->take(50)
            ->get()
            ->map(function ($discussion) {
                return [
                    'id' => $discussion->id,
                    'message' => $discussion->message,
                    'user' => [
                        'id' => $discussion->user->id,
                        'name' => $discussion->user->name
                    ],
                    'editing_section' => $discussion->editing_section,
                    'created_at' => $discussion->created_at->diffForHumans(),
                    'timestamp' => $discussion->created_at->toIso8601String()
                ];
            });

        return response()->json([
            'messages' => $discussions
        ]);
    }

    /**
     * 发送新的讨论消息
     */
    public function sendMessage(Request $request, WikiPage $page): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'message' => 'required|string|max:1000',
            'editing_section' => 'nullable|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $discussion = WikiPageDiscussion::create([
            'wiki_page_id' => $page->id,
            'user_id' => Auth::id(),
            'message' => $request->message,
            'editing_section' => $request->editing_section
        ]);

        return response()->json([
            'success' => true,
            'message' => [
                'id' => $discussion->id,
                'message' => $discussion->message,
                'user' => [
                    'id' => Auth::id(),
                    'name' => Auth::user()->name
                ],
                'editing_section' => $discussion->editing_section,
                'created_at' => $discussion->created_at->diffForHumans(),
                'timestamp' => $discussion->created_at->toIso8601String()
            ]
        ]);
    }
}