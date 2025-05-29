// 导入 Tiptap 编辑器核心和各种扩展
import StarterKit from '@tiptap/starter-kit'; // Tiptap 的基础套件，包含常用功能如段落、粗体、斜体等
import Image from '@tiptap/extension-image'; // 图片扩展
import Link from '@tiptap/extension-link'; // 链接扩展
import Placeholder from '@tiptap/extension-placeholder'; // 占位符扩展
import Typography from '@tiptap/extension-typography'; // 排版优化扩展
import CharacterCount from '@tiptap/extension-character-count'; // 字符计数扩展
import Table from '@tiptap/extension-table'; // 表格扩展
import TableRow from '@tiptap/extension-table-row'; // 表格行扩展
import TableCell from '@tiptap/extension-table-cell'; // 表格单元格扩展
import TableHeader from '@tiptap/extension-table-header'; // 表格头扩展
import Underline from '@tiptap/extension-underline'; // 下划线扩展
import TextAlign from '@tiptap/extension-text-align'; // 文本对齐扩展
import Highlight from '@tiptap/extension-highlight'; // 文本高亮扩展
import CodeBlockLowlight from '@tiptap/extension-code-block-lowlight'; // 代码块语法高亮扩展
import { lowlight } from 'lowlight/lib/core'; // 用于语法高亮的 lowlight 库

// 导入 highlight.js 支持的语言
import javascript from 'highlight.js/lib/languages/javascript';
import css from 'highlight.js/lib/languages/css';
import php from 'highlight.js/lib/languages/php';
import html from 'highlight.js/lib/languages/xml'; // HTML 通常用 xml 语言高亮
import python from 'highlight.js/lib/languages/python';
import json from 'highlight.js/lib/languages/json';

// 向 lowlight 注册需要支持的语言，以便代码块能够正确高亮
lowlight.registerLanguage('javascript', javascript);
lowlight.registerLanguage('js', javascript); // 注册 'js' 为 'javascript' 的别名
lowlight.registerLanguage('css', css);
lowlight.registerLanguage('php', php);
lowlight.registerLanguage('html', html);
lowlight.registerLanguage('python', python);
lowlight.registerLanguage('json', json);

/**
 * 获取 Tiptap 编辑器的基本扩展集合。
 * 包含常用文本格式和结构。
 * @returns {Array} Tiptap 扩展数组。
 */
export function getBaseExtensions() {
    return [
        StarterKit.configure({
            heading: {
                levels: [1, 2, 3, 4, 5, 6] // 允许 H1 到 H6 标题
            },
            codeBlock: false, // 禁用 StarterKit 默认的代码块，使用功能更强的 CodeBlockLowlight
        }),
        Link.configure({
            openOnClick: false, // 点击链接时不打开新标签页，而是允许编辑
            HTMLAttributes: {
                class: 'text-blue-600 underline hover:text-blue-800', // 为链接添加 Tailwind CSS 样式
            },
        }),
        Placeholder, // 占位符
        Typography, // 智能排版
        CharacterCount.configure({
            limit: 50000, // 字符限制
        }),
        Underline, // 下划线
    ];
}

/**
 * 获取 Tiptap 编辑器的图片扩展。
 * @param {object} options - 图片扩展的额外配置选项。
 * @returns {Extension} 图片扩展实例。
 */
export function getImageExtension(options = {}) {
    return Image.configure({
        allowBase64: true, // 允许粘贴 base64 编码的图片
        inline: true, // 图片可以内联显示
        HTMLAttributes: {
            class: 'max-w-full h-auto rounded', // 为图片添加响应式和圆角样式
        },
        ...options // 合并传入的自定义选项
    });
}

/**
 * 获取 Tiptap 编辑器的表格相关扩展。
 * @returns {Array} 表格扩展数组。
 */
export function getTableExtensions() {
    return [
        Table.configure({
            resizable: true, // 允许调整表格列宽
        }),
        TableRow, // 表格行
        TableCell, // 表格单元格
        TableHeader, // 表格头
    ];
}

