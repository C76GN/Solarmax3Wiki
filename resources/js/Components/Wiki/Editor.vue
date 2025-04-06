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
    },
    onCreate: ({ editor }) => {
        charCount.value = editor.storage.characterCount.characters();
    }
});
let autosaveTimer = null;
// 监听内容变化，进行自动保存
watch(
    () => props.modelValue,
    async (newValue, oldValue) => {
        if (props.autosave && props.pageId && isEditable.value && lastSavedContent.value !== newValue) {
            // 清除之前的计时器
            if (autosaveTimer) {
                clearTimeout(autosaveTimer);
            }
            // 设置新的计时器
            autosaveTimer = setTimeout(() => {
                saveDraft(newValue);
            }, props.autosaveInterval);
        }
    }
);

// 自动保存草稿
const saveDraft = async (content) => {
    if (!props.pageId || !isEditable.value) return;

    isAutosaving.value = true;
    autosaveMessage.value = '正在保存草稿...';

    try {
        const response = await axios.post(route('wiki.save-draft', props.pageId), {
            content: content
        });

        lastSavedContent.value = content;
        autosaveMessage.value = '草稿已保存于 ' + response.data.saved_at;
        emit('saved', response.data);

        // 5秒后隐藏保存提示
        setTimeout(() => {
            isAutosaving.value = false;
        }, 5000);
    } catch (error) {
        console.error('保存草稿失败:', error);
        autosaveMessage.value = '保存草稿失败: ' + (error.response?.data?.message || '未知错误');

        // 10秒后隐藏错误提示
        setTimeout(() => {
            isAutosaving.value = false;
        }, 10000);
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
        // 只有当编辑器内容与传入值不同时才更新
        const currentContent = editor.value?.getHTML() || '';
        if (newValue !== currentContent) {
            editor.value?.commands.setContent(newValue, false);
        }
    }
);

onMounted(() => {
    // 初始监听页面关闭事件，提示保存
    window.addEventListener('beforeunload', handleBeforeUnload);
});

onBeforeUnmount(() => {
    if (autosaveTimer) {
        clearTimeout(autosaveTimer);

        // 如果还有未保存的内容，尝试保存
        if (props.autosave && props.pageId && isEditable.value &&
            lastSavedContent.value !== props.modelValue) {
            saveDraft(props.modelValue);
        }
    }
});

// 页面关闭时提示
const handleBeforeUnload = (event) => {
    if (isEditable.value && editor.value && editor.value.getHTML() !== lastSavedContent.value) {
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