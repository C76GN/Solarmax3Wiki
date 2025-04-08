<?php
namespace App\Services;

class DiffService
{
    /**
     * 检查是否存在冲突
     */
    public function hasConflict(string $baseContent, string $userAContent, string $userBContent): bool
    {
        // 更精确的冲突检测算法
        $baseLines = explode("\n", $baseContent);
        $userALines = explode("\n", $userAContent);
        $userBLines = explode("\n", $userBContent);
        
        $changesA = $this->calculateChanges($baseLines, $userALines);
        $changesB = $this->calculateChanges($baseLines, $userBLines);
        
        // 检查是否有重叠修改
        foreach ($changesA as $lineNum => $changeType) {
            if (isset($changesB[$lineNum]) && $changeType != 'unchanged') {
                // 如果A和B都修改了同一行，检查修改是否相同
                if ($userALines[$lineNum] !== $userBLines[$lineNum]) {
                    return true; // 存在冲突
                }
            }
        }
        
        // 检查新增行是否导致冲突
        $addedLinesA = array_filter($changesA, function($change) {
            return $change === 'added';
        });
        
        $addedLinesB = array_filter($changesB, function($change) {
            return $change === 'added';
        });
        
        $addedLineConflict = array_intersect_key($addedLinesA, $addedLinesB);
        if (!empty($addedLineConflict)) {
            return true;
        }
        
        return false;
    }

    private function calculateChanges(array $oldLines, array $newLines): array
    {
        $changes = [];
        $max = max(count($oldLines), count($newLines));
        
        for ($i = 0; $i < $max; $i++) {
            if ($i >= count($oldLines)) {
                $changes[$i] = 'added';
            } elseif ($i >= count($newLines)) {
                $changes[$i] = 'deleted';
            } elseif ($oldLines[$i] !== $newLines[$i]) {
                $changes[$i] = 'modified';
            } else {
                $changes[$i] = 'unchanged';
            }
        }
        
        return $changes;
    }

    private function calculateLineDifferences(array $oldLines, array $newLines): array
    {
        $changes = [];
        $max = max(count($oldLines), count($newLines));
        
        for ($i = 0; $i < $max; $i++) {
            $oldLine = $oldLines[$i] ?? '';
            $newLine = $newLines[$i] ?? '';
            
            if ($oldLine !== $newLine) {
                $changes[$i] = $newLine;
            }
        }
        
        return $changes;
    }

    /**
     * 获取两个内容之间的差异行
     */
    public function getDiffLines(string $oldContent, string $newContent): array
    {
        $oldLines = explode("\n", $oldContent);
        $newLines = explode("\n", $newContent);
        
        // 获取更精确的行差异
        $differ = new \Diff_TextDiffer();
        $diff = $differ->diff($oldLines, $newLines);
        
        $changedLines = [];
        foreach ($diff as $index => $change) {
            if ($change[1] !== 0) { // 有变化的行
                $changedLines[] = $index;
            }
        }
        
        return $changedLines;
    }

    /**
     * 检查两个差异集是否有重叠
     */
    private function hasOverlappingChanges(array $diffA, array $diffB): bool
    {
        $intersection = array_intersect($diffA, $diffB);
        return !empty($intersection);
    }

    /**
     * 生成更详细的HTML差异视图
     */
    public function generateDiffHtml(string $oldContent, string $newContent): string
    {
        // 使用更专业的差异比较库
        require_once base_path('vendor/phpspec/php-diff/lib/Diff.php');
        require_once base_path('vendor/phpspec/php-diff/lib/Diff/Renderer/Html/SideBySide.php');
        
        $diff = new \Diff(explode("\n", $oldContent), explode("\n", $newContent), []);
        $renderer = new \Diff_Renderer_Html_SideBySide();
        
        return $diff->render($renderer);
    }
    
    /**
     * 生成行内差异HTML
     */
    public function generateInlineDiffHtml(string $oldContent, string $newContent): string
    {
        $oldLines = explode("\n", $oldContent);
        $newLines = explode("\n", $newContent);
        
        $diff = [];
        $maxLen = max(count($oldLines), count($newLines));
        
        for ($i = 0; $i < $maxLen; $i++) {
            $oldLine = $oldLines[$i] ?? '';
            $newLine = $newLines[$i] ?? '';
            
            if ($oldLine !== $newLine) {
                if (empty($oldLine)) {
                    $diff[] = '<div class="line-added"><ins class="bg-green-200">' . htmlspecialchars($newLine) . '</ins></div>';
                } elseif (empty($newLine)) {
                    $diff[] = '<div class="line-removed"><del class="bg-red-200">' . htmlspecialchars($oldLine) . '</del></div>';
                } else {
                    // 尝试进行词语级别的差异对比
                    $wordDiff = $this->generateWordLevelDiff($oldLine, $newLine);
                    $diff[] = '<div class="line-changed">' . $wordDiff . '</div>';
                }
            } else {
                $diff[] = '<div class="line-unchanged">' . htmlspecialchars($oldLine) . '</div>';
            }
        }
        
        return implode("\n", $diff);
    }
    
    /**
     * 生成词语级别的差异
     */
    private function generateWordLevelDiff(string $oldLine, string $newLine): string
    {
        $oldWords = preg_split('/(\s+)/', $oldLine, -1, PREG_SPLIT_DELIM_CAPTURE);
        $newWords = preg_split('/(\s+)/', $newLine, -1, PREG_SPLIT_DELIM_CAPTURE);
        
        $diffWords = $this->computeWordDiff($oldWords, $newWords);
        
        $result = '';
        foreach ($diffWords as [$op, $text]) {
            if ($op === -1) {
                $result .= '<del class="bg-red-200">' . htmlspecialchars($text) . '</del>';
            } elseif ($op === 1) {
                $result .= '<ins class="bg-green-200">' . htmlspecialchars($text) . '</ins>';
            } else {
                $result .= htmlspecialchars($text);
            }
        }
        
        return $result;
    }
    
    /**
     * 计算词语级别的差异
     */
    private function computeWordDiff(array $oldWords, array $newWords): array
    {
        // 这里使用简单的LCS (最长公共子序列) 算法
        // 实际项目中可以使用更专业的差异算法库
        
        $matrix = [];
        $maxlen = 0;
        $omax = 0;
        $nmax = 0;
        
        foreach ($oldWords as $oindex => $ovalue) {
            foreach ($newWords as $nindex => $nvalue) {
                if ($ovalue === $nvalue) {
                    $value = ($matrix[$oindex - 1][$nindex - 1] ?? 0) + 1;
                    $matrix[$oindex][$nindex] = $value;
                    
                    if ($value > $maxlen) {
                        $maxlen = $value;
                        $omax = $oindex - $maxlen + 1;
                        $nmax = $nindex - $maxlen + 1;
                    }
                } else {
                    $matrix[$oindex][$nindex] = 0;
                }
            }
        }
        
        if ($maxlen === 0) {
            $result = [];
            foreach ($oldWords as $word) {
                $result[] = [-1, $word];
            }
            foreach ($newWords as $word) {
                $result[] = [1, $word];
            }
            return $result;
        }
        
        return array_merge(
            $this->computeWordDiff(
                array_slice($oldWords, 0, $omax),
                array_slice($newWords, 0, $nmax)
            ),
            array_map(function ($i) use ($oldWords, $omax) {
                return [0, $oldWords[$omax + $i]];
            }, range(0, $maxlen - 1)),
            $this->computeWordDiff(
                array_slice($oldWords, $omax + $maxlen),
                array_slice($newWords, $nmax + $maxlen)
            )
        );
    }
}