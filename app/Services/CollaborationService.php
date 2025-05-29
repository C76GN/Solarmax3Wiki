<?php

namespace App\Services;

use App\Events\WikiDiscussionMessage;
use App\Events\WikiEditorsUpdated;
use App\Models\ActivityLog;
use App\Models\User;
use App\Models\WikiPage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * 协作服务
 * 负责管理维基页面的实时协作功能，包括在线编辑者列表、临时编辑锁和实时讨论消息。
 */
class CollaborationService
{
    /**
     * 在线编辑者缓存键前缀。
     */
    const EDITORS_CACHE_PREFIX = 'wiki_editors_';

    /**
     * 讨论消息缓存键前缀。
     */
    const DISCUSSION_CACHE_PREFIX = 'wiki_discussion_';

    /**
     * 编辑者活动超时时间（秒）。超过此时间未活跃的编辑者将被移除。
     */
    const EDITOR_TIMEOUT = 70;

    /**
     * 讨论消息的缓存生命周期（小时）。
     */
    const DISCUSSION_LIFETIME = 24;

    /**
     * 每个讨论的最大保留消息数量。
     */
    const MAX_MESSAGES = 100;

    /**
     * 维基页面临时编辑锁的缓存键前缀。
     */
    const TEMP_LOCK_PREFIX = 'wiki_temp_lock_';

    /**
     * 临时编辑锁的持续时间（秒）。
     */
    const TEMP_LOCK_DURATION = 60;

    /**
     * 注册或更新编辑者在特定维基页面上的活跃状态。
     * 该方法会检查临时锁和编辑人数限制，如果用户已经在编辑，则只更新其活跃时间戳。
     * @param  WikiPage  $page  维基页面实例。
     * @param  User  $user  当前编辑者用户实例。
     * @return bool 如果编辑者成功注册或更新活跃状态，则返回 true；否则返回 false。
     */
    public function registerEditor(WikiPage $page, User $user): bool
    {
        $cacheKey = $this->getEditorsKey($page->id);
        $editors = $this->getEditors($page->id);
        // 检查页面是否存在临时锁，如果存在且当前用户不在编辑列表中，则拒绝注册。
        $tempLockKey = self::TEMP_LOCK_PREFIX.$page->id;
        if (Cache::has($tempLockKey) && ! isset($editors[$user->id])) {
            Log::warning("Temporary lock active for page {$page->id}. User {$user->id} registration denied.");
            return false;
        }
        // 检查编辑人数限制。如果已达最大人数（2人）且当前用户不在编辑列表中，则拒绝注册。
        $editorCount = count($editors);
        $isCurrentUserEditing = isset($editors[$user->id]);
        if ($editorCount >= 2 && ! $isCurrentUserEditing) {
            Log::warning("Editor limit (2) reached for page {$page->id}. User {$user->id} registration denied.");
            return false;
        }
        // 更新或添加编辑者信息。
        $editors[$user->id] = [
            'id' => $user->id,
            'name' => $user->name,
            'last_active' => now()->timestamp,
            'editing_since' => $editors[$user->id]['editing_since'] ?? now()->timestamp, // 如果是新注册，记录开始编辑时间
            'avatar' => $user->avatar ?? null,
            'base_version_id' => $editors[$user->id]['base_version_id'] ?? $page->current_version_id, // 记录用户开始编辑时的页面版本ID
        ];
        // 缓存编辑者列表，并设置缓存过期时间。
        Cache::put($cacheKey, $editors, now()->addMinutes(30));
        // 广播编辑者列表更新事件。
        $this->broadcastEditorsChange($page, $editors);
        // 记录用户活动日志。
        ActivityLog::log('editor_active', $page, ['user_id' => $user->id, 'action' => 'registered/heartbeat']);
        return true;
    }

