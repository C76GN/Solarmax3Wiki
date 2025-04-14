<?php

namespace App\Console\Commands;

use App\Models\WikiPage;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PurgeOldWikiPages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    // 定义命令签名，可以添加选项，例如 --days=30
    protected $signature = 'wiki:purge-trash {--days=30 : 保留多少天内的已删除页面}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '永久删除回收站中超过指定天数的 Wiki 页面';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = (int) $this->option('days');
        if ($days <= 0) {
            $this->error('保留天数必须大于 0。');

            return 1;
        }

        $threshold = Carbon::now()->subDays($days);
        $this->info("开始清理 {$days} 天前移入回收站的 Wiki 页面 (删除于 {$threshold->toDateTimeString()} 之前)...");

        $count = 0;
        // 分块处理，防止内存溢出
        WikiPage::onlyTrashed()
            ->where('deleted_at', '<=', $threshold)
            ->chunkById(100, function ($pages) use (&$count) {
                foreach ($pages as $page) {
                    DB::beginTransaction();
                    try {
                        // 在这里可以添加删除关联数据的逻辑，如果需要的话
                        // $page->versions()->delete();
                        // $page->comments()->delete();
                        // ...

                        $page->forceDelete(); // 永久删除
                        $this->line("已永久删除页面 ID: {$page->id}, 标题: {$page->title}");
                        // 注意：这里无法记录操作日志，因为没有请求上下文
                        Log::info("Wiki page {$page->id} ('{$page->title}') permanently deleted by scheduled task.");
                        $count++;
                        DB::commit();
                    } catch (\Exception $e) {
                        DB::rollBack();
                        Log::error("Error force deleting page {$page->id} via task: ".$e->getMessage());
                        $this->error("删除页面 ID: {$page->id} 时出错: ".$e->getMessage());
                    }
                }
            });

        $this->info("清理完成。共永久删除了 {$count} 个页面。");

        return 0;
    }
}
