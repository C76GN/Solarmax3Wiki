<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Wiki 编辑者更新事件
 *
 * 此事件在 Wiki 页面上的编辑者列表发生变化时触发。
 * 它实现了 `ShouldBroadcast` 接口，意味着它可以通过 Laravel 的广播系统实时发送到客户端。
 */
class WikiEditorsUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * 发生编辑者更新的 Wiki 页面 ID。
     *
     * 该属性是公共的，因为它需要被广播系统序列化并发送到客户端。
     *
     * @var int
     */
    public $pageId;

    /**
     * 当前正在编辑该 Wiki 页面的编辑者列表。
     *
     * 包含每个编辑者的基本信息，如 ID、名称等，以便客户端更新UI。
     *
     * @var array
     */
    public $editors;

    /**
     * 创建一个新的 WikiEditorsUpdated 事件实例。
     *
     * @param int $pageId 发生更新的 Wiki 页面 ID。
     * @param array $editors 当前编辑该页面的用户数组。
     */
    public function __construct(int $pageId, array $editors)
    {
        $this->pageId = $pageId;
        $this->editors = $editors;
    }

    /**
     * 获取事件应该广播到的频道。
     *
     * 此事件将广播到一个以 `wiki.page.` 为前缀，后接页面 ID 的私有频道。
     * 这确保只有订阅了特定页面更新的客户端才能接收到此事件。
     *
     * @return Channel|array 返回一个或多个频道实例。
     */
    public function broadcastOn(): Channel|array
    {
        return new Channel('wiki.page.'.$this->pageId);
    }

    /**
     * 获取事件的广播名称。
     *
     * 此方法定义了事件在广播时使用的特定名称，客户端将通过此名称监听事件。
     *
     * @return string 返回事件的广播名称，例如 'editors.updated'。
     */
    public function broadcastAs(): string
    {
        return 'editors.updated';
    }
}