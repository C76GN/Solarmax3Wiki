<?php

namespace App\Services;

use App\Events\WikiDiscussionMessage;
use App\Events\WikiEditorsUpdated;
use App\Models\ActivityLog;
use App\Models\User;
use App\Models\WikiPage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log; // Added import for ActivityLog

class CollaborationService
{
    const EDITORS_CACHE_PREFIX = 'wiki_editors_';

    const DISCUSSION_CACHE_PREFIX = 'wiki_discussion_';

    const EDITOR_TIMEOUT = 70; // 秒

    const DISCUSSION_LIFETIME = 24; // 小时

    const MAX_MESSAGES = 100; // 最大保留消息数

    const TEMP_LOCK_PREFIX = 'wiki_temp_lock_'; // 新增常量

    const TEMP_LOCK_DURATION = 60; // 临时锁定持续时间（秒），例如 1 分钟

    public function registerEditor(WikiPage $page, User $user): bool // 修改返回类型为 bool
    {
        $cacheKey = $this->getEditorsKey($page->id);
        $editors = $this->getEditors($page->id); // getEditors 内部会清理超时的

        // --- 新增：检查临时锁 ---
        $tempLockKey = self::TEMP_LOCK_PREFIX.$page->id;
        if (Cache::has($tempLockKey) && ! isset($editors[$user->id])) {
            // 如果存在临时锁，并且当前用户不是已在编辑列表中的用户，则拒绝
            Log::warning("Temporary lock active for page {$page->id}. User {$user->id} registration denied.");

            return false; // 返回 false 表示注册失败
        }
        // --- 结束新增 ---

        // --- 新增：检查编辑人数限制 ---
        $editorCount = count($editors);
        $isCurrentUserEditing = isset($editors[$user->id]);

        if ($editorCount >= 2 && ! $isCurrentUserEditing) {
            Log::warning("Editor limit (2) reached for page {$page->id}. User {$user->id} registration denied.");

            return false; // 返回 false 表示注册失败
        }
        // --- 结束新增 ---

        // 如果用户已经在编辑，只更新时间戳
        $editors[$user->id] = [
            'id' => $user->id,
            'name' => $user->name,
            'last_active' => now()->timestamp,
            'editing_since' => $editors[$user->id]['editing_since'] ?? now()->timestamp,
            'avatar' => $user->avatar ?? null, // 假设有头像字段
            'base_version_id' => $editors[$user->id]['base_version_id'] ?? $page->current_version_id, // 记录基版本
        ];

        Cache::put($cacheKey, $editors, now()->addMinutes(30)); // 编辑列表缓存时间
        $this->broadcastEditorsChange($page, $editors);

        // 记录活动日志 (可选，看是否需要记录心跳)
        ActivityLog::log('editor_active', $page, ['user_id' => $user->id, 'action' => 'registered/heartbeat']);

        return true; // 返回 true 表示注册/心跳成功
    }

    public function setTemporaryLock(int $pageId): void
    {
        $tempLockKey = self::TEMP_LOCK_PREFIX.$pageId;
        Cache::put($tempLockKey, true, self::TEMP_LOCK_DURATION);
        Log::info("Temporary lock set for page {$pageId} for ".self::TEMP_LOCK_DURATION.' seconds.');
    }

    // --- 新增：移除临时锁的方法 ---
    public function removeTemporaryLock(int $pageId): void
    {
        $tempLockKey = self::TEMP_LOCK_PREFIX.$pageId;
        if (Cache::forget($tempLockKey)) {
            Log::info("Temporary lock removed for page {$pageId}.");
        }
    }

    public function unregisterEditor(WikiPage $page, User $user): void
    {
        $cacheKey = $this->getEditorsKey($page->id);
        $editors = $this->getEditors($page->id);

        $wasEditing = isset($editors[$user->id]); // 检查用户是否真的在编辑列表里

        unset($editors[$user->id]);

        if (! empty($editors)) {
            Cache::put($cacheKey, $editors, now()->addMinutes(30));
        } else {
            Cache::forget($cacheKey);
            // 当最后一个编辑器离开时，移除临时锁
            $this->removeTemporaryLock($page->id);
        }

        $this->broadcastEditorsChange($page, $editors);

        // 只有当用户确实在编辑列表里时才记录日志
        if ($wasEditing) {
            ActivityLog::log('editor_active', $page, ['user_id' => $user->id, 'action' => 'unregistered']);
        }
    }

