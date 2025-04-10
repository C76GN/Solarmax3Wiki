<?php
namespace App\Services;

use Illuminate\Support\Facades\Log; // 引入Log

// 尝试使用 jfcherng/php-diff (如果安装了)
use Jfcherng\Diff\Differ;
use Jfcherng\Diff\DiffHelper;
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
     * @param string $baseContent The original content before edits (user started from).
     * @param string $currentContent The content currently in the database (potentially changed by others).
     * @param string $newContent The content being submitted (user's proposed changes).
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
                 Log::debug("Conflict detected at line " . ($i + 1) . ": Modified differently. Base='{$lineBase}', Current='{$lineCurrent}', New='{$lineNew}'");
                return true;
            }

             // Case 2: One modified a line that the other deleted.
            if ($currentChanged && $lineNew === null && $lineBase !== null) {
                 Log::debug("Conflict detected at line " . ($i + 1) . ": Deleted by New, but modified by Current. Base='{$lineBase}', Current='{$lineCurrent}'");
                return true;
            }
            if ($newChanged && $lineCurrent === null && $lineBase !== null) {
                 Log::debug("Conflict detected at line " . ($i + 1) . ": Deleted by Current, but modified by New. Base='{$lineBase}', New='{$lineNew}'");
                return true;
            }

             // Case 3: Concurrent insertions at the same position (Harder to detect perfectly without proper 3-way merge)
             // This basic check might miss adjacent insertions.
             if ($lineBase === null && $lineCurrent !== null && $lineNew !== null && $lineCurrent !== $lineNew) {
                 // Both inserted at the same line index, potentially conflicting insertions
                  Log::debug("Potential conflict detected at line " . ($i + 1) . ": Concurrent insertion? Current='{$lineCurrent}', New='{$lineNew}'");
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
     * @param string $oldContent
     * @param string $newContent
     * @return string HTML diff view or error message.
     */
    public function generateDiffHtml(string $oldContent, string $newContent): string
    {
        // --- Try jfcherng/php-diff first ---
        if (class_exists(Differ::class) && class_exists(\Jfcherng\Diff\Renderer\Html\SideBySide::class)) {
            try {
                 $differOptions = [
                     'ignoreWhitespace' => true, // Example option
                     'context' => 3,           // Example option
                 ];
                 $rendererOptions = [
                    'detailLevel' => 'word', // 'line', 'char', 'word'
                    'language' => 'eng',
                    'lineNumbers' => true,
                 ];

                $differ = new Differ(explode("\n", $oldContent), explode("\n", $newContent), $differOptions);
                // $renderer = new \Jfcherng\Diff\Renderer\Html\Unified($rendererOptions);
                $renderer = new \Jfcherng\Diff\Renderer\Html\SideBySide($rendererOptions); // More common for conflict view

                return $renderer->render($differ);
            } catch (\Throwable $e) {
                 Log::error("Error generating diff HTML using jfcherng/php-diff: " . $e->getMessage());
                 // Fall through to the next method if this fails
            }
        }

         // --- Fallback to phpspec/php-diff ---
        try {
             // Ensure autoloader can find Diff classes (manual require_once as fallback)
             if (!class_exists('\Diff')) {
                 $diffLibPath = base_path('vendor/phpspec/php-diff/lib/Diff.php');
                 if (file_exists($diffLibPath)) { require_once $diffLibPath; }
                 else { throw new \Exception("php-diff library not found."); }
             }
             if (!class_exists('\Diff_Renderer_Html_SideBySide')) {
                 $rendererPath = base_path('vendor/phpspec/php-diff/lib/Diff/Renderer/Html/SideBySide.php');
                 if (file_exists($rendererPath)) { require_once $rendererPath; }
                 else { throw new \Exception("php-diff SideBySide renderer not found."); }
             }

            $diffOptions = [
                'ignoreWhitespace' => true,
                'ignoreCase' => false,
            ];
            $diff = new \Diff(explode("\n", $oldContent), explode("\n", $newContent), $diffOptions);

            $rendererOptions = [
                // Options specific to phpspec renderer
            ];
            $renderer = new \Diff_Renderer_Html_SideBySide($rendererOptions);
            return $diff->render($renderer);

        } catch (\Throwable $e) {
             Log::error("Error generating diff HTML using phpspec/php-diff: " . $e->getMessage() . "\n" . $e->getTraceAsString());
             return "<div class='p-4 bg-red-100 text-red-700'>生成差异视图时出错: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    }

    // Other potential diff methods like generateInlineDiffHtml...
    // ... (Keep the existing generateInlineDiffHtml and its helpers if you use them elsewhere)
}