<template>
    <div class="tiptap-editor dark">
        <menu-bar v-if="editor" :editor="editor" :is-editable="editable" @toggle-edit="$emit('toggle-edit')" />
        <editor-content v-if="editor" :editor="editor" class="editor-content prose max-w-none dark:prose-invert"
            :class="{ 'editor-disabled': !editable }" />
        <div v-if="autosaveEnabled"
            class="autosave-status-bar p-2 text-xs text-gray-500 border-t border-gray-700 text-right">
            <span v-if="autosaveStatus" :class="autosaveStatusClass" class="ml-2 flex items-center justify-end">
                <font-awesome-icon :icon="autosaveStatusIcon" :spin="autosaveStatus.type === 'pending'" class="mr-1" />
                {{ autosaveStatus.message }}
            </span>
            <span v-else class="ml-2 text-gray-500 italic">草稿未更改</span>
        </div>
    </div>
</template>

<script setup>
// Script 部分保持不变 (除非你需要改变逻辑)
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
// 确保所有需要的语言都已导入
import javascript from 'highlight.js/lib/languages/javascript';
import css from 'highlight.js/lib/languages/css';
import php from 'highlight.js/lib/languages/php';
import html from 'highlight.js/lib/languages/xml'; // xml 通常包含 html
import python from 'highlight.js/lib/languages/python';
import json from 'highlight.js/lib/languages/json';

// 注册语言
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
        StarterKit.configure({ heading: { levels: [1, 2, 3, 4, 5, 6] }, codeBlock: false }), // 确保 CodeBlock 由 lowlight 接管
        Image.configure({ allowBase64: true, inline: true, HTMLAttributes: { class: 'max-w-full h-auto rounded' } }),
        Link.configure({ openOnClick: false, HTMLAttributes: { class: 'text-blue-400 underline hover:text-blue-300' } }), // 调整暗色链接颜色
        Placeholder.configure({ placeholder: props.placeholder }),
        Table.configure({ resizable: true }), TableRow, TableCell, TableHeader,
        Typography,
        Underline,
        TextAlign.configure({ types: ['heading', 'paragraph'] }),
        Highlight.configure({ HTMLAttributes: { class: 'bg-yellow-300/30 dark:bg-yellow-700/40 px-1 rounded' } }), // 调整高亮颜色
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
        debouncedSaveDraft(newHtml); // Debounced call
    },
    onCreate: ({ editor }) => {
        editor.setEditable(props.editable); // Ensure editable state is set on create
        lastSavedContent.value = editor.getHTML(); // Initialize last saved content
    }
});

// --- Watchers ---
watch(() => props.modelValue, (newValue) => {
    if (editor.value && editor.value.getHTML() !== newValue) {
        console.log("Editor content updated from external modelValue");
        editor.value.commands.setContent(newValue, false);
        hasUnsavedChanges.value = false; // Reset flag when content is set externally
        lastSavedContent.value = newValue; // Update last saved state
    }
});

watch(() => props.editable, (newValue) => {
    console.log("Editable prop changed:", newValue);
    if (editor.value) {
        editor.value.setEditable(newValue);
        // Manage autosave timer based on editable state
        if (newValue && autosaveEnabled.value) {
            console.log("Editable turned on, setting up autosave timer");
            setupAutosaveTimer();
        } else {
            console.log("Editable turned off, clearing autosave timer and potentially saving");
            clearAutosaveTimer();
            // Save draft immediately if becoming non-editable with changes
            if (!newValue && autosaveEnabled.value && hasUnsavedChanges.value) {
                if (debounceTimer) clearTimeout(debounceTimer);
                saveDraft(editor.value.getHTML() || props.modelValue);
            }
        }
    }
}, { immediate: true });

// --- Computed ---
const autosaveEnabled = computed(() => props.autosave && props.pageId !== null && props.editable);

