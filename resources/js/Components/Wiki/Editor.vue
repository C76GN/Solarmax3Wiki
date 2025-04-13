<template>
    <div class="tiptap-editor">
        <menu-bar v-if="editor" :editor="editor" :is-editable="editable" @toggle-edit="$emit('toggle-edit')" />
        <editor-content v-if="editor" :editor="editor" class="editor-content prose max-w-none"
            :class="{ 'editor-disabled': !editable }" />
        <div v-if="autosaveEnabled" class="autosave-status-bar p-2 text-xs text-gray-500 border-t text-right">
            <span v-if="autosaveStatus" :class="autosaveStatusClass" class="ml-2 flex items-center justify-end">
                <font-awesome-icon :icon="autosaveStatusIcon" :spin="autosaveStatus.type === 'pending'" class="mr-1" />
                {{ autosaveStatus.message }}
            </span>
            <span v-else class="ml-2 text-gray-400 italic">草稿未更改</span>
        </div>
    </div>
</template>
<script setup>
import { ref, watch, onMounted, onBeforeUnmount, computed, defineEmits, defineProps, nextTick } from 'vue';
import { useEditor, EditorContent } from '@tiptap/vue-3';
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
import MenuBar from './EditorMenuBar.vue';
import axios from 'axios';
import { debounce } from 'lodash';
import { formatDateTime } from '@/utils/formatters';
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
const props = defineProps({
    modelValue: { type: String, default: '' },
    placeholder: { type: String, default: '开始编辑内容...' },
    editable: { type: Boolean, default: true }, // 直接使用 prop
    autosave: { type: Boolean, default: false },
    pageId: { type: Number, default: null },
    autosaveInterval: { type: Number, default: 30000 } // 30 seconds
});
const emit = defineEmits(['update:modelValue', 'saved', 'error', 'statusUpdate', 'toggle-edit']);
const lastSavedContent = ref(props.modelValue);
const hasUnsavedChanges = ref(false);
const autosaveStatus = ref(null);
let debounceTimer = null;
let autosaveTimer = null;
const editor = useEditor({
    content: props.modelValue,
    extensions: [
        StarterKit.configure({ heading: { levels: [1, 2, 3, 4, 5, 6] }, codeBlock: false }),
        Image.configure({ allowBase64: true, inline: true, HTMLAttributes: { class: 'max-w-full h-auto rounded' } }),
        Link.configure({ openOnClick: false, HTMLAttributes: { class: 'text-blue-600 underline hover:text-blue-800' } }),
        Placeholder.configure({ placeholder: props.placeholder }),
        Table.configure({ resizable: true }), TableRow, TableCell, TableHeader,
        Typography,
        Underline,
        TextAlign.configure({ types: ['heading', 'paragraph'] }),
        Highlight,
        CodeBlockLowlight.configure({ lowlight }),
        CharacterCount.configure({ limit: 50000 }),
    ],
    editable: props.editable,
    autofocus: props.editable ? 'end' : false,
    onUpdate: ({ editor }) => {
        const newHtml = editor.getHTML();
        emit('update:modelValue', newHtml);
        if (lastSavedContent.value !== newHtml) {
            hasUnsavedChanges.value = true;
        }
        debouncedSaveDraft(newHtml);
    },
    onCreate: ({ editor }) => {
        editor.setEditable(props.editable);
        lastSavedContent.value = editor.getHTML();
    }
});
watch(() => props.modelValue, (newValue) => {
    if (editor.value && editor.value.getHTML() !== newValue) {
        console.log("Editor content updated from external modelValue");
        editor.value.commands.setContent(newValue, false);
        hasUnsavedChanges.value = false;
        lastSavedContent.value = newValue;
    }
});
watch(() => props.editable, (newValue) => {
    console.log("Editable prop changed:", newValue);
    if (editor.value) {
        editor.value.setEditable(newValue);
        if (newValue && autosaveEnabled.value) {
            console.log("Editable turned on, setting up autosave timer");
            setupAutosaveTimer();
        } else {
            console.log("Editable turned off, clearing autosave timer and potentially saving");
            clearAutosaveTimer();
            if (!newValue && autosaveEnabled.value && hasUnsavedChanges.value) {
                if (debounceTimer) clearTimeout(debounceTimer);
                saveDraft(editor.value.getHTML() || props.modelValue);
            }
        }
    }
}, { immediate: true });
const autosaveEnabled = computed(() => props.autosave && props.pageId !== null && props.editable);
onMounted(() => {
    console.log("Editor component mounted, initial editable state:", props.editable);
    if (autosaveEnabled.value) {
        console.log("Autosave enabled on mount, setting up timer");
        setupAutosaveTimer();
    }
    window.addEventListener('online', handleOnline);
    if (editor.value && !props.modelValue) {
        console.log("Initial modelValue is empty, ensuring editor content is empty");
        editor.value.commands.setContent('', false);
    }
});
onBeforeUnmount(() => {
    console.log("Editor component unmounting");
    clearAutosaveTimer(); // 清除自动保存定时器
    // 保存未保存的草稿
    if (autosaveEnabled.value && hasUnsavedChanges.value && editor.value) {
        if (debounceTimer) clearTimeout(debounceTimer);
        console.log("Saving draft on unmount via sendBeacon");
        // 使用 navigator.sendBeacon 在页面卸载时尝试发送最后的数据
        if (navigator.sendBeacon) {
            const url = route('wiki.save-draft', props.pageId);
            const formData = new FormData();
            formData.append('content', editor.value.getHTML());
            formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
            try {
                const success = navigator.sendBeacon(url, formData);
                console.log("sendBeacon attempt status:", success);
            } catch (e) {
                console.error("sendBeacon failed on unmount:", e);
            }
        } else {
            console.warn("navigator.sendBeacon not supported. Draft might not be saved on unmount.");
            localStorage.setItem(`draft_${props.pageId}`, editor.value.getHTML());
        }
    }
    if (editor.value) {
        console.log("Destroying editor instance");
        editor.value.destroy();
    }
    window.removeEventListener('online', handleOnline);
});
const debouncedSaveDraft = debounce(async (content) => {
    await saveDraft(content);
}, 2000);
const saveDraft = async (content) => {
    if (!autosaveEnabled.value) {
        console.log("Autosave not enabled or editor not editable, skipping draft save.");
        return;
    }
    if (!editor.value || content === lastSavedContent.value) {
        console.log("No changes or editor not ready, skipping draft save.");
        return;
    }
    console.log("Attempting to save draft...");
    autosaveStatus.value = { type: 'pending', message: '正在自动保存草稿...' };
    emit('statusUpdate', { ...autosaveStatus.value });
    try {
        const response = await axios.post(route('wiki.save-draft', props.pageId), { content });
        console.log("Draft saved successfully:", response.data);
        lastSavedContent.value = content;
        hasUnsavedChanges.value = false;
        const savedAtDate = response.data.saved_at ? new Date(response.data.saved_at) : new Date();
        autosaveStatus.value = {
            type: 'success',
            message: `草稿已于 ${formatDateTime(savedAtDate)} 保存`,
            saved_at: response.data.saved_at
        };
        emit('saved', { ...autosaveStatus.value });
        emit('statusUpdate', { ...autosaveStatus.value });
        setTimeout(() => {
            if (autosaveStatus.value?.type === 'success') {
                autosaveStatus.value = null;
                emit('statusUpdate', null);
            }
        }, 5000);
    } catch (error) {
        const errorMessage = error.response?.data?.message || error.message || '网络错误，无法保存';
        console.error('保存草稿失败:', errorMessage, error.response || error);
        autosaveStatus.value = {
            type: 'error',
            message: `草稿保存失败: ${errorMessage}`
        };
        emit('statusUpdate', { ...autosaveStatus.value });
        emit('error', error);
    }
};
const setupAutosaveTimer = () => {
    clearAutosaveTimer();
    if (autosaveEnabled.value) {
        console.log(`Setting up autosave timer with interval ${props.autosaveInterval}ms`);
        autosaveTimer = setInterval(() => {
            console.log("Autosave timer triggered. Checking for changes...");
            if (hasUnsavedChanges.value && editor.value) {
                if (debounceTimer) clearTimeout(debounceTimer);
                saveDraft(editor.value.getHTML() || props.modelValue);
            } else {
                console.log("No unsaved changes detected by interval.");
            }
        }, props.autosaveInterval);
    }
};
const clearAutosaveTimer = () => {
    if (autosaveTimer) {
        console.log("Clearing autosave interval timer");
        clearInterval(autosaveTimer);
        autosaveTimer = null;
    }
}
const handleOnline = () => {
    if (autosaveEnabled.value && hasUnsavedChanges.value && editor.value) {
        if (debounceTimer) clearTimeout(debounceTimer);
        saveDraft(editor.value.getHTML() || props.modelValue);
    }
};
const autosaveStatusClass = computed(() => {
    if (!autosaveStatus.value) return 'text-gray-400 italic';
    switch (autosaveStatus.value.type) {
        case 'success': return 'text-green-600';
        case 'error': return 'text-red-600';
        case 'pending': return 'text-blue-600';
        default: return 'text-gray-500';
    }
});
const autosaveStatusIcon = computed(() => {
    if (!autosaveStatus.value) return ['fas', 'circle-info'];
    switch (autosaveStatus.value.type) {
        case 'success': return ['fas', 'check-circle'];
        case 'error': return ['fas', 'exclamation-circle'];
        case 'pending': return ['fas', 'spinner'];
        default: return ['fas', 'info-circle'];
    }
});
defineExpose({ editor });
</script>
<style scoped>
.tiptap-editor {
    border: 1px solid #e2e8f0;
    border-radius: 0.5rem;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    background-color: white;
}

