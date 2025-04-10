<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WikiEditorsUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * 页面ID
     *
     * @var int
     */
    public $pageId;

    /**
     * 编辑者列表
     *
     * @var array
     */
    public $editors;

    /**
     * 创建新事件实例
     *
     * @param  int  $pageId  页面ID
     * @param  array  $editors  编辑者列表
     * @return void
     */
    public function __construct(int $pageId, array $editors)
    {
        $this->pageId = $pageId;
        $this->editors = $editors;
    }

    /**
     * 获取事件广播的频道
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        return new Channel('wiki.page.'.$this->pageId);
    }

    /**
     * 获取广播事件名称
     *
     * @return string
     */
    public function broadcastAs()
    {
        return 'editors.updated';
    }
}
