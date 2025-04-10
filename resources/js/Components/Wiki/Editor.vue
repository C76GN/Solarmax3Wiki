<template>
    <div class="tiptap-editor">
        <menu-bar v-if="editor" :editor="editor" :is-editable="isEditableRef" @toggle-edit="toggleEdit" />
        <editor-content v-if="editor" :editor="editor" class="editor-content prose max-w-none"
            :class="{ 'editor-disabled': !isEditableRef }" />
        <!-- 状态显示区 -->
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

// 导入并注册 highlight.js 语言包
import javascript from 'highlight.js/lib/languages/javascript';
import css from 'highlight.js/lib/languages/css';
import php from 'highlight.js/lib/languages/php';
import html from 'highlight.js/lib/languages/xml'; // xml 通常包含 html
import python from 'highlight.js/lib/languages/python';
import json from 'highlight.js/lib/languages/json';

lowlight.registerLanguage('javascript', javascript);
lowlight.registerLanguage('js', javascript);
lowlight.registerLanguage('css', css);
lowlight.registerLanguage('php', php);
lowlight.registerLanguage('html', html);
lowlight.registerLanguage('python', python);
lowlight.registerLanguage('json', json);
// ... 你可以继续注册其他你需要的语言

const props = defineProps({
    modelValue: { type: String, default: '' },
    placeholder: { type: String, default: '开始编辑内容...' },
    editable: { type: Boolean, default: true }, // Initial editable state from parent
    autosave: { type: Boolean, default: false },
    pageId: { type: Number, default: null },
    autosaveInterval: { type: Number, default: 30000 } // 30 seconds
});

const emit = defineEmits(['update:modelValue', 'saved', 'error', 'statusUpdate']);

const isEditableRef = ref(props.editable);
const lastSavedContent = ref(props.modelValue);
const hasUnsavedChanges = ref(false);
const autosaveStatus = ref(null); // { type: 'pending' | 'success' | 'error', message: string, saved_at?: string }
let debounceTimer = null;
let autosaveTimer = null; // 用于 clearInterval

// --- Tiptap Editor Initialization ---
// !!! 修改点：将 useEditor 移到 setup 顶层 !!!
const editor = useEditor({
    content: props.modelValue,
    extensions: [
        StarterKit.configure({ heading: { levels: [1, 2, 3, 4, 5, 6] }, codeBlock: false }), // 禁用默认CodeBlock，使用Lowlight
        Image.configure({ allowBase64: true, inline: true, HTMLAttributes: { class: 'max-w-full h-auto rounded' } }),
        Link.configure({ openOnClick: false, HTMLAttributes: { class: 'text-blue-600 underline hover:text-blue-800' } }),
        Placeholder.configure({ placeholder: props.placeholder }),
        Table.configure({ resizable: true }), TableRow, TableCell, TableHeader,
        Typography,
        Underline,
        TextAlign.configure({ types: ['heading', 'paragraph'] }),
        Highlight,
        CodeBlockLowlight.configure({ lowlight }), // 使用CodeBlockLowlight
        CharacterCount.configure({ limit: 50000 }), // 假设你需要字数统计
    ],
    editable: isEditableRef.value,
    autofocus: isEditableRef.value ? 'end' : false,
    // onUpdate moved to watch effect below or specific handlers
});

// Watch for external changes to modelValue
watch(() => props.modelValue, (newValue) => {
    // Check if the editor instance exists and the content is actually different
    if (editor.value && editor.value.getHTML() !== newValue) {
        editor.value.commands.setContent(newValue, false); // false to prevent triggering update event again
        hasUnsavedChanges.value = false;
        lastSavedContent.value = newValue;
    }
});

// Watch for internal editor updates to emit changes and handle autosave logic
watch(() => editor.value?.getHTML(), (newHtml) => {
    if (editor.value && newHtml !== props.modelValue) {
        emit('update:modelValue', newHtml); // Emit the change
        if (lastSavedContent.value !== newHtml) { // Only mark as unsaved if content actually differs from last save
            hasUnsavedChanges.value = true;
        }
        debouncedSaveDraft(newHtml); // Trigger debounced draft save
    }
});


// Watch for changes in props.editable
watch(() => props.editable, (newValue) => {
    isEditableRef.value = newValue;
    if (editor.value) {
        editor.value.setEditable(newValue);
        if (newValue && autosaveEnabled.value) {
            setupAutosaveTimer(); // Restart timer if editing is enabled
        } else {
            clearAutosaveTimer(); // Stop timer if not editable or autosave disabled
            // If turning non-editable and there are changes, save immediately
            if (!newValue && autosaveEnabled.value && hasUnsavedChanges.value) {
                if (debounceTimer) clearTimeout(debounceTimer); // Clear any pending debounce
                saveDraft(editor.value.getHTML() || props.modelValue);
            }
        }
    }
});

