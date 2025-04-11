<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Jfcherng\Diff\Differ;
use Jfcherng\Diff\Renderer\Html\SideBySide as JfcherngSideBySideRenderer;

class DiffService
{
    /**
     * 检查是否存在编辑冲突（三方合并场景）
     *
     * @param  string  $baseContent  基础版本内容
     * @param  string  $currentContent  当前数据库中的版本内容
     * @param  string  $newContent  用户提交的新内容
     */
    public function hasConflict(string $baseContent, string $currentContent, string $newContent): bool
    {
        // 如果新内容和基础版本相同，说明用户没有做修改或撤销了修改，不算冲突
        if ($newContent === $baseContent) {
            return false;
        }

        // 如果当前数据库版本和基础版本相同，说明在用户编辑期间其他人没有修改，直接接受新内容，不算冲突
        if ($currentContent === $baseContent) {
            return false;
        }

        // 如果新内容和当前数据库内容相同，说明可能是重复提交或无效提交，不算冲突
        if ($newContent === $currentContent) {
            // 可以考虑记录日志或返回特定状态
            return false;
        }

        // 简单的三方合并冲突检测：
        // 如果同一行在 currentContent 和 newContent 中相对于 baseContent 都被修改了，
        // 并且修改的内容不同，则认为是冲突。
        // 注意：这是一个基础的行级比较，可能无法覆盖所有复杂的合并冲突场景。
        // 更复杂的场景可能需要更高级的 diff 或合并库。

        $baseLines = explode("\n", $baseContent);
        $currentLines = explode("\n", $currentContent);
        $newLines = explode("\n", $newContent);

        $lenBase = count($baseLines);
        $lenCurrent = count($currentLines);
        $lenNew = count($newLines);
        $maxLen = max($lenBase, $lenCurrent, $lenNew);

        for ($i = 0; $i < $maxLen; $i++) {
            $lineBase = $baseLines[$i] ?? null;
            $lineCurrent = $currentLines[$i] ?? null;
            $lineNew = $newLines[$i] ?? null;

            // 检查当前版本和新版本是否都修改了基础版本的该行
            $currentChanged = ($lineBase !== $lineCurrent);
            $newChanged = ($lineBase !== $lineNew);

            // 如果两者都修改了，并且修改后的内容不同，则冲突
            if ($currentChanged && $newChanged && $lineCurrent !== $lineNew) {
                Log::debug('Conflict detected at line '.($i + 1).": Modified differently. Base='{$lineBase}', Current='{$lineCurrent}', New='{$lineNew}'");

                return true;
            }

            // 检查一方修改，另一方删除的情况
            // Case 1: Current modified, New deleted the line (compared to base)
            if ($currentChanged && $lineNew === null && $lineBase !== null) {
                Log::debug('Conflict detected at line '.($i + 1).": Deleted by New, but modified by Current. Base='{$lineBase}', Current='{$lineCurrent}'");

                return true;
            }
            // Case 2: New modified, Current deleted the line (compared to base)
            if ($newChanged && $lineCurrent === null && $lineBase !== null) {
                Log::debug('Conflict detected at line '.($i + 1).": Deleted by Current, but modified by New. Base='{$lineBase}', New='{$lineNew}'");

                return true;
            }

            // 可选：更复杂的场景，例如并发插入不同内容等，可能需要更复杂的逻辑
            // 例如，检查是否在基础版本不存在的地方（null）同时插入了不同的内容
            if ($lineBase === null && $lineCurrent !== null && $lineNew !== null && $lineCurrent !== $lineNew) {
                Log::debug('Potential conflict detected at line '.($i + 1).": Concurrent insertion? Current='{$lineCurrent}', New='{$lineNew}'");
                // 根据业务逻辑决定这是否算冲突，通常插入不同内容不算直接冲突，但可能需要合并
                // return true; // 如果认为并发插入不同内容也是冲突
            }
        }

        // 没有检测到明显冲突
        return false;
    }