// --- Lifecycle Hooks ---
onMounted(() => {
    console.log("Editor component mounted, initial editable state:", props.editable);
    if (autosaveEnabled.value) {
        console.log("Autosave enabled on mount, setting up timer");
        setupAutosaveTimer();
    }
    window.addEventListener('online', handleOnline);
    // Ensure editor content is empty if modelValue starts empty
    if (editor.value && !props.modelValue) {
        console.log("Initial modelValue is empty, ensuring editor content is empty");
        editor.value.commands.setContent('', false); // Use empty string
    }
});

onBeforeUnmount(() => {
    console.log("Editor component unmounting");
    clearAutosaveTimer(); // Clear interval timer
    // Attempt to save unsaved draft using sendBeacon if available
    if (autosaveEnabled.value && hasUnsavedChanges.value && editor.value) {
        if (debounceTimer) clearTimeout(debounceTimer); // Clear debounced call
        console.log("Saving draft on unmount via sendBeacon");
        if (navigator.sendBeacon) {
            const url = route('wiki.save-draft', props.pageId);
            const formData = new FormData();
            formData.append('content', editor.value.getHTML());
            // Make sure CSRF token exists
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            if (csrfToken) {
                formData.append('_token', csrfToken);
            }
            try {
                const success = navigator.sendBeacon(url, formData);
                console.log("sendBeacon attempt status:", success);
            } catch (e) {
                console.error("sendBeacon failed on unmount:", e);
                // Fallback maybe? localStorage isn't ideal for complex content.
            }
        } else {
            console.warn("navigator.sendBeacon not supported. Draft might not be saved on unmount.");
            // Consider a more robust fallback if needed, localStorage is simple but limited
            // localStorage.setItem(`draft_${props.pageId}`, editor.value.getHTML());
        }
    }
    if (editor.value) {
        console.log("Destroying editor instance");
        editor.value.destroy();
    }
    window.removeEventListener('online', handleOnline);
});


// --- Autosave Logic ---
const debouncedSaveDraft = debounce(async (content) => {
    await saveDraft(content);
}, 2000); // 2 second debounce after typing stops

const saveDraft = async (content) => {
    if (!autosaveEnabled.value) {
        console.log("Autosave not enabled or editor not editable, skipping draft save.");
        return;
    }
    if (!editor.value || content === lastSavedContent.value) {
        console.log("No changes or editor not ready, skipping draft save.");
        // Reset status if no changes
        // if (autosaveStatus.value?.type === 'success') {
        //     autosaveStatus.value = null;
        //     emit('statusUpdate', null);
        // }
        return;
    }

    console.log("Attempting to save draft...");
    autosaveStatus.value = { type: 'pending', message: '正在自动保存草稿...' };
    emit('statusUpdate', { ...autosaveStatus.value });

    try {
        const response = await axios.post(route('wiki.save-draft', props.pageId), { content });
        console.log("Draft saved successfully:", response.data);
        lastSavedContent.value = content; // Update last saved content *after* successful save
        hasUnsavedChanges.value = false; // Reset unsaved changes flag
        const savedAtDate = response.data.saved_at ? new Date(response.data.saved_at) : new Date();
        autosaveStatus.value = {
            type: 'success',
            message: `草稿已于 ${formatDateTime(savedAtDate)} 保存`,
            saved_at: response.data.saved_at // Store the ISO string
        };
        emit('saved', { ...autosaveStatus.value });
        emit('statusUpdate', { ...autosaveStatus.value });

        // Clear success message after a delay
        setTimeout(() => {
            if (autosaveStatus.value?.type === 'success') {
                autosaveStatus.value = null; // Clear status for a cleaner UI
                emit('statusUpdate', null);
            }
        }, 5000); // Keep success message for 5 seconds

    } catch (error) {
        const errorMessage = error.response?.data?.message || error.message || '网络错误，无法保存';
        console.error('保存草稿失败:', errorMessage, error.response || error);
        autosaveStatus.value = {
            type: 'error',
            message: `草稿保存失败: ${errorMessage}`
        };
        emit('statusUpdate', { ...autosaveStatus.value });
        emit('error', error); // Emit the actual error object
    }
};

