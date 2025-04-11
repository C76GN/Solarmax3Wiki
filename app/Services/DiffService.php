<?php

namespace App\Services;

use Illuminate\Support\Facades\Log; // 引入Log
// 尝试使用 jfcherng/php-diff (如果安装了)
use Jfcherng\Diff\Differ;
use Jfcherng\Diff\Renderer\Html\SideBySide as JfcherngSideBySideRenderer;

// use Jfcherng\Diff\Renderer\Html\SideBySide; // 或者 Unified
// use Jfcherng\Diff\Renderer\Html\Unified; // 或者 Unified

class DiffService
{
    /**
     * Checks for conflicts between two sets of changes based on a common ancestor.
     *
     * This implementation is basic. A 3-way merge algorithm would be more robust.
     * It primarily checks if both users modified the *same line* with different content,
     * or if one user modified a line deleted by the other. It might miss some complex conflicts.
     *
     * @param  string  $baseContent  The original content before edits (user started from).
     * @param  string  $currentContent  The content currently in the database (potentially changed by others).
     * @param  string  $newContent  The content being submitted (user's proposed changes).
     * @return bool True if a conflict is detected, false otherwise.
     */
    public function hasConflict(string $baseContent, string $currentContent, string $newContent): bool
    {
        // Optimization: If the content hasn't actually changed from the base, no conflict.
        if ($newContent === $baseContent) {
            // Log::debug("hasConflict: newContent is same as baseContent. No conflict.");
            return false;
        }
        // Optimization: If current content is same as base, it means no one else edited concurrently.
        if ($currentContent === $baseContent) {
            // Log::debug("hasConflict: currentContent is same as baseContent. No conflict.");
            return false;
        }
        // Optimization: If the proposed new content is identical to what's already current, no conflict.
        if ($newContent === $currentContent) {
            // Log::debug("hasConflict: newContent is same as currentContent. No conflict (idempotent save).");
            return false;
        }

        // Line-based comparison (Basic approach)
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

            $currentChanged = ($lineBase !== $lineCurrent);
            $newChanged = ($lineBase !== $lineNew);

            // Case 1: Both modified the same original line, but differently.
            if ($lineBase !== null && $currentChanged && $newChanged && $lineCurrent !== $lineNew) {
                Log::debug('Conflict detected at line '.($i + 1).": Modified differently. Base='{$lineBase}', Current='{$lineCurrent}', New='{$lineNew}'");

                return true;
            }

            // Case 2: One modified a line that the other deleted.
            if ($currentChanged && $lineNew === null && $lineBase !== null) {
                Log::debug('Conflict detected at line '.($i + 1).": Deleted by New, but modified by Current. Base='{$lineBase}', Current='{$lineCurrent}'");

                return true;
            }
            if ($newChanged && $lineCurrent === null && $lineBase !== null) {
                Log::debug('Conflict detected at line '.($i + 1).": Deleted by Current, but modified by New. Base='{$lineBase}', New='{$lineNew}'");

                return true;
            }

            // Case 3: Concurrent insertions at the same position (Harder to detect perfectly without proper 3-way merge)
            // This basic check might miss adjacent insertions.
            if ($lineBase === null && $lineCurrent !== null && $lineNew !== null && $lineCurrent !== $lineNew) {
                // Both inserted at the same line index, potentially conflicting insertions
                Log::debug('Potential conflict detected at line '.($i + 1).": Concurrent insertion? Current='{$lineCurrent}', New='{$lineNew}'");
                // Decide if this constitutes a conflict based on requirements. For safety, maybe return true.
                // return true;
            }
        }

