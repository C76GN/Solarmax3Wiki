// 引入官方扩展
import StarterKit from '@tiptap/starter-kit'
import Underline from '@tiptap/extension-underline'
import Link from '@tiptap/extension-link'
import Image from '@tiptap/extension-image'
import Placeholder from '@tiptap/extension-placeholder'
import Table from '@tiptap/extension-table'
import TableRow from '@tiptap/extension-table-row'
import TableCell from '@tiptap/extension-table-cell'
import TableHeader from '@tiptap/extension-table-header'
import CodeBlock from '@tiptap/extension-code-block'
import Highlight from '@tiptap/extension-highlight'

// 引入自定义扩展
import { WikiLink } from './WikiLink'

// 配置扩展
export const getExtensions = (options = {}) => {
    return [
        // 基础工具包
        StarterKit.configure({
            // 自定义配置基础工具包
            heading: {
                levels: [1, 2, 3, 4],
            },
            bulletList: {
                keepMarks: true,
                keepAttributes: true,
            },
            orderedList: {
                keepMarks: true,
                keepAttributes: true,
            },
            // 可以根据需求配置其他扩展
        }),

        // 添加下划线支持
        Underline,

        // 配置链接
        Link.configure({
            openOnClick: false,
            HTMLAttributes: {
                class: 'text-blue-600 underline',
            },
        }),

        // 配置图片
        Image.configure({
            inline: true,
            HTMLAttributes: {
                class: 'rounded max-w-full',
            },
        }),

        // 配置占位符
        Placeholder.configure({
            placeholder: options.placeholder || '开始编辑内容...',
        }),

        // 表格支持
        Table.configure({
            resizable: true,
            HTMLAttributes: {
                class: 'border-collapse table-auto w-full',
            },
        }),
        TableRow,
        TableCell.configure({
            HTMLAttributes: {
                class: 'border border-gray-300 p-2',
            },
        }),
        TableHeader.configure({
            HTMLAttributes: {
                class: 'border border-gray-300 p-2 bg-gray-100 font-bold',
            },
        }),

        // 代码块
        CodeBlock.configure({
            HTMLAttributes: {
                class: 'bg-gray-800 text-white p-4 rounded my-4 overflow-x-auto',
            },
        }),

        // 高亮
        Highlight.configure({
            HTMLAttributes: {
                class: 'bg-yellow-100 p-1 rounded',
            }
        }),

        // 自定义的WikiLink扩展
        WikiLink.configure({
            HTMLAttributes: {
                class: 'wiki-link cursor-pointer',
            },
        }),
    ]
}