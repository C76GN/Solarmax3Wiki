<?php

namespace App\Services;

use App\Events\WikiDiscussionMessage;
use App\Events\WikiEditorsUpdated;
use App\Models\User;
use App\Models\WikiPage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CollaborationService
{
    const EDITORS_CACHE_PREFIX = 'wiki_editors_';

    const DISCUSSION_CACHE_PREFIX = 'wiki_discussion_';

    const EDITOR_TIMEOUT = 70; // 秒

    const DISCUSSION_LIFETIME = 24; // 小时

    const MAX_MESSAGES = 100; // 最大保留消息数

    public function registerEditor(WikiPage $page, User $user): void
    {
        $cacheKey = $this->getEditorsKey($page->id);
        $editors = $this->getEditors($page->id);

        $editors[$user->id] = [
            'id' => $user->id,
            'name' => $user->name,
            'last_active' => now()->timestamp,
            'editing_since' => $editors[$user->id]['editing_since'] ?? now()->timestamp,
            'avatar' => $user->avatar ?? null,
        ];

        Cache::put($cacheKey, $editors, now()->addMinutes(30));
        $this->broadcastEditorsChange($page, $editors);

        // 记录活动日志
        \App\Models\ActivityLog::log('editor_active', $page, [
            'user_id' => $user->id,
            'action' => 'registered',
        ]);
    }

    public function unregisterEditor(WikiPage $page, User $user): void
    {
        $cacheKey = $this->getEditorsKey($page->id);
        $editors = $this->getEditors($page->id);

        // 移除编辑者
        unset($editors[$user->id]);

        if (! empty($editors)) {
            Cache::put($cacheKey, $editors, now()->addMinutes(30));
        } else {
            Cache::forget($cacheKey);
        }

        $this->broadcastEditorsChange($page, $editors);

        // 记录活动日志
        \App\Models\ActivityLog::log('editor_active', $page, [
            'user_id' => $user->id,
            'action' => 'unregistered',
        ]);
    }

    public function getEditors(int $pageId): array
    {
        $cacheKey = $this->getEditorsKey($pageId);
        $editors = Cache::get($cacheKey, []);
        $now = now()->timestamp;

        // 清理超时的编辑者
        foreach ($editors as $userId => $editor) {
            if ($now - $editor['last_active'] > self::EDITOR_TIMEOUT) {
                unset($editors[$userId]);

                // 记录活动日志（超时退出）
                \App\Models\ActivityLog::log('editor_active', WikiPage::find($pageId), [
                    'user_id' => $userId,
                    'action' => 'timeout',
                ]);
            }
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