    /**
     * 为指定维基页面设置临时编辑锁。
     *
     * 在页面保存或特定操作后，可设置此锁以避免并发修改。
     *
     * @param  int  $pageId  维基页面ID。
     */
    public function setTemporaryLock(int $pageId): void
    {
        $tempLockKey = self::TEMP_LOCK_PREFIX.$pageId;
        // 缓存临时锁，设置其持续时间。
        Cache::put($tempLockKey, true, self::TEMP_LOCK_DURATION);
        Log::info("Temporary lock set for page {$pageId} for ".self::TEMP_LOCK_DURATION.' seconds.');
    }

    /**
     * 移除指定维基页面的临时编辑锁。
     *
     * @param  int  $pageId  维基页面ID。
     */
    public function removeTemporaryLock(int $pageId): void
    {
        $tempLockKey = self::TEMP_LOCK_PREFIX.$pageId;
        if (Cache::forget($tempLockKey)) {
            Log::info("Temporary lock removed for page {$pageId}.");
        }
    }

    /**
     * 注销指定用户在维基页面上的编辑状态。
     *
     * 如果用户是该页面最后一个离开的编辑者，则移除临时锁。
     *
     * @param  WikiPage  $page  维基页面实例。
     * @param  User  $user  要注销的用户实例。
     */
    public function unregisterEditor(WikiPage $page, User $user): void
    {
        $cacheKey = $this->getEditorsKey($page->id);
        $editors = $this->getEditors($page->id);

        $wasEditing = isset($editors[$user->id]); // 记录用户是否曾在编辑列表中。

        unset($editors[$user->id]); // 从编辑者列表中移除用户。

        if (! empty($editors)) {
            // 如果还有其他编辑者，更新缓存。
            Cache::put($cacheKey, $editors, now()->addMinutes(30));
        } else {
            // 如果所有编辑者都已离开，清除缓存并移除临时锁。
            Cache::forget($cacheKey);
            $this->removeTemporaryLock($page->id);
        }

        // 广播编辑者列表更新事件。
        $this->broadcastEditorsChange($page, $editors);

        // 只有当用户确实在编辑列表里时才记录注销日志。
        if ($wasEditing) {
            ActivityLog::log('editor_active', $page, ['user_id' => $user->id, 'action' => 'unregistered']);
        }
    }

    /**
     * 获取指定维基页面的当前在线编辑者列表。
     *
     * 该方法会自动清理超时的编辑者。
     *
     * @param  int  $pageId  维基页面ID。
     * @return array 活跃编辑者的数组，每个元素包含编辑者ID、名称、最后活跃时间等。
     */
    public function getEditors(int $pageId): array
    {
        $cacheKey = $this->getEditorsKey($pageId);
        $editors = Cache::get($cacheKey, []);
        $now = now()->timestamp;

        $initialEditorCount = count($editors); // 记录清理前的编辑者数量。

        // 遍历并清理超时的编辑者。
        foreach ($editors as $userId => $editor) {
            if (isset($editor['last_active']) && ($now - $editor['last_active'] > self::EDITOR_TIMEOUT)) {
                unset($editors[$userId]);

                // 记录超时退出日志。
                $wikiPage = WikiPage::find($pageId);
                if ($wikiPage) {
                    \App\Models\ActivityLog::log('editor_active', $wikiPage, [
                        'user_id' => $userId,
                        'action' => 'timeout',
                    ]);
                    Log::info("Editor {$userId} timed out from page {$pageId}.");
                } else {
                    Log::warning("Attempted to log editor timeout for non-existent page {$pageId}, user {$userId}.");
                }
            }
        }

        // 如果有编辑者被移除，更新缓存。
        if (count($editors) < $initialEditorCount) {
            if (empty($editors)) {
                Cache::forget($cacheKey);
            } else {
                Cache::put($cacheKey, $editors, now()->addMinutes(30));
            }
        }

        return $editors;
    }

