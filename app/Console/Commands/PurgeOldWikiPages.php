<?php

namespace App\Console\Commands;

use App\Models\WikiPage;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * 清理旧 Wiki 页面命令
 *
 * 此 Artisan 命令用于从回收站中永久删除超过指定天数的 Wiki 页面。
 * 这有助于定期清理数据库，防止数据膨胀。
 */
class PurgeOldWikiPages extends Command
{
    /**
     * Artisan 命令的签名。
     *
     * 定义了命令的调用方式以及可选参数：
     * - `--days`: 要保留的已删除页面的天数。超过此天数的页面将被永久删除。
     *   默认值为 30 天。
     *
     * @var string
     */
    protected $signature = 'wiki:purge-trash {--days=30 : 保留多少天内的已删除页面}';

    /**
     * Artisan 命令的简短描述。
     *
     * 该描述会在 `php artisan list` 命令的输出中显示。
     *
     * @var string
     */
    protected $description = '永久删除回收站中超过指定天数的 Wiki 页面';

    /**
     * 执行 Artisan 命令的逻辑。
     *
     * 该方法负责根据设置的天数，查询并永久删除符合条件的 Wiki 页面。
     * 为了避免内存溢出，它采用分块处理的方式进行删除。
     *
     * @return int 返回状态码：0 表示成功，非 0 表示失败。
     */
    public function handle(): int
    {
        // 获取 --days 选项的值并转换为整数。
        $days = (int) $this->option('days');

        // 验证保留天数，必须大于 0。
        if ($days <= 0) {
            $this->error('保留天数必须大于 0。');

            return 1;
        }

        // 计算删除时间阈值：当前时间减去指定的保留天数。
        $threshold = Carbon::now()->subDays($days);
        // 输出清理开始的信息和删除阈值。
        $this->info("开始清理 {$days} 天前移入回收站的 Wiki 页面 (删除于 {$threshold->toDateTimeString()} 之前)...");

        $count = 0; // 记录被删除的页面数量。

        // 查询所有已软删除（即在回收站中）且删除时间早于阈值的 Wiki 页面。
        // `chunkById(100, ...)` 用于分批处理记录，每次处理 100 条，
        // 有效地减少内存使用，避免处理大量数据时造成的性能问题。
        WikiPage::onlyTrashed() // 仅查询已软删除的记录。
            ->where('deleted_at', '<=', $threshold) // 筛选删除时间在阈值之前的页面。
            ->chunkById(100, function ($pages) use (&$count) {
                // 遍历当前批次的每个页面。
                foreach ($pages as $page) {
                    // 启动数据库事务，确保每个页面的删除操作是原子性的。
                    // 如果删除过程中出现错误，可以回滚所有更改。
                    DB::beginTransaction();
                    try {
                        // 执行 Wiki 页面的永久删除。
                        // `forceDelete()` 会绕过软删除机制，直接从数据库中移除记录及其关联。
                        $page->forceDelete();
                        // 输出删除成功的页面信息。
                        $this->line("已永久删除页面 ID: {$page->id}, 标题: {$page->title}");
                        // 记录删除操作到日志文件。
                        // 注意：此处的日志记录是命令行任务的一部分，不会触发 ActivityLog 模型中的日志特性，
                        // 因为没有 HTTP 请求上下文。
                        Log::info("Wiki page {$page->id} ('{$page->title}') permanently deleted by scheduled task.");
                        $count++; // 增加删除计数。
                        DB::commit(); // 提交事务，确认删除操作。
                    } catch (\Exception $e) {
                        DB::rollBack(); // 如果发生异常，回滚事务。
                        // 记录删除失败的错误信息到日志。
                        Log::error("Error force deleting page {$page->id} via task: ".$e->getMessage());
                        // 输出错误信息到命令行。
                        $this->error("删除页面 ID: {$page->id} 时出错: ".$e->getMessage());
                    }
                }
            });

        // 输出清理完成的总览信息。
        $this->info("清理完成。共永久删除了 {$count} 个页面。");

        // 返回零状态码，指示命令执行成功。
        return 0;
    }
}