.editor-content {
    padding: 1rem;
    min-height: 300px;
    overflow-y: auto;
    max-height: 70vh;
    flex-grow: 1;
}

.editor-content:focus {
    outline: none;
}

.editor-disabled .ProseMirror {
    background-color: #f9fafb;
    cursor: not-allowed;
    opacity: 0.7;
}

.editor-disabled .editor-menu-bar button {
    opacity: 0.5;
    cursor: not-allowed;
}

.ProseMirror {
    line-height: 1.6;
}

.ProseMirror>*+* {
    margin-top: 0.75em;
}

.ProseMirror p {
    margin: 0 0 0.75em 0;
}

.ProseMirror ul,
.ProseMirror ol {
    padding: 0 1rem;
    margin: 0.75em 0;
}

.ProseMirror h1,
.ProseMirror h2,
.ProseMirror h3,
.ProseMirror h4,
.ProseMirror h5,
.ProseMirror h6 {
    line-height: 1.1;
    margin-top: 1.25em;
    margin-bottom: 0.5em;
}

.ProseMirror code {
    background-color: rgba(97, 97, 97, 0.1);
    color: #374151;
    padding: .2rem .4rem;
    border-radius: 0.25rem;
    font-family: monospace;
    font-size: 0.9em;
}

.ProseMirror pre {
    background: #0D0D0D;
    color: #FFF;
    font-family: 'JetBrainsMono', monospace;
    padding: 0.75rem 1rem;
    border-radius: 0.5rem;
    margin: 1em 0;
    overflow-x: auto;
}