        // Log::debug("No direct line conflicts detected by basic hasConflict check.");
        // If no direct conflicts found based on these simple rules.
        return false;
    }

    /**
     * Generates an HTML representation of the differences between two strings.
     * Attempts to use jfcherng/php-diff if available, otherwise falls back to phpspec/php-diff.
     *
     * @return string HTML diff view or error message.
     */
    public function generateDiffHtml(string $oldContent, string $newContent): string
    {
        // 优先使用 jfcherng/php-diff
        if (class_exists(Differ::class) && class_exists(JfcherngSideBySideRenderer::class)) {
            try {
                $differOptions = [
                    'ignoreWhitespace' => true,
                    'context' => 3, // 显示上下文行数
                ];
                $rendererOptions = [
                    'detailLevel' => 'word', // 对比到单词级别
                    'language' => 'eng',    // 语言设置（可能影响特定语言的处理）
                    'lineNumbers' => true,   // 显示行号
                ];

                $differ = new Differ(explode("\n", $oldContent), explode("\n", $newContent), $differOptions);
                // 使用别名 JfcherngSideBySideRenderer
                $renderer = new JfcherngSideBySideRenderer($rendererOptions);

                return $renderer->render($differ);
            } catch (\Throwable $e) {
                Log::error('Error generating diff HTML using jfcherng/php-diff: '.$e->getMessage());

                // 如果 jfcherng 失败，可以选择不尝试旧库，直接返回错误
                return "<div class='p-4 bg-red-100 text-red-700'>生成差异视图时出错(jfcherng): ".htmlspecialchars($e->getMessage()).'</div>';
                // 或者继续尝试旧库（如下）
            }
        } else {
            Log::warning('jfcherng/php-diff library not found or required classes are missing. Falling back to phpspec/php-diff.');
        }

        // 备选方案: phpspec/php-diff (如果上面的库不存在或失败)
        // 注意：这个库比较老旧，可能不再维护，建议优先使用 jfcherng
        try {
            // 尝试加载 phpspec/php-diff （如果 jfcherng 不可用）
            // 确保类在使用前已加载
            if (!class_exists('Diff', false)) {
                $diffLibPath = base_path('vendor/phpspec/php-diff/lib/Diff.php');
                if (file_exists($diffLibPath)) {
                    require_once $diffLibPath;
                } else {
                    // 可以记录更详细的错误或抛出更具体的异常
                    throw new \Exception('php-diff library core file not found at ' . $diffLibPath);
                }
            }

            if (!class_exists('Diff_Renderer_Html_SideBySide', false)) {
                $rendererPath = base_path('vendor/phpspec/php-diff/lib/Diff/Renderer/Html/SideBySide.php');
                if (file_exists($rendererPath)) {
                    require_once $rendererPath;
                } else {
                    // 可以记录更详细的错误或抛出更具体的异常
                    throw new \Exception('php-diff SideBySide renderer file not found at ' . $rendererPath);
                }
            }
            // 增加一个最终检查，确保类确实加载了
            if (!class_exists('Diff') || !class_exists('Diff_Renderer_Html_SideBySide')) {
                throw new \Exception('Required classes from phpspec/php-diff could not be loaded after require_once.');
            }
            $diffOptions = [
                'ignoreWhitespace' => true,
                'ignoreCase'       => false,
            ];
            // ... (rest of the phpspec diff logic) ...
            $diff = new \Diff(explode("\n", $oldContent), explode("\n", $newContent), $diffOptions);

            // 检查渲染器实例化是否成功
            $renderer = new \Diff_Renderer_Html_SideBySide([]); // 提供空选项数组
            $diffOutput = $diff->render($renderer);
            if ($diffOutput === false || $diffOutput === null) {
                // 渲染器可能返回 false 或 null on error
                throw new \Exception('php-diff renderer failed to generate output.');
            }
            return $diffOutput;
        } catch (\Throwable $e) {
            // 记录更详细的错误信息
            Log::error('Error generating diff HTML using phpspec/php-diff: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            // 返回更友好的错误提示
            return "<div class='p-4 bg-red-100 text-red-700'>生成差异视图时发生错误，请稍后重试。</div>";
        }
    }

    // Other potential diff methods like generateInlineDiffHtml...
    // ... (Keep the existing generateInlineDiffHtml and its helpers if you use them elsewhere)
}
