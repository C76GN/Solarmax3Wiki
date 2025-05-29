<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Jfcherng\Diff\Differ;
use Jfcherng\Diff\Renderer\Html\SideBySide as JfcherngSideBySideRenderer;

/**
 * 差异服务
 * 提供文本冲突检测和 HTML 差异视图生成功能。
 */
class DiffService
{
    /**
     * 检查是否存在编辑冲突。
     *
     * 基于三方合并原则，比较基础版本、当前数据库版本和用户提交的新内容。
     * 如果同一行在两个版本中相对于基础版本都被修改，并且修改后的内容不同，则认为存在冲突。
     * 也处理一方修改而另一方删除的情况。
     *
     * @param  string  $baseContent     基础版本内容
     * @param  string  $currentContent  当前数据库中的内容
     * @param  string  $newContent      用户提交的新内容
     * @return bool 如果检测到冲突则返回 true，否则返回 false。
     */
    public function hasConflict(string $baseContent, string $currentContent, string $newContent): bool
    {
        // 如果用户提交的内容与基础内容相同，或与当前内容相同，则无冲突。
        if ($newContent === $baseContent || $newContent === $currentContent) {
            return false;
        }

        // 如果当前内容与基础内容相同，表示用户编辑期间无他人修改，也无冲突。
        if ($currentContent === $baseContent) {
            return false;
        }

        // 将内容按行分割，进行行级比较。
        $baseLines = explode("\n", $baseContent);
        $currentLines = explode("\n", $currentContent);
        $newLines = explode("\n", $newContent);

        $maxLen = max(count($baseLines), count($currentLines), count($newLines));

        for ($i = 0; $i < $maxLen; $i++) {
            $lineBase = $baseLines[$i] ?? null;
            $lineCurrent = $currentLines[$i] ?? null;
            $lineNew = $newLines[$i] ?? null;

            // 检查当前版本和新版本是否都修改了基础版本的该行。
            $currentChanged = ($lineBase !== $lineCurrent);
            $newChanged = ($lineBase !== $lineNew);

            // 如果两者都修改了，并且修改后的内容不同，则视为冲突。
            if ($currentChanged && $newChanged && $lineCurrent !== $lineNew) {
                Log::debug('Conflict detected at line '.($i + 1).": Modified differently. Base='{$lineBase}', Current='{$lineCurrent}', New='{$lineNew}'");
                return true;
            }

            // 检查一方修改，另一方删除的情况。
            // 当前版本修改了该行，但新版本删除了该行（相对于基础版本）。
            if ($currentChanged && $lineNew === null && $lineBase !== null) {
                Log::debug('Conflict detected at line '.($i + 1).": Deleted by New, but modified by Current. Base='{$lineBase}', Current='{$lineCurrent}'");
                return true;
            }
            // 新版本修改了该行，但当前版本删除了该行（相对于基础版本）。
            if ($newChanged && $lineCurrent === null && $lineBase !== null) {
                Log::debug('Conflict detected at line '.($i + 1).": Deleted by Current, but modified by New. Base='{$lineBase}', New='{$lineNew}'");
                return true;
            }
        }

        return false;
    }

    /**
     * 生成两个文本内容之间的 HTML 差异视图 (并排显示)。
     * 优先使用 `jfcherng/php-diff` 库，如果该库不可用，则回退到 `phpspec/php-diff`。
     *
     * @param  string  $oldContent  旧版本内容
     * @param  string  $newContent  新版本内容
     * @return string HTML 格式的差异视图，或在出错时返回错误消息的 HTML。
     */
    public function generateDiffHtml(string $oldContent, string $newContent): string
    {
        // 优先尝试使用 jfcherng/php-diff 库。
        if (class_exists(Differ::class) && class_exists(JfcherngSideBySideRenderer::class)) {
            try {
                // 配置差异对比和渲染器选项。
                $differOptions = ['ignoreWhitespace' => true, 'context' => 3];
                $rendererOptions = [
                    'detailLevel' => 'word',
                    'language' => 'eng',
                    'lineNumbers' => true,
                    'wrapLock' => false,
                    'lineChangeIndicator' => '↕',
                    'withoutCopyright' => true,
                ];

                $differ = new Differ(explode("\n", $oldContent), explode("\n", $newContent), $differOptions);
                $renderer = new JfcherngSideBySideRenderer($rendererOptions);

                return $renderer->render($differ);
            } catch (\Throwable $e) {
                Log::error('Error generating diff HTML using jfcherng/php-diff: '.$e->getMessage());
                return "<div class='p-4 bg-red-100 text-red-700'>生成差异视图时出错(jfcherng): ".htmlspecialchars($e->getMessage()).'</div>';
            }
        } else {
            Log::warning('jfcherng/php-diff 库不可用，回退到 phpspec/php-diff。');
        }

        // 回退到 phpspec/php-diff 库。
        try {
            // 检查并加载 phpspec/php-diff 库所需文件。
            if (! class_exists('Diff', false)) {
                $diffLibPath = base_path('vendor/phpspec/php-diff/lib/Diff.php');
                if (file_exists($diffLibPath)) {
                    @include_once $diffLibPath;
                } else {
                    throw new \Exception('php-diff 核心文件未找到: '.$diffLibPath);
                }
            }
            if (! class_exists('Diff_Renderer_Html_SideBySide', false)) {
                $rendererPath = base_path('vendor/phpspec/php-diff/lib/Diff/Renderer/Html/SideBySide.php');
                if (file_exists($rendererPath)) {
                    @include_once $rendererPath;
                } else {
                    throw new \Exception('php-diff SideBySide 渲染器文件未找到: '.$rendererPath);
                }
            }

            // 再次检查所需类是否已加载。
            if (! class_exists('Diff') || ! class_exists('Diff_Renderer_Html_SideBySide')) {
                throw new \Exception('phpspec/php-diff 依赖类未能加载。');
            }

            // 配置差异对比选项。
            $diffOptions = ['ignoreWhitespace' => true, 'ignoreCase' => false];
            $diff = new \Diff(explode("\n", $oldContent), explode("\n", $newContent), $diffOptions);

            // 使用 phpspec 的 SideBySide 渲染器。
            $renderer = new \Diff_Renderer_Html_SideBySide([]);
            $diffOutput = $diff->render($renderer);

            if ($diffOutput === false || $diffOutput === null) {
                throw new \Exception('phpspec/php-diff 渲染器未能生成输出。');
            }

            return $diffOutput;
        } catch (\Throwable $e) {
            Log::error('Error generating diff HTML using phpspec/php-diff: '.$e->getMessage()."\n".$e->getTraceAsString());
            return "<div class='p-4 bg-red-100 text-red-700'>生成差异视图时发生内部错误，请稍后重试。</div>";
        }
    }
}