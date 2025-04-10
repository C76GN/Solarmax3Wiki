import { getEditorConfig, getExtensions, createBasicEditorConfig } from '@/extensions';
export const useEditor = () => {
    return {
        init: (options = {}) => {
            return getEditorConfig(options);
        },
        getOptions: (customOptions = {}) => {
            return getEditorConfig(customOptions);
        },
        createContent: (content, callback, focusPosition = 'end') => {
            return createBasicEditorConfig(content, callback, focusPosition);
        },
        getExtensions: (options = {}) => {
            return getExtensions(options);
        }
    };
};
export default useEditor;