    public function getEditors(int $pageId): array
    {
        $cacheKey = $this->getEditorsKey($pageId);
        $editors = Cache::get($cacheKey, []);
        $now = now()->timestamp;

        // 清理超时的编辑者
        foreach ($editors as $userId => $editor) {
            // 检查 'last_active' 是否存在，并且计算时间差
            if (isset($editor['last_active']) && ($now - $editor['last_active'] > self::EDITOR_TIMEOUT)) { // <-- 添加了缺失的右括号
                unset($editors[$userId]);

                // 记录活动日志（超时退出）
                // 确保WikiPage模型存在
                $wikiPage = WikiPage::find($pageId);
                if ($wikiPage) { // 只有在页面存在时才记录日志
                    \App\Models\ActivityLog::log('editor_active', $wikiPage, [
                        'user_id' => $userId,
                        'action' => 'timeout',
                    ]);
                    Log::info("Editor {$userId} timed out from page {$pageId}."); // 添加日志记录
                } else {
                    Log::warning("Attempted to log editor timeout for non-existent page {$pageId}, user {$userId}.");
                }
            }
        }

        // 如果有编辑器被移除，更新缓存
        // (这步可选，如果get总是从缓存读最新，不写回去也没问题，下次get时自然会清理)
        // 但为了明确性，可以在这里判断是否有移除并更新缓存
        if (count($editors) < count(Cache::get($cacheKey, []))) { // 检查编辑器数量是否减少
            if (empty($editors)) {
                Cache::forget($cacheKey); // 如果没有编辑器了，直接删除缓存键
            } else {
                Cache::put($cacheKey, $editors, now()->addMinutes(30)); // 更新缓存
            }
            // 不需要再次广播，因为这不是主动操作，下次心跳或获取时会更新
        }

        return $editors;
    }

    public function addDiscussionMessage(WikiPage $page, User $user, string $message): array
    {
        $cacheKey = $this->getDiscussionKey($page->id);
        // 先获取当前消息，避免并发问题（虽然可能性小）
        $messages = Cache::get($cacheKey, []);

        // 如果超过最大数量，移除最早的消息
        if (count($messages) >= self::MAX_MESSAGES) {
            // array_slice 保留最后 N 条记录
            $messages = array_slice($messages, -(self::MAX_MESSAGES - 1));
            Log::info("Discussion cache for page {$page->id} pruned to ".self::MAX_MESSAGES.' messages.'); // 添加日志记录
        }

        $newMessage = [
            'id' => uniqid('msg_'), // 增加前缀，避免潜在的纯数字ID问题
            'user_id' => $user->id,
            'user_name' => $user->name,
            'message' => strip_tags($message), // 简单过滤HTML标签
            'timestamp' => now()->timestamp,
            'avatar' => $user->avatar ?? null, // 假设用户模型有 avatar 属性
        ];

        $messages[] = $newMessage;

        // 存回缓存
        Cache::put($cacheKey, $messages, now()->addHours(self::DISCUSSION_LIFETIME));

        // 广播新消息
        $this->broadcastNewMessage($page, $newMessage);

        return $newMessage;
    }

    public function getDiscussionMessages(int $pageId): array
    {
        $cacheKey = $this->getDiscussionKey($pageId);

        return Cache::get($cacheKey, []);
    }

    private function broadcastEditorsChange(WikiPage $page, array $editors): void
    {
        Log::info("Broadcasting editor update for page {$page->id}", ['editors_count' => count($editors)]); // 添加日志
        try {
            event(new WikiEditorsUpdated(
                $page->id,
                array_values($editors) // 确保发送的是索引数组
            ));
        } catch (\Exception $e) {
            Log::error("Error broadcasting WikiEditorsUpdated for page {$page->id}: ".$e->getMessage());
        }
    }

    private function broadcastNewMessage(WikiPage $page, array $message): void
    {
        Log::info("Broadcasting new message for page {$page->id}", ['message_id' => $message['id']]); // 添加日志
        try {
            event(new WikiDiscussionMessage(
                $page->id,
                $message
            ));
        } catch (\Exception $e) {
            Log::error("Error broadcasting WikiDiscussionMessage for page {$page->id}: ".$e->getMessage());
        }
    }

    private function getEditorsKey(int $pageId): string
    {
        return self::EDITORS_CACHE_PREFIX.$pageId;
    }

    private function getDiscussionKey(int $pageId): string
    {
        return self::DISCUSSION_CACHE_PREFIX.$pageId;
    }
}
