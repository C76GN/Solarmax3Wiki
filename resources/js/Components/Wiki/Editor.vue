<template>
    <div class="tiptap-editor">
        <menu-bar v-if="editor" :editor="editor" :is-editable="isEditable" @toggle-edit="toggleEdit" />

        <editor-content :editor="editor" class="editor-content prose max-w-none"
            :class="{ 'editor-disabled': !isEditable }" />

        <div v-if="isEditable && charCount" class="char-count mt-2 text-sm text-gray-500">
            {{ charCount }} 个字符
        </div>

        <div v-if="isAutosaving" class="autosave-indicator mt-2 text-sm text-gray-500">
            {{ autosaveMessage }}
        </div>
    </div>
</template>

<script setup>
import { ref, watch, onMounted, onBeforeUnmount, nextTick, computed } from 'vue';
import { useEditor, EditorContent } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';
import Image from '@tiptap/extension-image';
import Link from '@tiptap/extension-link';
import Placeholder from '@tiptap/extension-placeholder';
import Typography from '@tiptap/extension-typography';
import CharacterCount from '@tiptap/extension-character-count';
import MenuBar from './EditorMenuBar.vue';
import { useForm } from '@inertiajs/vue3';
import axios from 'axios';
import { debounce } from 'lodash';

const props = defineProps({
    modelValue: {
        type: String,
        default: ''
    },
    placeholder: {
        type: String,
        default: '开始编辑内容...'
    },
    editable: {
        type: Boolean,
        default: true
    },
    autosave: {
        type: Boolean,
        default: false
    },
    pageId: {
        type: Number,
        default: null
    },
    autosaveInterval: {
        type: Number,
        default: 30000 // 30秒
    }
});
const emit = defineEmits(['update:modelValue', 'saved']);
const isEditable = ref(props.editable);
const charCount = ref(0);
const isAutosaving = ref(false);
const autosaveMessage = ref('');
const lastSavedContent = ref(props.modelValue);
const hasUnsavedChanges = ref(false);
const lastModified = ref(Date.now());



const toggleEdit = () => {
    isEditable.value = !isEditable.value;
    editor.value.setEditable(isEditable.value);
};

// 初始化编辑器
const editor = useEditor({
    content: props.modelValue,
    extensions: [
        StarterKit,
        Image,
        Link.configure({
            openOnClick: false,
        }),
        Placeholder.configure({
            placeholder: props.placeholder,
        }),
        Typography,
        CharacterCount.configure({
            limit: 50000
        })
    ],
    editable: isEditable.value,
    autofocus: isEditable.value ? 'end' : false,
    onUpdate: ({ editor }) => {
        const html = editor.getHTML();
        emit('update:modelValue', html);
        charCount.value = editor.storage.characterCount.characters();
        hasUnsavedChanges.value = true;
        lastModified.value = Date.now();
    },
    onCreate: ({ editor }) => {
        charCount.value = editor.storage.characterCount.characters();
    }
});

const debouncedSaveDraft = debounce(async (content) => {
    if (!props.pageId || !isEditable.value) return;
    await saveDraft(content);
}, 2000);

let autosaveTimer = null;
// 监听内容变化，进行自动保存
watch(
    () => props.modelValue,
    async (newValue, oldValue) => {
        // 如果内容有变更且开启了自动保存
        if (props.autosave && props.pageId && isEditable.value && lastSavedContent.value !== newValue) {
            hasUnsavedChanges.value = true;

            // 使用防抖函数来避免频繁保存
            debouncedSaveDraft(newValue);

            // 如果上次修改时间超过指定的间隔，则立即保存
            const timeSinceLastSave = Date.now() - lastModified.value;
            if (timeSinceLastSave > props.autosaveInterval) {
                debouncedSaveDraft.cancel();
                await saveDraft(newValue);
            }
        }
    }
);