const setupAutosaveTimer = () => {
    clearAutosaveTimer(); // Clear any existing timer first
    if (autosaveEnabled.value) {
        console.log(`Setting up autosave timer with interval ${props.autosaveInterval}ms`);
        autosaveTimer = setInterval(() => {
            console.log("Autosave timer triggered. Checking for changes...");
            if (hasUnsavedChanges.value && editor.value) {
                if (debounceTimer) clearTimeout(debounceTimer); // Clear debounce if interval triggers save
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
    if (debounceTimer) {
        console.log("Clearing debounce timer");
        clearTimeout(debounceTimer);
        debounceTimer = null;
    }
};

// Handle coming back online - save immediately if there are changes
const handleOnline = () => {
    if (autosaveEnabled.value && hasUnsavedChanges.value && editor.value) {
        if (debounceTimer) clearTimeout(debounceTimer);
        saveDraft(editor.value.getHTML() || props.modelValue);
    }
};

// --- Status Display ---
const autosaveStatusClass = computed(() => {
    if (!autosaveStatus.value) return 'text-gray-500 italic'; // Use darker gray for dark mode
    switch (autosaveStatus.value.type) {
        case 'success': return 'text-green-400'; // Brighter green for dark mode
        case 'error': return 'text-red-400';   // Brighter red
        case 'pending': return 'text-blue-400';  // Brighter blue
        default: return 'text-gray-500';       // Keep this for neutral/cleared state
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


// Expose editor instance if needed by parent
defineExpose({ editor });

</script>

<style scoped>
/* Use a dark background for the editor container */
.tiptap-editor {
    border: 1px solid #4b5563;
    /* Tailwind gray-600 */
    border-radius: 0.5rem;
    /* rounded-lg */
    overflow: hidden;
    display: flex;
    flex-direction: column;
    background-color: #1f2937;
    /* Tailwind gray-800 */
    color: #d1d5db;
    /* Tailwind gray-300 - Default text color for the component */
}

/* Ensure editor content area has padding and takes space */
.editor-content {
    padding: 1rem;
    min-height: 300px;
    /* Or adjust as needed */
    overflow-y: auto;
    max-height: 70vh;
    /* Example max height */
    flex-grow: 1;
    /* Ensure prose styles apply correctly */
    @apply prose max-w-none prose-invert;
    /* prose-invert applies dark theme prose styles */
}

/* Ensure the editor itself has a transparent background so container bg shows */
.ProseMirror {
    background: transparent;
    outline: none;
    /* Keep outline none */
    color: inherit;
    /* Inherit text color from parent */
}

/* Disabled state styling */
.editor-disabled .ProseMirror {
    background-color: #374151;
    /* Tailwind gray-700 */
    cursor: not-allowed;
    opacity: 0.7;
}

.editor-disabled .editor-menu-bar button {
    opacity: 0.5;
    cursor: not-allowed;
}

/* Adjust autosave status bar for dark theme */
.autosave-status-bar {
    border-top: 1px solid #4b5563;
    /* Darker border */
    background-color: #111827;
    /* Darker gray for status bar bg */
    color: #9ca3af;
    /* Lighter gray for base text */
}

.autosave-status-bar span {
    transition: color 0.3s ease;
}

.autosave-status-bar .text-gray-500 {
    color: #6b7280;
    /* Darker placeholder gray */
}

/* Focus ring for dark mode */
.tiptap-editor:focus-within {
    border-color: #3b82f6;
    /* Tailwind blue-500 */
    box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.5);
    /* Semi-transparent blue */
}

/* Override Prose styles specifically within the editor if needed */
.ProseMirror p {
    margin-bottom: 0.75em;
}

.ProseMirror h1,
.ProseMirror h2,
.ProseMirror h3,
.ProseMirror h4,
.ProseMirror h5,
.ProseMirror h6 {
    margin-top: 1.5em;
    margin-bottom: 0.5em;
}

.ProseMirror table {
    border-collapse: collapse;
    width: 100%;
    margin: 1em 0;
}

.ProseMirror th,
.ProseMirror td {
    border: 1px solid #4b5563;
    padding: 0.5em;
}

.ProseMirror th {
    background-color: #374151;
}

.ProseMirror tr:nth-child(even) {
    background-color: rgba(55, 65, 81, 0.5);
}
</style>