    /**
     * 生成两个文本内容之间的 HTML 差异视图 (Side by Side)。
     * 优先使用 jfcherng/php-diff 库。
     *
     * @param  string  $oldContent  旧版本内容
     * @param  string  $newContent  新版本内容
     * @return string HTML 格式的差异视图，或在出错时返回错误消息的 HTML。
     */
    public function generateDiffHtml(string $oldContent, string $newContent): string
    {
        // 优先使用 jfcherng/php-diff
        if (class_exists(Differ::class) && class_exists(JfcherngSideBySideRenderer::class)) {
            try {
                $differOptions = [
                    'ignoreWhitespace' => true, // 忽略空白差异
                    'context' => 3,          // 上下文行数, 设为 null 或 -1 显示所有行
                    // 'ignoreCase' => false,      // 是否忽略大小写，默认为 false
                    // 'ignoreLineEnding' => false, // 是否忽略行尾差异，默认为 false
                ];
                $rendererOptions = [
                    'detailLevel' => 'word',       // 'line', 'word', 'char' - 差异对比粒度
                    'language' => 'eng',         // 'eng' 或 'chs' 摘要语言
                    'lineNumbers' => true,         // 显示行号
                    'wrapLock' => false,        // 不自动换行长内容，允许水平滚动
                    'lineChangeIndicator' => '↕', // 行变化指示符
                    'withoutCopyright' => true, // 不显示库的版权信息
                ];

                $differ = new Differ(explode("\n", $oldContent), explode("\n", $newContent), $differOptions);

                // 明确使用 SideBySide Renderer
                $renderer = new JfcherngSideBySideRenderer($rendererOptions);

                return $renderer->render($differ);
            } catch (\Throwable $e) {
                Log::error('Error generating diff HTML using jfcherng/php-diff: '.$e->getMessage());

                return "<div class='p-4 bg-red-100 text-red-700'>生成差异视图时出错(jfcherng): ".htmlspecialchars($e->getMessage()).'</div>';
            }
        } else {
            Log::warning('jfcherng/php-diff library not found or required classes are missing. Falling back to phpspec/php-diff.');
        }

        // 回退到 phpspec/php-diff
        // 注意：phpspec/php-diff 的渲染效果可能不如 jfcherng/php-diff
        try {
            // 确保 Diff 和 Renderer 类已加载 (如果 composer autoload 正常，通常不需要手动 require)
            if (! class_exists('Diff', false)) {
                $diffLibPath = base_path('vendor/phpspec/php-diff/lib/Diff.php');
                if (file_exists($diffLibPath)) {
                    @include_once $diffLibPath; // 使用 @ 避免重复包含警告
                } else {
                    throw new \Exception('php-diff library core file not found at '.$diffLibPath);
                }
            }
            if (! class_exists('Diff_Renderer_Html_SideBySide', false)) {
                $rendererPath = base_path('vendor/phpspec/php-diff/lib/Diff/Renderer/Html/SideBySide.php');
                if (file_exists($rendererPath)) {
                    @include_once $rendererPath;
                } else {
                    throw new \Exception('php-diff SideBySide renderer file not found at '.$rendererPath);
                }
            }

            // 再次检查类是否存在
            if (! class_exists('Diff') || ! class_exists('Diff_Renderer_Html_SideBySide')) {
                throw new \Exception('Required classes from phpspec/php-diff could not be loaded after require_once.');
            }

            $diffOptions = [
                'ignoreWhitespace' => true,
                'ignoreCase' => false, // 通常不忽略大小写
            ];

            $diff = new \Diff(explode("\n", $oldContent), explode("\n", $newContent), $diffOptions);

            // 使用 phpspec 的 SideBySide 渲染器
            $renderer = new \Diff_Renderer_Html_SideBySide([]); // 构造函数可能不需要参数
            $diffOutput = $diff->render($renderer);

            if ($diffOutput === false || $diffOutput === null) {
                throw new \Exception('phpspec/php-diff renderer failed to generate output.');
            }

            return $diffOutput;
        } catch (\Throwable $e) {
            Log::error('Error generating diff HTML using phpspec/php-diff: '.$e->getMessage()."\n".$e->getTraceAsString());

            // 提供一个用户友好的错误消息
            return "<div class='p-4 bg-red-100 text-red-700'>生成差异视图时发生内部错误，请稍后重试。</div>";
        }
    }
}
