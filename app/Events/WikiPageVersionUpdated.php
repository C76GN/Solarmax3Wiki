<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WikiPageVersionUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $pageId;

    public $newVersionId;

    /**
     * Create a new event instance.
     */
    public function __construct(int $pageId, int $newVersionId)
    {
        $this->pageId = $pageId;
        $this->newVersionId = $newVersionId;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel|\Illuminate\Broadcasting\PresenceChannel|\Illuminate\Broadcasting\PrivateChannel>
     */
    public function broadcastOn(): array
    {
        // 使用与编辑器和讨论相同的公共频道
        return [new Channel('wiki.page.'.$this->pageId)];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        // 自定义事件名称，前端监听这个名称
        return 'page.version.updated';
    }
}
