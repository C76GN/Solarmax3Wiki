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
       preg_match_all('/\[\[([^\]]+)\]\]/', $content, $matches);
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
       $pattern = '/[^.]*\[\[' . preg_quote($title, '/') . '\]\][^.]*/';
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
}