/**
 * 获取 Tiptap 编辑器的文本对齐扩展。
 * @returns {Extension} 文本对齐扩展实例。
 */
export function getTextAlignExtension() {
    return TextAlign.configure({
        types: ['heading', 'paragraph'], // 允许对标题和段落进行文本对齐
    });
}

/**
 * 获取 Tiptap 编辑器的代码块语法高亮扩展。
 * @returns {Extension} 代码块高亮扩展实例。
 */
export function getCodeHighlightExtension() {
    return CodeBlockLowlight.configure({
        lowlight, // 使用配置好的 lowlight 实例进行语法高亮
    });
}

/**
 * 获取 Tiptap 编辑器的文本高亮扩展。
 * @returns {Extension} 文本高亮扩展实例。
 */
export function getHighlightExtension() {
    return Highlight;
}

/**
 * 获取 Tiptap 编辑器的完整扩展集合。
 * 根据传入的选项配置占位符、字符限制和图片行为。
 * @param {object} options - 扩展的配置选项。
 * @param {string} [options.placeholderText='开始编辑内容...'] - 占位符文本。
 * @param {number} [options.characterLimit=50000] - 字符限制。
 * @param {object} [options.imageOptions={}] - 图片扩展的特定选项。
 * @returns {Array} 完整的 Tiptap 扩展数组。
 */
export function getExtensions(options = {}) {
    const {
        placeholderText = '开始编辑内容...',
        characterLimit = 50000,
        imageOptions = {},
    } = options;

    return [
        ...getBaseExtensions(), // 包含基本功能
        getImageExtension(imageOptions), // 图片功能
        ...getTableExtensions(), // 表格功能
        getTextAlignExtension(), // 文本对齐功能
        getCodeHighlightExtension(), // 代码高亮功能
        getHighlightExtension(), // 文本高亮功能
        Placeholder.configure({
            placeholder: placeholderText, // 配置占位符文本
        }),
        CharacterCount.configure({
            limit: characterLimit, // 配置字符限制
        }),
    ];
}

/**
 * 获取 Tiptap 编辑器的完整配置对象。
 * @param {object} options - 配置选项，例如内容、是否可编辑、自动聚焦等。
 * @returns {object} Tiptap 编辑器配置对象。
 */
export function getEditorConfig(options = {}) {
    const defaultOptions = {
        extensions: getExtensions({
            placeholderText: options.placeholder || '开始编辑内容...', // 默认占位符
            characterLimit: options.characterLimit || 50000, // 默认字符限制
            imageOptions: options.imageOptions || {}, // 默认图片选项
        }),
        autofocus: true, // 默认自动聚焦
        editable: true, // 默认可编辑
    };

    return {
        ...defaultOptions, // 合并默认选项
        ...options, // 覆盖或添加自定义选项
    };
}

/**
 * 创建一个基础的 Tiptap 编辑器配置。
 * 适用于只需内容和更新回调的简单场景。
 * @param {string} content - 编辑器初始内容。
 * @param {Function} updateCallback - 内容更新时的回调函数。
 * @param {string} [focusPosition='end'] - 自动聚焦的位置（'start', 'end', 'all'）。
 * @returns {object} 基础 Tiptap 编辑器配置对象。
 */
export function createBasicEditorConfig(content, updateCallback, focusPosition = 'end') {
    return {
        content, // 初始内容
        extensions: getExtensions(), // 使用完整的扩展集合
        editable: true, // 默认可编辑
        autofocus: focusPosition, // 自动聚焦位置
        onUpdate: updateCallback, // 内容更新回调
    };
}

/**
 * 可用的代码高亮语言列表。
 * 用于在代码块中选择对应的语言进行高亮。
 * @type {Array<Object>} 语言选项数组。
 */
export const availableLanguages = [
    { name: '纯文本', value: '' },
    { name: 'JavaScript', value: 'javascript' },
    { name: 'HTML', value: 'html' },
    { name: 'CSS', value: 'css' },
    { name: 'PHP', value: 'php' },
    { name: 'Python', value: 'python' },
    { name: 'JSON', value: 'json' },
];