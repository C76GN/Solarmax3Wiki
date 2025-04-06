<?php

namespace App\Services;

use App\Models\WikiPage;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

/**
 * 协作服务
 * 管理Wiki页面实时协作功能
 */
class CollaborationService
{
    /**
     * 页面编辑者缓存前缀
     */
    const EDITORS_CACHE_PREFIX = 'wiki_editors_';
    
    /**
     * 页面讨论缓存前缀
     */
    const DISCUSSION_CACHE_PREFIX = 'wiki_discussion_';
    
    /**
     * 编辑者活跃超时时间（秒）
     */
    const EDITOR_TIMEOUT = 60;
    
    /**
     * 讨论消息保留时间（小时）
     */
    const DISCUSSION_LIFETIME = 24;
    
    /**
     * 注册用户正在编辑页面
     *
     * @param WikiPage $page 页面对象
     * @param User $user 用户对象
     * @return void
     */
    public function registerEditor(WikiPage $page, User $user): void
    {
        $cacheKey = $this->getEditorsKey($page->id);
        $editors = $this->getEditors($page->id);
        
        // 更新或添加当前用户
        $editors[$user->id] = [
            'id' => $user->id,
            'name' => $user->name,
            'last_active' => now()->timestamp
        ];
        
        // 保存到缓存
        Cache::put($cacheKey, $editors, now()->addMinutes(30));
        
        // 广播编辑者变化
        $this->broadcastEditorsChange($page, $editors);
    }
    
    /**
     * 注销用户编辑状态
     *
     * @param WikiPage $page 页面对象
     * @param User $user 用户对象
     * @return void
     */
    public function unregisterEditor(WikiPage $page, User $user): void
    {
        $cacheKey = $this->getEditorsKey($page->id);
        $editors = $this->getEditors($page->id);
        
        // 移除当前用户
        unset($editors[$user->id]);
        
        // 如果还有其他编辑者，保存回缓存
        if (!empty($editors)) {
            Cache::put($cacheKey, $editors, now()->addMinutes(30));
        } else {
            Cache::forget($cacheKey);
        }
        
        // 广播编辑者变化
        $this->broadcastEditorsChange($page, $editors);
    }
    
    /**
     * 获取页面当前编辑者列表
     *
     * @param int $pageId 页面ID
     * @return array 编辑者列表
     */
    public function getEditors(int $pageId): array
    {
        $cacheKey = $this->getEditorsKey($pageId);
        $editors = Cache::get($cacheKey, []);
        
        // 清理超时的编辑者
        $now = now()->timestamp;
        foreach ($editors as $userId => $editor) {
            if ($now - $editor['last_active'] > self::EDITOR_TIMEOUT) {
                unset($editors[$userId]);
            }
        }
        
        return $editors;
    }
    
    /**
     * 添加讨论消息
     *
     * @param WikiPage $page 页面对象
     * @param User $user 用户对象
     * @param string $message 消息内容
     * @return array 消息数据
     */
    public function addDiscussionMessage(WikiPage $page, User $user, string $message): array
    {
        $cacheKey = $this->getDiscussionKey($page->id);
        $messages = $this->getDiscussionMessages($page->id);
        
        $newMessage = [
            'id' => uniqid(),
            'user_id' => $user->id,
            'user_name' => $user->name,
            'message' => $message,
            'timestamp' => now()->timestamp
        ];
        
        // 添加新消息
        $messages[] = $newMessage;
        
        // 保存到缓存
        Cache::put($cacheKey, $messages, now()->addHours(self::DISCUSSION_LIFETIME));
        
        // 广播新消息
        $this->broadcastNewMessage($page, $newMessage);
        
        return $newMessage;
    }
    
    /**
     * 获取讨论消息列表
     *
     * @param int $pageId 页面ID
     * @return array 消息列表
     */
    public function getDiscussionMessages(int $pageId): array
    {
        $cacheKey = $this->getDiscussionKey($pageId);
        return Cache::get($cacheKey, []);
    }
    
    /**
     * 广播编辑者变化事件
     *
     * @param WikiPage $page 页面对象
     * @param array $editors 编辑者列表
     * @return void
     */
    private function broadcastEditorsChange(WikiPage $page, array $editors): void
    {
        // 使用事件广播通知所有客户端编辑者列表变化
        event(new \App\Events\WikiEditorsUpdated(
            $page->id,
            array_values($editors)
        ));
    }
    
    /**
     * 广播新消息事件
     *
     * @param WikiPage $page 页面对象
     * @param array $message 消息数据
     * @return void
     */
    private function broadcastNewMessage(WikiPage $page, array $message): void
    {
        // 使用事件广播通知所有客户端有新消息
        event(new \App\Events\WikiDiscussionMessage(
            $page->id,
            $message
        ));
    }
    
    /**
     * 生成编辑者缓存键
     *
     * @param int $pageId 页面ID
     * @return string 缓存键
     */
    private function getEditorsKey(int $pageId): string
    {
        return self::EDITORS_CACHE_PREFIX . $pageId;
    }
    
    /**
     * 生成讨论缓存键
     *
     * @param int $pageId 页面ID
     * @return string 缓存键
     */
    private function getDiscussionKey(int $pageId): string
    {
        return self::DISCUSSION_CACHE_PREFIX . $pageId;
    }
}