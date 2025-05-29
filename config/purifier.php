<?php

/**
 * HTML Purifier 配置
 *
 * 此文件配置 HTML Purifier 库的行为，用于净化和过滤 HTML 内容，
 * 以防止跨站脚本 (XSS) 攻击和其他不安全的 HTML 内容。
 */
return [
    /**
     * 基本配置
     */
    'encoding' => 'UTF-8',              // 定义输入和输出的字符编码
    'finalize' => true,                 // 是否在配置后锁定配置，禁止进一步修改
    'ignoreNonStrings' => false,                // 是否忽略非字符串类型的输入数据
    'cachePath' => storage_path('app/purifier'), // HTML Purifier 缓存文件的存储路径
    'cacheFileMode' => 0755,                 // 缓存文件和目录的权限模式

    /**
     * 净化规则配置
     * 每个键代表一个配置集，可以在调用 Purifier 时指定使用。
     */
    'settings' => [
        // 默认配置集 - 包含了基本 HTML 元素和属性的白名单
        'default' => [
            // 定义文档类型，通常为 HTML 4.01 Transitional
            'HTML.Doctype' => 'HTML 4.01 Transitional',
            // 允许的 HTML 标签及其属性白名单。
            // 包含了 Tiptap 编辑器常用的标签（如标题、文本格式、链接、图片、表格、代码块等）
            'HTML.Allowed' => 'h1[style],h2[style],h3[style],h4[style],h5[style],h6[style],div,b,strong,i,em,u,a[href|title|target|rel],ul[style],ol[start|style],li,p[style],br,span[style],img[width|height|alt|src|title],code,pre[class],blockquote[style],hr,table,thead,tbody,tfoot,tr,td[colspan|rowspan|style],th[colspan|rowspan|style],mark',
            // 允许的 CSS 属性白名单
            'CSS.AllowedProperties' => 'font,font-size,font-weight,font-style,font-family,text-decoration,padding-left,color,background-color,text-align,width,height',
            // 禁用自动段落格式化，因为 Tiptap 通常会自行管理段落结构
            'AutoFormat.AutoParagraph' => false,
            // 自动移除空的 HTML 标签
            'AutoFormat.RemoveEmpty' => true,
            // 'CSS.MaxImgLength'         => null, // 可选：允许图片使用 Data URI，默认不限制
            // 'HTML.SafeIframe'          => true, // 可选：如果需要允许安全的 iframe
            // 'URI.SafeIframeRegexp'     => '%^(http://|https://|//)(www.youtube.com/embed/|player.vimeo.com/video/)%', // 可选：配合 SafeIframe 使用，限制 iframe 来源
        ],

        // 测试配置集 - 启用 ID 属性
        'test' => [
            'Attr.EnableID' => 'true', // 允许所有元素使用 ID 属性
        ],

        // YouTube 配置集 - 专门用于允许安全嵌入 YouTube 视频
        'youtube' => [
            'HTML.SafeIframe' => 'true', // 启用 iframe 的安全模式
            // 限制 iframe 的 src 属性只能来自 YouTube 或 Vimeo
            'URI.SafeIframeRegexp' => '%^(http://|https://|//)(www.youtube.com/embed/|player.vimeo.com/video/)%',
        ],

        // 自定义 HTML5 元素定义
        'custom_definition' => [
            'id' => 'html5-definitions', // 定义集的唯一 ID
            'rev' => 1,                 // 定义集的版本号
            'debug' => false,           // 是否启用调试模式
            'elements' => [
                // 定义块级 HTML5 语义化元素
                ['section',  'Block', 'Flow', 'Common'],
                ['nav',      'Block', 'Flow', 'Common'],
                ['article',  'Block', 'Flow', 'Common'],
                ['aside',    'Block', 'Flow', 'Common'],
                ['header',   'Block', 'Flow', 'Common'],
                ['footer',   'Block', 'Flow', 'Common'],
                ['address',  'Block', 'Flow', 'Common'],
                ['hgroup',   'Block', 'Required: h1 | h2 | h3 | h4 | h5 | h6', 'Common'],

                // 定义图片和描述元素
                ['figure',     'Block', 'Optional: (figcaption, Flow) | (Flow, figcaption) | Flow', 'Common'],
                ['figcaption', 'Inline', 'Flow', 'Common'],

                // 定义媒体元素
                ['video', 'Block', 'Optional: (source, Flow) | (Flow, source) | Flow', 'Common', [
                    'src' => 'URI',        // 视频源地址
                    'type' => 'Text',      // 视频 MIME 类型
                    'width' => 'Length',   // 视频宽度
                    'height' => 'Length',  // 视频高度
                    'poster' => 'URI',     // 视频封面图片地址
                    'preload' => 'Enum#auto,metadata,none', // 预加载方式
                    'controls' => 'Bool',  // 是否显示播放控件
                ]],
                ['source', 'Block', 'Flow', 'Common', [
                    'src' => 'URI',        // 媒体源地址
                    'type' => 'Text',      // 媒体 MIME 类型
                ]],

                // 定义内联 HTML5 语义化元素
                ['s',    'Inline', 'Inline', 'Common'],    // 不再准确的文本
                ['var',  'Inline', 'Inline', 'Common'],    // 变量
                ['sub',  'Inline', 'Inline', 'Common'],    // 下标
                ['sup',  'Inline', 'Inline', 'Common'],    // 上标
                ['mark', 'Inline', 'Inline', 'Common'],    // 高亮文本
                ['wbr',  'Inline', 'Empty',  'Core'],      // 软换行符

                // 定义编辑标记元素
                ['ins', 'Block', 'Flow', 'Common', ['cite' => 'URI', 'datetime' => 'CDATA']], // 插入的文本
                ['del', 'Block', 'Flow', 'Common', ['cite' => 'URI', 'datetime' => 'CDATA']], // 删除的文本
            ],
            'attributes' => [
                // 允许 iframe 元素的 allowfullscreen 属性
                ['iframe', 'allowfullscreen', 'Bool'],

                // 允许表格和单元格的特定属性
                ['table', 'height', 'Text'],    // 表格高度
                ['td',    'border', 'Text'],    // 单元格边框
                ['th',    'border', 'Text'],    // 表头单元格边框
                ['tr',    'width',  'Text'],    // 行宽度
                ['tr',    'height', 'Text'],    // 行高度
                ['tr',    'border', 'Text'],    // 行边框
            ],
        ],

        // 自定义属性 - 允许特定元素使用非标准属性
        'custom_attributes' => [
            ['a', 'target', 'Enum#_blank,_self,_target,_top'], // 允许链接的 target 属性
        ],

        // 自定义元素 - 定义新的或重新定义的 HTML 元素
        'custom_elements' => [
            ['u', 'Inline', 'Inline', 'Common'], // 允许使用 u 标签（下划线）
        ],
    ],
];
