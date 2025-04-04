<?php

namespace App\Http\Controllers;

use App\Models\WikiPage;
use App\Models\WikiPageSectionLock;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class WikiPageSectionLockController extends Controller
{
    /**
     * 获取页面所有区域锁定状态
     */
    public function getSectionLocks(WikiPage $page): JsonResponse
    {
        // 清理过期锁
        WikiPageSectionLock::where('wiki_page_id', $page->id)
            ->where('expires_at', '<', now())
            ->delete();
            
        // 获取有效锁
        $locks = WikiPageSectionLock::where('wiki_page_id', $page->id)
            ->with('user:id,name')
            ->get()
            ->map(function ($lock) {
                return [
                    'section_id' => $lock->section_id,
                    'section_title' => $lock->section_title,
                    'locked_by' => [
                        'id' => $lock->user->id,
                        'name' => $lock->user->name
                    ],
                    'expires_at' => $lock->expires_at->toIso8601String(),
                    // 当前用户是否可以解锁这个区域
                    'can_unlock' => Auth::id() == $lock->user_id || Auth::user()->hasPermission('wiki.unlock_any')
                ];
            });

        return response()->json([
            'locks' => $locks
        ]);
    }

    /**
     * 锁定页面特定区域
     */
    public function lockSection(Request $request, WikiPage $page): JsonResponse
    {
        $validated = $request->validate([
            'section_id' => 'required|string|max:255',
            'section_title' => 'nullable|string|max:255',
            'duration' => 'nullable|integer|min:5|max:120' // 锁定时间，单位分钟
        ]);
        
        // 默认锁定30分钟
        $duration = $validated['duration'] ?? 30;
        $expiresAt = now()->addMinutes($duration);
        
        // 检查该区域是否已被锁定
        $existingLock = WikiPageSectionLock::where('wiki_page_id', $page->id)
            ->where('section_id', $validated['section_id'])
            ->first();
            
        // 如果已被当前用户锁定，则刷新过期时间
        if ($existingLock && $existingLock->user_id == Auth::id()) {
            $existingLock->update([
                'expires_at' => $expiresAt
            ]);
            
            return response()->json([
                'success' => true,
                'message' => '已刷新锁定时间',
                'expires_at' => $expiresAt->toIso8601String()
            ]);
        }
        
        // 如果被其他用户锁定且未过期，返回错误
        if ($existingLock && !$existingLock->isExpired()) {
            return response()->json([
                'success' => false,
                'message' => '该区域已被其他用户锁定',
                'locked_by' => $existingLock->user->name,
                'expires_at' => $existingLock->expires_at->toIso8601String()
            ], 409);
        }
        
        // 如果锁存在但已过期，删除它
        if ($existingLock) {
            $existingLock->delete();
        }
        
        // 创建新锁
        $lock = WikiPageSectionLock::create([
            'wiki_page_id' => $page->id,
            'user_id' => Auth::id(),
            'section_id' => $validated['section_id'],
            'section_title' => $validated['section_title'] ?? null,
            'expires_at' => $expiresAt
        ]);
        
        return response()->json([
            'success' => true,
            'message' => '区域已锁定',
            'lock' => [
                'section_id' => $lock->section_id,
                'section_title' => $lock->section_title,
                'expires_at' => $lock->expires_at->toIso8601String()
            ]
        ]);
    }

    /**
     * 解锁页面特定区域
     */
    public function unlockSection(Request $request, WikiPage $page): JsonResponse
    {
        $validated = $request->validate([
            'section_id' => 'required|string|max:255'
        ]);
        
        $lock = WikiPageSectionLock::where('wiki_page_id', $page->id)
            ->where('section_id', $validated['section_id'])
            ->first();
            
        if (!$lock) {
            return response()->json([
                'success' => false,
                'message' => '该区域未被锁定'
            ]);
        }
        
        // 检查权限：只有锁定者或管理员可以解锁
        if ($lock->user_id != Auth::id() && !Auth::user()->hasPermission('wiki.unlock_any')) {
            return response()->json([
                'success' => false,
                'message' => '您没有权限解锁此区域'
            ], 403);
        }
        
        $lock->delete();
        
        return response()->json([
            'success' => true,
            'message' => '区域已解锁'
        ]);
    }
}