const autosaveEnabled = computed(() => props.autosave && props.pageId !== null && isEditableRef.value);

onMounted(() => {
    // Ensure editable state is set correctly on mount
    if (editor.value) {
        editor.value.setEditable(isEditableRef.value);
    }
    setupAutosaveTimer();
    window.addEventListener('online', handleOnline);

    // If content prop was initially empty, set it again after mount to ensure placeholder works
    if (editor.value && !props.modelValue) {
        editor.value.commands.setContent('', false);
    }
});

onBeforeUnmount(() => {
    clearAutosaveTimer();

    // Save draft on unmount if needed
    if (autosaveEnabled.value && hasUnsavedChanges.value) {
        if (debounceTimer) clearTimeout(debounceTimer);
        if (navigator.sendBeacon && editor.value) {
            const url = route('wiki.save-draft', props.pageId);
            const formData = new FormData();
            formData.append('content', editor.value.getHTML());
            formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content')); // Include CSRF token if needed by backend
            try {
                navigator.sendBeacon(url, formData);
            } catch (e) {
                console.error("sendBeacon failed on unmount:", e);
                // Fallback or log error if needed
            }
        } else {
            console.warn("sendBeacon not supported or editor not ready, draft might not be saved on unmount.");
        }
    }

    if (editor.value) {
        editor.value.destroy();
    }
    window.removeEventListener('online', handleOnline);
});

// --- Methods ---
const toggleEdit = () => {
    // This function is now controlled by the parent via the editable prop
    // If direct toggle inside this component is needed, emit an event
    // emit('toggle-edit-request'); // Example event
    console.warn("toggleEdit called inside Editor.vue - this should ideally be managed by the parent component via the 'editable' prop.");
    // For testing, we can toggle the internal ref, but this breaks the prop flow
    // isEditableRef.value = !isEditableRef.value;
    // if (editor.value) editor.value.setEditable(isEditableRef.value);
};

// Debounce draft saving
const debouncedSaveDraft = debounce(async (content) => {
    await saveDraft(content);
}, 2000); // Save 2 seconds after last edit

// Actual save draft function
const saveDraft = async (content) => {
    if (!autosaveEnabled.value) return;
    // Only save if content has actually changed since the last successful save
    if (!editor.value || content === lastSavedContent.value) {
        return;
    }

    autosaveStatus.value = { type: 'pending', message: '正在自动保存草稿...' };
    emit('statusUpdate', autosaveStatus.value);

    try {
        const response = await axios.post(route('wiki.save-draft', props.pageId), { content });
        lastSavedContent.value = content; // Update last saved content *only on success*
        hasUnsavedChanges.value = false;
        const savedAtDate = response.data.saved_at ? new Date(response.data.saved_at) : new Date();
        autosaveStatus.value = {
            type: 'success',
            message: `草稿已于 ${formatDateTime(savedAtDate)} 保存`,
            saved_at: response.data.saved_at
        };
        emit('saved', autosaveStatus.value);
        emit('statusUpdate', autosaveStatus.value);

        // Clear success message after a delay
        setTimeout(() => {
            if (autosaveStatus.value?.type === 'success') {
                autosaveStatus.value = null;
                emit('statusUpdate', autosaveStatus.value);
            }
        }, 5000);

    } catch (error) {
        console.error('保存草稿失败:', error.response?.data || error.message);
        const errorMessage = error.response?.data?.message || error.message || '网络错误，无法保存';
        autosaveStatus.value = {
            type: 'error',
            message: `草稿保存失败: ${errorMessage}`
        };
        emit('statusUpdate', autosaveStatus.value);
        emit('error', error);
        // Don't clear error message automatically
    }
};

// Setup the interval timer for autosave
const setupAutosaveTimer = () => {
    clearAutosaveTimer(); // Clear existing timer first
    if (autosaveEnabled.value) {
        autosaveTimer = setInterval(() => {
            if (hasUnsavedChanges.value && editor.value) {
                if (debounceTimer) clearTimeout(debounceTimer); // Clear debounce if interval triggers
                saveDraft(editor.value.getHTML() || props.modelValue);
            }
        }, props.autosaveInterval);
    }
};

const clearAutosaveTimer = () => {
    if (autosaveTimer) {
        clearInterval(autosaveTimer);
        autosaveTimer = null;
    }
}