// 自动保存草稿
const saveDraft = async (content) => {
    if (!props.pageId || !isEditable.value) return;

    if (content === lastSavedContent.value) {
        return;
    }

    isAutosaving.value = true;
    autosaveMessage.value = '正在保存草稿...';

    try {
        const maxRetries = 3;
        let retryCount = 0;
        let saveSuccess = false;

        while (!saveSuccess && retryCount < maxRetries) {
            try {
                const response = await axios.post(route('wiki.save-draft', props.pageId), {
                    content: content
                });

                lastSavedContent.value = content;
                hasUnsavedChanges.value = false;
                autosaveMessage.value = '草稿已保存于 ' + response.data.saved_at;
                emit('saved', response.data);

                saveSuccess = true;

                setTimeout(() => {
                    if (autosaveMessage.value.includes('草稿已保存')) {
                        isAutosaving.value = false;
                    }
                }, 5000);
            } catch (error) {
                retryCount++;
                console.warn(`保存草稿失败，正在重试 (${retryCount}/${maxRetries})...`, error);

                // 如果是网络错误，尝试延迟后重试
                if (error.message.includes('Network Error')) {
                    await new Promise(resolve => setTimeout(resolve, 2000 * retryCount));
                } else {
                    break; // 非网络错误不重试
                }
            }
        }

        if (!saveSuccess) {
            throw new Error('草稿保存多次尝试失败');
        }
    } catch (error) {
        console.error('保存草稿失败:', error);
        autosaveMessage.value = '保存草稿失败: ' + (error.response?.data?.message || error.message || '未知错误');

        setTimeout(() => {
            if (autosaveMessage.value.includes('保存草稿失败')) {
                isAutosaving.value = false;
            }
        }, 10000);

        // 十秒后再次尝试保存
        setTimeout(() => {
            if (hasUnsavedChanges.value) {
                saveDraft(props.modelValue);
            }
        }, 10000);
    }
};

const setupAutosaveTimer = () => {
    if (props.autosave && props.pageId && isEditable.value) {
        // 清除之前的定时器
        if (autosaveTimer) {
            clearInterval(autosaveTimer);
        }

        // 设置新的定时器，精确30秒
        autosaveTimer = setInterval(() => {
            if (hasUnsavedChanges.value) {
                saveDraft(props.modelValue);
                console.log("Auto-saving draft...");
            }
        }, 30000); // 严格30秒
    }
};

// 监听编辑器配置变化
watch(
    () => props.editable,
    (newValue) => {
        isEditable.value = newValue;
        if (editor.value) {
            editor.value.setEditable(newValue);
        }
    }
);

// 监听内容变化
watch(
    () => props.modelValue,
    (newValue) => {
        const currentContent = editor.value?.getHTML() || '';
        if (newValue !== currentContent) {
            editor.value?.commands.setContent(newValue, false);
        }
    }
);

onMounted(() => {
    // 初始监听页面关闭事件，提示保存
    window.addEventListener('beforeunload', handleBeforeUnload);

    // 设置自动保存定时器
    setupAutosaveTimer();

    // 监听网络状态变化
    window.addEventListener('online', handleOnline);
});

onBeforeUnmount(() => {
    // 清理事件监听和定时器
    window.removeEventListener('beforeunload', handleBeforeUnload);
    window.removeEventListener('online', handleOnline);

    if (autosaveTimer) {
        clearInterval(autosaveTimer);
    }

    // 离开页面前最后保存一次
    if (props.autosave && props.pageId && isEditable.value &&
        lastSavedContent.value !== props.modelValue && hasUnsavedChanges.value) {
        saveDraft(props.modelValue);
    }
});

const handleOnline = () => {
    if (hasUnsavedChanges.value) {
        saveDraft(props.modelValue);
    }
};

// 页面关闭时提示
const handleBeforeUnload = (event) => {
    if (isEditable.value && hasUnsavedChanges.value) {
        event.preventDefault();
        event.returnValue = '您有未保存的更改，确定要离开吗？';
    }
};
</script>

<style>
.tiptap-editor {
    border: 1px solid #e2e8f0;
    border-radius: 0.5rem;
}

.editor-content {
    padding: 1rem;
    min-height: 300px;
    overflow-y: auto;
    max-height: 600px;
}

.editor-content:focus {
    outline: none;
}

.editor-disabled {
    background-color: #f9fafb;
    cursor: not-allowed;
}

.ProseMirror {
    >*+* {
        margin-top: 0.75em;
    }

    p {
        margin: 0;
    }

    ul,
    ol {
        padding: 0 1rem;
    }

    h1,
    h2,
    h3,
    h4,
    h5,
    h6 {
        line-height: 1.1;
        margin-top: 1rem;
    }

    code {
        background-color: rgba(#616161, 0.1);
        color: #616161;
        padding: 0.2rem 0.4rem;
        border-radius: 0.25rem;
    }

    pre {
        background: #0D0D0D;
        color: #FFF;
        font-family: 'JetBrainsMono', monospace;
        padding: 0.75rem 1rem;
        border-radius: 0.5rem;

        code {
            color: inherit;
            padding: 0;
            background: none;
            font-size: 0.8rem;
        }
    }

    img {
        max-width: 100%;
        height: auto;
    }

    blockquote {
        padding-left: 1rem;
        border-left: 2px solid rgba(#0D0D0D, 0.1);
    }

    hr {
        border: none;
        border-top: 2px solid rgba(#0D0D0D, 0.1);
        margin: 2rem 0;
    }

    a {
        color: #2563eb;
        text-decoration: underline;
    }
}
</style>