<?php

namespace App\Services;

use Mews\Purifier\Facades\Purifier;

/**
* Wiki内容服务类
* 
* 提供Wiki内容处理的相关功能，包括内容净化、链接解析、
* 引用上下文提取和媒体处理等。
*/
class WikiContentService
{
   /**
    * 净化Wiki内容，移除潜在的恶意代码
    *
    * @param string $content 原始内容
    * @return string 净化后的内容
    */
   public function purifyContent(string $content): string
   {
       return Purifier::clean($content, 'wiki');
   }

   /**
    * 解析内容中的Wiki链接
    * 
    * 提取 [[链接名称]] 格式的链接
    *
    * @param string $content Wiki内容
    * @return array 提取的链接数组
    */
   public function parseWikiLinks(string $content): array
   {
        preg_match_all('/<span\s+data-type="wiki-link"\s+data-title="([^"]+)"[^>]*>.*?<\/span>|\\[\\[([^\\]]+)\\]\\]/', $content, $matches);
        $titles = [];
        foreach ($matches[1] as $index => $match) {
            $titles[] = !empty($match) ? $match : $matches[2][$index];
        }
       return array_filter($titles);
       return $matches[1] ?? [];
   }

   /**
    * 提取引用的上下文
    * 
    * 提取包含特定标题引用的上下文内容
    *
    * @param string $content Wiki内容
    * @param string $title 要查找的标题
    * @return string|null 上下文内容或null（如果未找到）
    */
   public function extractReferenceContext(string $content, string $title): ?string
   {
       $pattern = '/[^.]*(?:<span\s+data-type="wiki-link"\s+data-title="' . preg_quote($title, '/') . '"[^>]*>.*?<\/span>|\[\[' . preg_quote($title, '/') . '\]\])[^.]*/';
       if (preg_match($pattern, $content, $match)) {
           return trim($match[0]);
       }
       return null;
   }

   /**
    * 处理内容中的媒体文件
    * 
    * 可以对内容中的媒体引用进行处理和转换
    *
    * @param string $content Wiki内容
    * @return string 处理后的内容
    */
   public function processContentMedia(string $content): string
   {
       // 未实现具体功能，预留扩展
       return $content;
   }

   /**
     * 计算两个内容版本之间的差异
     * 
     * @param string $oldContent 旧内容
     * @param string $newContent 新内容
     * @return array 差异数组
     */
    public function calculateDiff(string $oldContent, string $newContent): array
    {
        $oldLines = preg_split('/\R/', $oldContent);
        $newLines = preg_split('/\R/', $newContent);
        
        $changes = [
            'added' => [],
            'removed' => [],
            'changed' => []
        ];
        
        // 使用PHP内置diff函数计算差异
        $diff = array_diff($newLines, $oldLines);
        $removed = array_diff($oldLines, $newLines);
        
        // 将差异添加到相应的数组中
        foreach ($diff as $line) {
            $changes['added'][] = $line;
        }
        
        foreach ($removed as $line) {
            $changes['removed'][] = $line;
        }
        
        // 查找可能被修改的行
        foreach ($oldLines as $i => $oldLine) {
            if (isset($newLines[$i]) && $oldLine !== $newLines[$i] && !in_array($oldLine, $changes['removed']) && !in_array($newLines[$i], $changes['added'])) {
                $changes['changed'][] = [
                    'old' => $oldLine,
                    'new' => $newLines[$i]
                ];
            }
        }
        
        return $changes;
    }
    
    /**
     * 判断差异是否足够重要，需要标记为冲突
     * 
     * @param array $diff 差异数组
     * @return bool 是否有重要差异
     */
    public function hasSignificantChanges(array $diff): bool
    {
        // 如果添加或删除的行数超过5行，或更改的行数超过10行，认为是重要变更
        $addedCount = count($diff['added']);
        $removedCount = count($diff['removed']);
        $changedCount = count($diff['changed']);
        
        return ($addedCount > 5 || $removedCount > 5 || $changedCount > 10);
    }
}