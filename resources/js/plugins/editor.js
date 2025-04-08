import { getEditorConfig } from '@/extensions';

export const useEditor = () => {
    return {
        init: (options = {}) => {
            return getEditorConfig(options);
        },
        getOptions: (customOptions = {}) => {
            return getEditorConfig(customOptions);
        }
    };
};

export default useEditor;