// Handle coming back online
const handleOnline = () => {
    if (autosaveEnabled.value && hasUnsavedChanges.value && editor.value) {
        if (debounceTimer) clearTimeout(debounceTimer);
        saveDraft(editor.value.getHTML() || props.modelValue);
    }
};

// --- Computed properties for status display ---
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
    if (!autosaveStatus.value) return ['fas', 'circle-info']; // Default icon
    switch (autosaveStatus.value.type) {
        case 'success': return ['fas', 'check-circle'];
        case 'error': return ['fas', 'exclamation-circle'];
        case 'pending': return ['fas', 'spinner'];
        default: return ['fas', 'info-circle'];
    }
});

</script>

<style>
/* Base Tiptap Editor Styles */
.tiptap-editor {
    border: 1px solid #e2e8f0;
    /* Tailwind gray-300 */
    border-radius: 0.5rem;
    /* Tailwind rounded-lg */
    overflow: hidden;
    display: flex;
    flex-direction: column;
    background-color: white;
}

.editor-content {
    padding: 1rem;
    min-height: 300px;
    /* Adjust as needed */
    overflow-y: auto;
    max-height: 70vh;
    /* Adjust as needed */
    flex-grow: 1;
}

.editor-content:focus {
    outline: none;
}

/* Disabled state */
.editor-disabled .ProseMirror {
    background-color: #f9fafb;
    /* Tailwind gray-50 */
    cursor: not-allowed;
    opacity: 0.7;
}

.editor-disabled .editor-menu-bar button {
    opacity: 0.5;
    cursor: not-allowed;
}

/* ProseMirror Content Styles (Tailwind 'prose' handles some, but customize here) */
.ProseMirror {
    /* Base styles */
    line-height: 1.6;
}

.ProseMirror>*+* {
    margin-top: 0.75em;
}

.ProseMirror p {
    margin: 0 0 0.75em 0;
    /* Adjust paragraph spacing */
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
    /* Tailwind prose handles font sizes, add specific overrides if needed */
}

.ProseMirror code {
    background-color: rgba(97, 97, 97, 0.1);
    /* Slightly transparent gray */
    color: #374151;
    /* Tailwind gray-700 */
    padding: 0.2rem 0.4rem;
    border-radius: 0.25rem;
    /* Tailwind rounded-sm */
    font-family: monospace;
    font-size: 0.9em;
}

.ProseMirror pre {
    background: #0D0D0D;
    color: #FFF;
    font-family: 'JetBrainsMono', monospace;
    /* Example font */
    padding: 0.75rem 1rem;
    border-radius: 0.5rem;
    /* Tailwind rounded-lg */
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
    /* To prevent extra space below */
    margin: 1em 0;
    border-radius: 0.25rem;
    /* Tailwind rounded */
}

.ProseMirror img.ProseMirror-selectednode {
    outline: 3px solid #68CEF8;
    /* Example selection outline */
}


.ProseMirror blockquote {
    padding-left: 1rem;
    border-left: 3px solid #d1d5db;
    /* Tailwind gray-300 */
    color: #4b5563;
    /* Tailwind gray-600 */
    font-style: italic;
    margin: 1em 0;
}

.ProseMirror hr {
    border: none;
    border-top: 1px solid #e5e7eb;
    /* Tailwind gray-200 */
    margin: 2rem 0;
}

.ProseMirror a {
    color: #2563eb;
    /* Tailwind blue-600 */
    text-decoration: underline;
    cursor: pointer;
}

.ProseMirror a:hover {
    color: #1d4ed8;
    /* Tailwind blue-700 */
}

.ProseMirror mark {
    background-color: #fef9c3;
    /* Tailwind yellow-100 */
    padding: 0.1em 0;
}

/* Tables */
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
    /* Tailwind gray-300 */
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
    /* Tailwind gray-50 */
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
    /* Tailwind blue-600 with alpha */
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
    /* Example color */
    pointer-events: none;
}

.ProseMirror.resize-cursor {
    cursor: ew-resize;
    cursor: col-resize;
}

/* Placeholder */
.ProseMirror p.is-editor-empty:first-child::before {
    content: attr(data-placeholder);
    float: left;
    color: #adb5bd;
    /* Tailwind gray-400 */
    pointer-events: none;
    height: 0;
}

/* Focus Ring */
.tiptap-editor:focus-within {
    border-color: #3b82f6;
    /* Tailwind blue-500 */
    box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.4);
    /* Example focus shadow */
}

.autosave-status-bar {
    min-height: 28px;
    /* Ensure consistent height */
}
</style>