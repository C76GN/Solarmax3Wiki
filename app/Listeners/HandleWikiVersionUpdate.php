<?php

namespace App\Listeners;

use App\Events\WikiPageVersionUpdated;
use App\Services\CollaborationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Wiki 页面版本更新事件监听器
 *
 * 此监听器响应 `WikiPageVersionUpdated` 事件，主要用于处理协作编辑中的版本冲突。
 * 当 Wiki 页面有新版本发布时，它会检查是否有其他用户正在编辑旧版本，并据此调整页面的临时锁定状态。
 */
class HandleWikiVersionUpdate implements ShouldQueue // 实现 ShouldQueue 接口，表示此监听器可以被异步处理
{
    use InteractsWithQueue; // 引入 trait，提供队列处理相关功能，如重试和超时

    /**
     * 协作服务实例。
     *
     * 用于访问和操作协作编辑相关的数据，如获取当前编辑者列表和管理页面锁定状态。
     *
     * @var CollaborationService
     */
    protected CollaborationService $collaborationService;

    /**
     * 构造函数。
     *
     * 通过依赖注入获取 `CollaborationService` 的实例。
     *
     * @param CollaborationService $collaborationService Wiki 协作服务
     */
    public function __construct(CollaborationService $collaborationService)
    {
        $this->collaborationService = $collaborationService;
    }

    /**
     * 处理 WikiPageVersionUpdated 事件。
     *
     * 当 Wiki 页面版本更新时，此方法被调用。它会检查所有当前编辑者，
     * 判断是否存在基于旧版本进行编辑的用户（即“过时编辑者”）。
     * 如果存在过时编辑者，则设置页面的临时编辑锁；否则，移除该临时锁。
     *
     * @param WikiPageVersionUpdated $event 触发的事件实例，包含页面ID和新版本ID。
     * @return void
     */
    public function handle(WikiPageVersionUpdated $event): void
    {
        // 记录事件触发信息，用于调试和跟踪
        Log::info("HandleWikiVersionUpdate 监听器已触发，页面ID: {$event->pageId}，新版本ID: {$event->newVersionId}。");

        // 获取当前正在编辑该页面的所有用户列表
        $editors = $this->collaborationService->getEditors($event->pageId);

        // 初始化标志，用于指示是否存在过时编辑者
        $hasStaleEditors = false;

        // 遍历所有编辑者，检查他们所基于的版本是否比当前新版本旧
        foreach ($editors as $editor) {
            // 如果编辑者记录了其开始编辑时所基于的页面版本ID，并且该ID小于当前页面新版本ID，
            // 则认为该编辑者正在编辑一个“过时”的版本。
            if (isset($editor['base_version_id']) && $editor['base_version_id'] < $event->newVersionId) {
                $hasStaleEditors = true; // 发现过时编辑者
                // 记录发现过时编辑者的详细信息
                Log::info("检测到过时编辑者: 用户ID {$editor['id']} 基于版本 {$editor['base_version_id']} 编辑页面 {$event->pageId}。");
                break; // 只要找到一个过时编辑者即可，无需继续遍历
            }
        }

        // 根据是否存在过时编辑者来设置或移除页面临时锁
        if ($hasStaleEditors) {
            // 如果存在过时编辑者，设置页面临时锁定，阻止新编辑者进入
            $this->collaborationService->setTemporaryLock($event->pageId);
        } else {
            // 如果没有过时编辑者（或已无任何活跃编辑者），移除页面临时锁定
            $this->collaborationService->removeTemporaryLock($event->pageId);
        }
    }
}