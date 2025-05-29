<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Wiki 讨论消息事件
 *
 * 此事件在 Wiki 页面有新讨论消息时触发，并广播到指定的频道，
 * 以便前端实时接收并更新讨论区。
 */
class WikiDiscussionMessage implements ShouldBroadcast
{
    // 使用 Laravel 的事件调度、Socket 交互和模型序列化特性
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * 发生讨论消息的 Wiki 页面 ID。
     *
     * 该属性为公共属性，以便在广播时直接访问。
     *
     * @var int
     */
    public $pageId;

    /**
     * 要广播的讨论消息数据。
     *
     * 包含消息内容、发送者信息和时间戳等。
     *
     * @var array
     */
    public $message;

    /**
     * 创建一个新的 WikiDiscussionMessage 事件实例。
     *
     * @param int $pageId 发生消息的 Wiki 页面 ID。
     * @param array $message 包含消息内容及相关信息的数组。
     * @return void
     */
    public function __construct(int $pageId, array $message)
    {
        $this->pageId = $pageId;
        $this->message = $message;
    }

    /**
     * 获取事件应该广播到的频道。
     *
     * 对于 Wiki 讨论消息，它将广播到特定 Wiki 页面的频道，
     * 格式为 `wiki.page.{pageId}`。
     *
     * @return \Illuminate\Broadcasting\Channel|\Illuminate\Broadcasting\Channel[]
     */
    public function broadcastOn()
    {
        // 返回一个公共频道，名称根据页面ID动态生成
        return new Channel('wiki.page.' . $this->pageId);
    }

    /**
     * 获取广播事件的名称。
     *
     * 这是客户端监听事件时使用的名称。
     *
     * @return string
     */
    public function broadcastAs()
    {
        // 定义事件在前端的别名
        return 'discussion.message';
    }
}