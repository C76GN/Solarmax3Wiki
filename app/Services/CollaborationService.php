<?php

namespace App\Services;

use App\Events\WikiDiscussionMessage;
use App\Events\WikiEditorsUpdated;
use App\Models\User;
use App\Models\WikiPage;
use Illuminate\Support\Facades\Cache;

class CollaborationService
{
    const EDITORS_CACHE_PREFIX = 'wiki_editors_';

    const DISCUSSION_CACHE_PREFIX = 'wiki_discussion_';

    const EDITOR_TIMEOUT = 60; // 秒

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
        $messages = $this->getDiscussionMessages($page->id);

        // 限制消息数量，超过最大数量时移除最早的消息
        if (count($messages) >= self::MAX_MESSAGES) {
            array_shift($messages);
        }

        $newMessage = [
            'id' => uniqid(),
            'user_id' => $user->id,
            'user_name' => $user->name,
            'message' => $message,
            'timestamp' => now()->timestamp,
            'avatar' => $user->avatar ?? null,
        ];

        $messages[] = $newMessage;

        Cache::put($cacheKey, $messages, now()->addHours(self::DISCUSSION_LIFETIME));
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
        event(new WikiEditorsUpdated(
            $page->id,
            array_values($editors)
        ));
    }

    private function broadcastNewMessage(WikiPage $page, array $message): void
    {
        event(new WikiDiscussionMessage(
            $page->id,
            $message
        ));
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
