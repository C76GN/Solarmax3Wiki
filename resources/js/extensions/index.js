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
import Underline from '@tiptap/extension-underline';
import TextAlign from '@tiptap/extension-text-align';
import Highlight from '@tiptap/extension-highlight';
import CodeBlockLowlight from '@tiptap/extension-code-block-lowlight';
import { lowlight } from 'lowlight/lib/core';
import javascript from 'highlight.js/lib/languages/javascript';
import css from 'highlight.js/lib/languages/css';
import php from 'highlight.js/lib/languages/php';
import html from 'highlight.js/lib/languages/xml';
import python from 'highlight.js/lib/languages/python';
import json from 'highlight.js/lib/languages/json';
lowlight.registerLanguage('javascript', javascript);
lowlight.registerLanguage('js', javascript);
lowlight.registerLanguage('css', css);
lowlight.registerLanguage('php', php);
lowlight.registerLanguage('html', html);
lowlight.registerLanguage('python', python);
lowlight.registerLanguage('json', json);
export function getBaseExtensions() {
    return [
        StarterKit.configure({
            heading: {
                levels: [1, 2, 3, 4, 5, 6]
            },
            codeBlock: false,
        }),
        Link.configure({
            openOnClick: false,
            HTMLAttributes: {
                class: 'text-blue-600 underline hover:text-blue-800',
            },
        }),
        Placeholder,
        Typography,
        CharacterCount.configure({
            limit: 50000,
        }),
        Underline,
    ];
}
export function getImageExtension(options = {}) {
    return Image.configure({
        allowBase64: true,
        inline: true,
        HTMLAttributes: {
            class: 'max-w-full h-auto rounded',
        },
        ...options
    });
}
export function getTableExtensions() {
    return [
        Table.configure({
            resizable: true,
        }),
        TableRow,
        TableCell,
        TableHeader,
    ];
}
export function getTextAlignExtension() {
    return TextAlign.configure({
        types: ['heading', 'paragraph'],
    });
}
export function getCodeHighlightExtension() {
    return CodeBlockLowlight.configure({
        lowlight,
    });
}
export function getHighlightExtension() {
    return Highlight;
}
export function getExtensions(options = {}) {
    const {
        placeholderText = '开始编辑内容...',
        characterLimit = 50000,
        imageOptions = {},
    } = options;
    return [
        ...getBaseExtensions(),
        getImageExtension(imageOptions),
        ...getTableExtensions(),
        getTextAlignExtension(),
        getCodeHighlightExtension(),
        getHighlightExtension(),
        Placeholder.configure({
            placeholder: placeholderText,
        }),
        CharacterCount.configure({
            limit: characterLimit,
        }),
    ];
}
export function getEditorConfig(options = {}) {
    const defaultOptions = {
        extensions: getExtensions({
            placeholderText: options.placeholder || '开始编辑内容...',
            characterLimit: options.characterLimit || 50000,
            imageOptions: options.imageOptions || {},
        }),
        autofocus: true,
        editable: true,
    };
    return {
        ...defaultOptions,
        ...options,
    };
}
export function createBasicEditorConfig(content, updateCallback, focusPosition = 'end') {
    return {
        content,
        extensions: getExtensions(),
        editable: true,
        autofocus: focusPosition,
        onUpdate: updateCallback,
    };
}
export const availableLanguages = [
    { name: '纯文本', value: '' },
    { name: 'JavaScript', value: 'javascript' },
    { name: 'HTML', value: 'html' },
    { name: 'CSS', value: 'css' },
    { name: 'PHP', value: 'php' },
    { name: 'Python', value: 'python' },
    { name: 'JSON', value: 'json' },
];