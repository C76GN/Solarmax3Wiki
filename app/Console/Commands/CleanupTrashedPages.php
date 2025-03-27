<?php

namespace App\Console\Commands;

use App\Models\WikiPage;
use Illuminate\Console\Command;
use Exception;

/**
 * Wiki回收站清理命令
 * 
 * 此命令用于永久删除回收站中长时间未恢复的Wiki页面
 */
class CleanupTrashedPages extends Command
{
    /**
     * 命令名称及参数定义
     *
     * @var string
     */
    protected $signature = 'wiki:cleanup-trash {days=30 : 删除多少天前的页面}';

    /**
     * 命令描述
     *
     * @var string
     */
    protected $description = '清理回收站中长时间未恢复的页面';

    /**
     * 执行命令
     *
     * @return int 返回状态码，0表示成功，非0表示失败
     */
    public function handle(): int
    {
        // 获取并验证参数
        $days = (int) $this->argument('days');
        if ($days < 1) {
            $this->error('天数必须大于0');
            return 1;
        }

        // 计算截止日期
        $date = now()->subDays($days);
        
        // 获取符合条件的页面数量
        $count = WikiPage::onlyTrashed()
            ->where('deleted_at', '<', $date)
            ->count();

        // 没有需要清理的页面则直接返回
        if ($count === 0) {
            $this->info("没有需要清理的页面");
            return 0;
        }

        // 确认操作
        if (!$this->confirm("将永久删除 {$count} 个超过 {$days} 天的已删除页面，是否继续?")) {
            return 0;
        }

        // 批量删除页面
        $deleted = $this->performDeletion($date);
        
        $this->info("已永久删除 {$deleted} 个页面");
        return 0;
    }

    /**
     * 执行实际的删除操作
     *
     * @param \Illuminate\Support\Carbon $date 截止日期
     * @return int 成功删除的页面数量
     */
    private function performDeletion($date): int
    {
        $deleted = 0;
        
        WikiPage::onlyTrashed()
            ->where('deleted_at', '<', $date)
            ->chunk(100, function ($pages) use (&$deleted) {
                foreach ($pages as $page) {
                    try {
                        // 删除页面及其相关引用
                        $page->outgoingReferences()->forceDelete();
                        $page->incomingReferences()->forceDelete();
                        $page->forceDelete();
                        $deleted++;
                    } catch (Exception $e) {
                        $this->error("删除页面 ID:{$page->id} 失败: {$e->getMessage()}");
                    }
                }
            });
            
        return $deleted;
    }
}