    /**
     * 为指定维基页面添加一条讨论消息。
     * 消息会缓存并在达到最大数量时自动清理旧消息。
     * @param  WikiPage  $page  维基页面实例。
     * @param  User  $user  发送消息的用户实例。
     * @param  string  $message  消息内容。
     * @return array 新添加的消息数据。
     */
    public function addDiscussionMessage(WikiPage $page, User $user, string $message): array
    {
        $cacheKey = $this->getDiscussionKey($page->id);
        $messages = Cache::get($cacheKey, []);
        // 如果消息数量超过最大限制，移除最早的消息。
        if (count($messages) >= self::MAX_MESSAGES) {
            $messages = array_slice($messages, -(self::MAX_MESSAGES - 1)); // 保留最新的 MAX_MESSAGES-1 条
            Log::info("Discussion cache for page {$page->id} pruned to ".self::MAX_MESSAGES.' messages.');
        }
        // 构建新消息数据。
        $newMessage = [
            'id' => uniqid('msg_'),
            'user_id' => $user->id,
            'user_name' => $user->name,
            'message' => strip_tags($message), // 简单过滤HTML标签，防止XSS。
            'timestamp' => now()->timestamp,
            'avatar' => $user->avatar ?? null,
        ];
        $messages[] = $newMessage; // 添加新消息。
        // 缓存更新后的消息列表，设置生命周期。
        Cache::put($cacheKey, $messages, now()->addHours(self::DISCUSSION_LIFETIME));
        // 广播新消息事件。
        $this->broadcastNewMessage($page, $newMessage);
        return $newMessage;
    }

    /**
     * 获取指定维基页面的所有讨论消息。
     *
     * @param  int  $pageId  维基页面ID。
     * @return array 讨论消息数组。
     */
    public function getDiscussionMessages(int $pageId): array
    {
        $cacheKey = $this->getDiscussionKey($pageId);

        return Cache::get($cacheKey, []);
    }

    /**
     * 广播维基页面编辑者列表更新事件。
     *
     * @param  WikiPage  $page  维基页面实例。
     * @param  array  $editors  当前的编辑者列表。
     */
    private function broadcastEditorsChange(WikiPage $page, array $editors): void
    {
        Log::info("Broadcasting editor update for page {$page->id}", ['editors_count' => count($editors)]);
        try {
            // 触发 WikiEditorsUpdated 事件，通知前端编辑者列表已更新。
            event(new WikiEditorsUpdated(
                $page->id,
                array_values($editors) // 确保发送的是索引数组，方便前端处理。
            ));
        } catch (\Exception $e) {
            Log::error("Error broadcasting WikiEditorsUpdated for page {$page->id}: ".$e->getMessage());
        }
    }

    /**
     * 广播维基页面新讨论消息事件。
     *
     * @param  WikiPage  $page  维基页面实例。
     * @param  array  $message  新消息的数据。
     */
    private function broadcastNewMessage(WikiPage $page, array $message): void
    {
        Log::info("Broadcasting new message for page {$page->id}", ['message_id' => $message['id']]);
        try {
            // 触发 WikiDiscussionMessage 事件，通知前端有新讨论消息。
            event(new WikiDiscussionMessage(
                $page->id,
                $message
            ));
        } catch (\Exception $e) {
            Log::error("Error broadcasting WikiDiscussionMessage for page {$page->id}: ".$e->getMessage());
        }
    }

    /**
     * 生成在线编辑者列表的缓存键。
     *
     * @param  int  $pageId  维基页面ID。
     * @return string 缓存键字符串。
     */
    private function getEditorsKey(int $pageId): string
    {
        return self::EDITORS_CACHE_PREFIX.$pageId;
    }

    /**
     * 生成讨论消息列表的缓存键。
     *
     * @param  int  $pageId  维基页面ID。
     * @return string 缓存键字符串。
     */
    private function getDiscussionKey(int $pageId): string
    {
        return self::DISCUSSION_CACHE_PREFIX.$pageId;
    }
}
