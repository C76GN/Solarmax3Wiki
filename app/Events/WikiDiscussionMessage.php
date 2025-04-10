<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WikiDiscussionMessage implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * 页面ID
     *
     * @var int
     */
    public $pageId;

    /**
     * 消息数据
     *
     * @var array
     */
    public $message;

    /**
     * 创建新事件实例
     *
     * @param  int  $pageId  页面ID
     * @param  array  $message  消息数据
     * @return void
     */
    public function __construct(int $pageId, array $message)
    {
        $this->pageId = $pageId;
        $this->message = $message;
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
        return 'discussion.message';
    }
}
