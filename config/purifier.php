<?php

/**
* HTML Purifier 配置
*
* 此文件配置 HTML Purifier 库的行为，用于净化和过滤 HTML 内容，
* 以防止 XSS 攻击和其他不安全的 HTML 内容。
*/
return [
   /**
    * 基本配置
    */
   'encoding'         => 'UTF-8',              // 字符编码
   'finalize'         => true,                 // 是否对输出进行最终处理
   'ignoreNonStrings' => false,                // 是否忽略非字符串输入
   'cachePath'        => storage_path('app/purifier'), // 缓存路径
   'cacheFileMode'    => 0755,                 // 缓存文件权限

   /**
    * 净化规则配置
    */
   'settings' => [
       // 默认配置 - 基本 HTML 元素和属性
       'default' => [
           'HTML.Doctype'             => 'HTML 4.01 Transitional',
           'HTML.Allowed'             => 'div,b,strong,i,em,u,a[href|title],ul,ol,li,p[style],br,span[style],img[width|height|alt|src]',
           'CSS.AllowedProperties'    => 'font,font-size,font-weight,font-style,font-family,text-decoration,padding-left,color,background-color,text-align',
           'AutoFormat.AutoParagraph' => true,  // 自动添加段落标签
           'AutoFormat.RemoveEmpty'   => true,  // 删除空标签
       ],

       // 测试配置 - 启用 ID 属性
       'test' => [
           'Attr.EnableID' => 'true',
       ],

       // YouTube 配置 - 允许安全的 iframe 嵌入
       "youtube" => [
           "HTML.SafeIframe"      => 'true',
           "URI.SafeIframeRegexp" => "%^(http://|https://|//)(www.youtube.com/embed/|player.vimeo.com/video/)%",
       ],

       // 自定义 HTML5 定义
       'custom_definition' => [
           'id'      => 'html5-definitions',
           'rev'     => 1,
           'debug'   => false,
           'elements' => [
               // 块级元素
               ['section',  'Block', 'Flow', 'Common'],
               ['nav',      'Block', 'Flow', 'Common'],
               ['article',  'Block', 'Flow', 'Common'],
               ['aside',    'Block', 'Flow', 'Common'],
               ['header',   'Block', 'Flow', 'Common'],
               ['footer',   'Block', 'Flow', 'Common'],
               ['address',  'Block', 'Flow', 'Common'],
               ['hgroup',   'Block', 'Required: h1 | h2 | h3 | h4 | h5 | h6', 'Common'],
               
               // 图片和描述
               ['figure',     'Block', 'Optional: (figcaption, Flow) | (Flow, figcaption) | Flow', 'Common'],
               ['figcaption', 'Inline', 'Flow', 'Common'],
               
               // 媒体元素
               ['video', 'Block', 'Optional: (source, Flow) | (Flow, source) | Flow', 'Common', [
                   'src'      => 'URI',
                   'type'     => 'Text',
                   'width'    => 'Length',
                   'height'   => 'Length',
                   'poster'   => 'URI',
                   'preload'  => 'Enum#auto,metadata,none',
                   'controls' => 'Bool',
               ]],
               ['source', 'Block', 'Flow', 'Common', [
                   'src'  => 'URI',
                   'type' => 'Text',
               ]],
               
               // 内联元素
               ['s',    'Inline', 'Inline', 'Common'],
               ['var',  'Inline', 'Inline', 'Common'],
               ['sub',  'Inline', 'Inline', 'Common'],
               ['sup',  'Inline', 'Inline', 'Common'],
               ['mark', 'Inline', 'Inline', 'Common'],
               ['wbr',  'Inline', 'Empty',  'Core'],
               
               // 编辑标记元素
               ['ins', 'Block', 'Flow', 'Common', ['cite' => 'URI', 'datetime' => 'CDATA']],
               ['del', 'Block', 'Flow', 'Common', ['cite' => 'URI', 'datetime' => 'CDATA']],
           ],
           'attributes' => [
               // iframe 特性
               ['iframe', 'allowfullscreen', 'Bool'],
               
               // 表格特性
               ['table', 'height', 'Text'],
               ['td',    'border', 'Text'],
               ['th',    'border', 'Text'],
               ['tr',    'width',  'Text'],
               ['tr',    'height', 'Text'],
               ['tr',    'border', 'Text'],
           ],
       ],
       
       // 自定义属性
       'custom_attributes' => [
           ['a', 'target', 'Enum#_blank,_self,_target,_top'],
       ],
       
       // 自定义元素
       'custom_elements' => [
           ['u', 'Inline', 'Inline', 'Common'],
       ],
   ],
];