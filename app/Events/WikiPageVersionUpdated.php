<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Wiki 页面版本更新事件
 *
 * 此事件在 Wiki 页面有新版本创建并成为当前版本时触发。
 * 它实现了 `ShouldBroadcast` 接口，意味着该事件将通过 Laravel 的广播系统实时发送到前端。
 * 前端可以监听此事件，以实现实时通知或 UI 更新。
 */
class WikiPageVersionUpdated implements ShouldBroadcast
{
    // 引入 Trait 以提供事件调度、套接字交互和模型序列化能力
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Wiki 页面的 ID。
     *
     * 该属性将作为事件负载的一部分，发送给所有监听者。
     *
     * @var int
     */
    public $pageId;

    /**
     * 新版本记录的 ID。
     *
     * 该属性将作为事件负载的一部分，发送给所有监听者。
     *
     * @var int
     */
    public $newVersionId;

    /**
     * 创建一个新的事件实例。
     *
     * @param int $pageId Wiki 页面的唯一标识符。
     * @param int $newVersionId 新创建的 Wiki 版本记录的唯一标识符。
     */
    public function __construct(int $pageId, int $newVersionId)
    {
        $this->pageId = $pageId;
        $this->newVersionId = $newVersionId;
    }

    /**
     * 获取事件应该广播到的频道。
     *
     * 此方法定义了哪些广播频道会接收到此事件。
     * 返回一个数组，包含一个公共频道，频道名称格式为 `wiki.page.{pageId}`，
     * 这与协作编辑和讨论功能使用的频道相同，便于前端集中监听页面相关的所有实时更新。
     *
     * @return array<int, \Illuminate\Broadcasting\Channel|\Illuminate\Broadcasting\PresenceChannel|\Illuminate\Broadcasting\PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [new Channel('wiki.page.'.$this->pageId)];
    }

    /**
     * 获取事件的广播名称。
     *
     * 此方法定义了事件在广播时使用的自定义名称。
     * 前端 JavaScript 客户端将使用此名称来监听特定的事件。
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        // 自定义事件名称，前端监听此名称以区分不同类型的页面事件
        return 'page.version.updated';
    }
}