.ProseMirror pre code {
    color: inherit;
    padding: 0;
    background: none;
    font-size: 0.85rem;
}

.ProseMirror img {
    max-width: 100%;
    height: auto;
    display: block;
    margin: 1em 0;
    border-radius: 0.25rem;
}

.ProseMirror img.ProseMirror-selectednode {
    outline: 3px solid #68CEF8;
}

.ProseMirror blockquote {
    padding-left: 1rem;
    border-left: 3px solid #d1d5db;
    color: #4b5563;
    font-style: italic;
    margin: 1em 0;
}

.ProseMirror hr {
    border: none;
    border-top: 1px solid #e5e7eb;
    margin: 2rem 0;
}

.ProseMirror a {
    color: #2563eb;
    text-decoration: underline;
    cursor: pointer;
}

.ProseMirror a:hover {
    color: #1d4ed8;
}

.ProseMirror mark {
    background-color: #fef9c3;
    padding: 0.1em 0;
}

.ProseMirror table {
    border-collapse: collapse;
    table-layout: fixed;
    width: 100%;
    margin: 1em 0;
    overflow: hidden;
}

.ProseMirror td,
.ProseMirror th {
    min-width: 1em;
    border: 1px solid #d1d5db;
    padding: 0.5rem 0.75rem;
    vertical-align: top;
    box-sizing: border-box;
    position: relative;
}

.ProseMirror td>*,
.ProseMirror th>* {
    margin-bottom: 0;
}

.ProseMirror th {
    font-weight: bold;
    text-align: left;
    background-color: #f9fafb;
}

.ProseMirror .selectedCell:after {
    z-index: 2;
    position: absolute;
    content: "";
    left: 0;
    right: 0;
    top: 0;
    bottom: 0;
    background: rgba(37, 99, 235, 0.1);
    pointer-events: none;
}

.ProseMirror .column-resize-handle {
    position: absolute;
    right: -2px;
    top: 0;
    bottom: 0;
    width: 4px;
    z-index: 20;
    background-color: #adf;
    pointer-events: none;
}

.ProseMirror.resize-cursor {
    cursor: ew-resize;
    cursor: col-resize;
}

.ProseMirror p.is-editor-empty:first-child::before {
    content: attr(data-placeholder);
    float: left;
    color: #adb5bd;
    pointer-events: none;
    height: 0;
}

.tiptap-editor:focus-within {
    border-color: #3b82f6;
    box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.4);
}

.autosave-status-bar {
    min-height: 28px;
}
</style>