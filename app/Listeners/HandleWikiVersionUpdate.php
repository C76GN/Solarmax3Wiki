<?php

namespace App\Listeners;

use App\Events\WikiPageVersionUpdated;
use App\Services\CollaborationService;
use Illuminate\Contracts\Queue\ShouldQueue; // 可选，如果希望异步处理
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class HandleWikiVersionUpdate // implements ShouldQueue // 可选
{
    use InteractsWithQueue; // 可选

    protected CollaborationService $collaborationService;

    public function __construct(CollaborationService $collaborationService)
    {
        $this->collaborationService = $collaborationService;
    }

    public function handle(WikiPageVersionUpdated $event): void
    {
        Log::info("HandleWikiVersionUpdate listener triggered for page {$event->pageId}, new version {$event->newVersionId}.");

        $editors = $this->collaborationService->getEditors($event->pageId);

        // 检查是否有其他编辑器仍在编辑旧版本
        $hasStaleEditors = false;
        foreach ($editors as $editor) {
            // 假设 registerEditor 保存了 base_version_id
            if (isset($editor['base_version_id']) && $editor['base_version_id'] < $event->newVersionId) {
                $hasStaleEditors = true;
                Log::info("Stale editor detected: User {$editor['id']} based on version {$editor['base_version_id']} for page {$event->pageId}.");
                break;
            }
        }

        if ($hasStaleEditors) {
            // 如果有编辑器在编辑旧版本，设置临时锁
            $this->collaborationService->setTemporaryLock($event->pageId);
        } else {
            // 如果没有编辑器在编辑旧版本（或者没有编辑器了），确保移除锁
            $this->collaborationService->removeTemporaryLock($event->pageId);
        }
    }
}
