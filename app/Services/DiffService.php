<?php

namespace App\Services;

/**
 * 差异对比服务
 * 用于比较文本差异，主要应用于检测Wiki页面内容的编辑冲突
 */
class DiffService
{
    /**
     * 检测两个文本内容之间是否存在冲突
     * 
     * @param string $baseContent 基准内容
     * @param string $userAContent 用户A的内容
     * @param string $userBContent 用户B的内容
     * @return bool 是否存在冲突
     */
    public function hasConflict(string $baseContent, string $userAContent, string $userBContent): bool
    {
        // 获取用户A和基准内容的差异行
        $diffA = $this->getDiffLines($baseContent, $userAContent);
        
        // 获取用户B和基准内容的差异行
        $diffB = $this->getDiffLines($baseContent, $userBContent);
        
        // 检查是否有重叠的修改行
        return $this->hasOverlappingChanges($diffA, $diffB);
    }
    
    /**
     * 获取两个文本之间的差异行数组
     * 
     * @param string $oldContent 旧内容
     * @param string $newContent 新内容
     * @return array 差异行数组
     */
    public function getDiffLines(string $oldContent, string $newContent): array
    {
        $oldLines = explode("\n", $oldContent);
        $newLines = explode("\n", $newContent);
        
        // 使用PHP内置的diff函数比较数组
        $diff = array_diff($newLines, $oldLines);
        
        return array_keys($diff);
    }
    
    /**
     * 检查两个差异集是否有重叠的修改行
     * 
     * @param array $diffA 差异集A
     * @param array $diffB 差异集B
     * @return bool 是否有重叠
     */
    private function hasOverlappingChanges(array $diffA, array $diffB): bool
    {
        // 计算两个数组的交集
        $intersection = array_intersect($diffA, $diffB);
        
        // 如果有交集，则存在冲突
        return !empty($intersection);
    }
    
    /**
     * 生成内容差异的HTML
     * 
     * @param string $oldContent 旧内容
     * @param string $newContent 新内容
     * @return string 包含差异标记的HTML
     */
    public function generateDiffHtml(string $oldContent, string $newContent): string
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
                    $diff[] = '<ins class="bg-green-200">' . htmlspecialchars($newLine) . '</ins>';
                } elseif (empty($newLine)) {
                    $diff[] = '<del class="bg-red-200">' . htmlspecialchars($oldLine) . '</del>';
                } else {
                    $diff[] = '<del class="bg-red-200">' . htmlspecialchars($oldLine) . '</del>';
                    $diff[] = '<ins class="bg-green-200">' . htmlspecialchars($newLine) . '</ins>';
                }
            } else {
                $diff[] = htmlspecialchars($oldLine);
            }
        }
        
        return implode("<br>\n", $diff);
    }
}