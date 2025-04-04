// resources/js/plugins/editor.js
// Tiptap编辑器配置
import { getExtensions } from '@/extensions';

export const useEditor = () => {
    const defaultOptions = {
        extensions: getExtensions(),
        content: '',
        autofocus: true,
        editable: true
    };

    return {
        // 可以在这里添加更多配置选项
        init: (options = {}) => {
            return { ...defaultOptions, ...options };
        },
        getOptions: (customOptions = {}) => {
            return { ...defaultOptions, ...customOptions };
        }
    };
};

export default useEditor;