import StarterKit from '@tiptap/starter-kit';
import Image from '@tiptap/extension-image';
import Link from '@tiptap/extension-link';
import Placeholder from '@tiptap/extension-placeholder';
import Typography from '@tiptap/extension-typography';
import CharacterCount from '@tiptap/extension-character-count';
import Table from '@tiptap/extension-table';
import TableRow from '@tiptap/extension-table-row';
import TableCell from '@tiptap/extension-table-cell';
import TableHeader from '@tiptap/extension-table-header';

// 导出创建扩展的函数
export function getExtensions() {
    return [
        StarterKit,
        Image.configure({
            allowBase64: true,
            inline: true,
        }),
        Link.configure({
            openOnClick: false,
            HTMLAttributes: {
                rel: 'noopener noreferrer',
                class: 'text-blue-600 hover:underline',
            },
        }),
        Placeholder.configure({
            placeholder: '开始编辑内容...',
        }),
        Typography,
        CharacterCount.configure({
            limit: 50000,
        }),
        Table.configure({
            resizable: true,
        }),
        TableRow,
        TableCell,
        TableHeader,
    ];
}

// 导出获取编辑器配置的函数
export function getEditorConfig(options = {}) {
    const defaultOptions = {
        extensions: getExtensions(),
        autofocus: true,
        editable: true,
    };

    return {
        ...defaultOptions,
        ...options,
    };
}