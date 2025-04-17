<template>
    <MainLayout :navigationLinks="navigationLinks">

        <Head :title="`解决冲突 - ${page.title}`" />
        <div class="container mx-auto py-8 px-4 md:px-6 lg:px-8">
            <!-- 修改: 添加标准的内容包裹 -->
            <div class="bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm rounded-lg shadow-lg p-6 md:p-8">
                <div
                    class="flex flex-col md:flex-row justify-between md:items-center mb-6 border-b dark:border-gray-700 pb-4">
                    <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-gray-100 mb-2 md:mb-0">解决编辑冲突: {{
                        page.title }}</h1>
                    <div class="flex items-center space-x-3 text-sm">
                        <Link :href="route('wiki.show', page.slug)"
                            class="text-blue-600 dark:text-blue-400 hover:underline flex items-center">
                        <font-awesome-icon :icon="['fas', 'arrow-left']" class="mr-1 h-3 w-3" /> 返回页面
                        </Link>
                    </div>
                </div>

                <div v-if="form.errors.general" class="alert-error mb-4">
                    {{ form.errors.general }}
                </div>
                <div v-else-if="$page.props.errors && $page.props.errors.general" class="alert-error mb-4">
                    {{ $page.props.errors.general }}
                </div>

                <div
                    class="mb-6 p-4 bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 rounded-md dark:bg-yellow-900/30 dark:border-yellow-600 dark:text-yellow-300">
                    <p class="font-medium text-sm">请仔细检查以下两个版本的差异，然后使用下方的快捷按钮选择一个版本作为基础，或直接在编辑器中手动编辑合并内容，最后提交解决方案。</p>
                </div>

                <div class="mb-8">
                    <h2 class="text-xl font-semibold mb-4 text-gray-800 dark:text-gray-200">版本差异对比</h2>
                    <div class="border rounded-lg overflow-hidden bg-white dark:bg-gray-800 dark:border-gray-700">
                        <!-- 将 v-html 应用到内部 div，并添加样式 -->
                        <div v-if="diffHtml" v-html="diffHtml"
                            class="diff-container text-sm leading-relaxed max-h-[50vh] overflow-y-auto"></div>
                        <div v-else class="p-4 text-gray-500 dark:text-gray-400 italic">无法加载差异视图。</div>
                    </div>
                </div>

                <!-- 修改: 按钮样式和布局 -->
                <form @submit.prevent="submitResolution" class="mt-8">
                    <!-- 编辑器 -->
                    <div class="mb-6">
                        <label class="block text-lg font-semibold text-gray-800 dark:text-gray-200 mb-3">
                            最终解决方案内容 <span class="text-red-500">*</span>
                        </label>
                        <Editor v-model="form.content" ref="resolutionEditor" :editable="true" :autosave="false"
                            placeholder="请在此处编辑或粘贴最终合并后的内容..." />
                        <InputError class="mt-1" :message="form.errors.content" />
                    </div>

                    <!-- 快捷选用按钮和提交按钮 -->
                    <div
                        class="flex flex-col sm:flex-row justify-between items-center flex-wrap gap-4 mt-6 pt-6 border-t dark:border-gray-700">
                        <div class="flex flex-wrap gap-2">
                            <button type="button" @click="selectVersion('current')" class="btn-secondary text-sm">
                                <font-awesome-icon :icon="['fas', 'check']" class="mr-1.5 h-3 w-3" />
                                采用“当前”版本 (v{{ conflictVersions.current?.version_number || 'N/A' }})
                            </button>
                            <button type="button" @click="selectVersion('conflict')"
                                class="btn-secondary text-sm bg-red-100 text-red-700 hover:bg-red-200 dark:bg-red-900/40 dark:text-red-300 dark:hover:bg-red-900/60">
                                <font-awesome-icon :icon="['fas', 'check']" class="mr-1.5 h-3 w-3" />
                                采用“冲突”版本 (v{{ conflictVersions.conflict?.version_number || 'N/A' }})
                            </button>
                        </div>
                        <div class="flex space-x-3">
                            <Link :href="route('wiki.show', page.slug)" class="btn-secondary">
                            取消
                            </Link>
                            <button type="submit"
                                class="btn-primary bg-green-600 hover:bg-green-700 dark:bg-green-500 dark:hover:bg-green-600"
                                :disabled="form.processing">
                                <font-awesome-icon v-if="form.processing" :icon="['fas', 'spinner']" spin
                                    class="mr-1" />
                                {{ form.processing ? '正在提交...' : '提交解决方案' }}
                            </button>
                        </div>
                    </div>

                    <!-- 解决说明 -->
                    <div class="mt-6">
                        <label for="resolution_comment"
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            解决说明 <span class="text-xs text-gray-500 dark:text-gray-400">(可选，说明如何解决的)</span>
                        </label>
                        <textarea id="resolution_comment" v-model="form.resolution_comment" rows="2"
                            class="textarea-field" placeholder="例如：保留了用户A的修改，合并了用户B的部分内容..."></textarea>
                        <InputError class="mt-1" :message="form.errors.resolution_comment" />
                    </div>
                </form>

                <!-- 原始内容预览 -->
                <div class="mt-12 pt-6 border-t dark:border-gray-700 grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div>
                        <h3 class="font-semibold mb-2 text-base text-gray-800 dark:text-gray-200">“当前”内容 (v{{
                            conflictVersions.current?.version_number }})</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">
                            编辑者: {{ conflictVersions.current?.creator?.name || '未知用户' }}
                            <br>时间: {{ formatDateTime(conflictVersions.current?.created_at) }}
                            <br>说明: {{ conflictVersions.current?.comment || '无' }}
                        </p>
                        <div class="version-content prose dark:prose-invert max-w-none text-sm"
                            v-html="conflictVersions.current?.content || '<em>无内容</em>'"></div>
                    </div>
                    <div>
                        <h3 class="font-semibold mb-2 text-base text-red-700 dark:text-red-400">“冲突”内容 (v{{
                            conflictVersions.conflict?.version_number }})</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">
                            编辑者: {{ conflictVersions.conflict?.creator?.name || '未知用户' }}
                            <br>时间: {{ formatDateTime(conflictVersions.conflict?.created_at) }}
                            <br>说明: {{ conflictVersions.conflict?.comment || '无' }}
                        </p>
                        <div class="version-content prose dark:prose-invert max-w-none text-sm border-red-200 bg-red-50 dark:border-red-800/50 dark:bg-red-900/20"
                            v-html="conflictVersions.conflict?.content || '<em>无内容</em>'"></div>
                    </div>
                </div>
            </div>
        </div>
        <FlashMessage />
    </MainLayout>
</template>

<script setup>
import { ref } from 'vue'; // Removed onMounted, computed as they are not used here directly
import { Link, Head, useForm, usePage } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayouts/MainLayout.vue';
import Editor from '@/Components/Wiki/Editor.vue';
import InputError from '@/Components/Other/InputError.vue';
import FlashMessage from '@/Components/Other/FlashMessage.vue';
import { formatDateTime } from '@/utils/formatters';
import { mainNavigationLinks } from '@/config/navigationConfig';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'; // Added for icons

const navigationLinks = mainNavigationLinks;
const pageProps = usePage().props; // Keep this if flash messages or auth checks are needed

const props = defineProps({
    page: { type: Object, required: true },
    conflictVersions: { type: Object, required: true }, // Contains current and conflict objects
    diffHtml: { type: String, required: true }, // HTML string for the diff
});

const resolutionEditor = ref(null); // Ref for the Tiptap editor instance

const form = useForm({
    content: props.conflictVersions?.current?.content || '<p></p>', // Initialize with current version content
    resolution_comment: '', // Default resolution comment is empty
});

// Function to set editor content based on chosen version
const selectVersion = (versionType) => {
    let newContent = '<p></p>'; // Default to empty paragraph
    let commentPrefix = '';

    if (versionType === 'current' && props.conflictVersions?.current) {
        newContent = props.conflictVersions.current.content || newContent;
        commentPrefix = `采用“当前”版本 (v${props.conflictVersions.current.version_number || 'N/A'}) 的内容。`;
    } else if (versionType === 'conflict' && props.conflictVersions?.conflict) {
        newContent = props.conflictVersions.conflict.content || newContent;
        commentPrefix = `采用“冲突”版本 (v${props.conflictVersions.conflict.version_number || 'N/A'}) 的内容。`;
    }

    // Update the form data
    form.content = newContent;
    form.resolution_comment = commentPrefix;

    // Update the Tiptap editor content if the editor instance exists
    if (resolutionEditor.value && resolutionEditor.value.editor) {
        resolutionEditor.value.editor.commands.setContent(newContent, false);
    }
};

// Function to submit the resolution
const submitResolution = () => {
    // Get the latest content from the editor before submitting
    if (resolutionEditor.value && resolutionEditor.value.editor) {
        form.content = resolutionEditor.value.editor.getHTML();
    }

    // Provide a default comment if none is entered
    if (!form.resolution_comment.trim()) {
        form.resolution_comment = '解决编辑冲突（未提供说明）';
    }

    // POST the form data to the resolve conflict route
    form.post(route('wiki.resolve-conflict', props.page.slug), {
        preserveScroll: true,
        onError: (errors) => {
            console.error("Conflict resolution failed:", errors);
            // Optionally show error message using FlashMessage or directly
            const firstError = Object.values(errors).flat()[0];
            // Example: flashMessageRef.value?.addMessage('error', firstError || '提交解决方案失败。');
            alert(firstError || '提交解决方案时出错，请检查内容或稍后再试。'); // Simple alert fallback
        },
    });
};
</script>

<style scoped>
/* Unified Button Styles */
.btn-primary {
    @apply inline-flex items-center justify-center px-5 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 transition;
}

.btn-secondary {
    @apply inline-flex items-center justify-center px-5 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 transition;
}

.textarea-field {
    @apply w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 dark:placeholder-gray-400 text-sm;
}

/* Diff Container Style */
.diff-container {
    /* max-height: 50vh; Ensure height is sufficient or remove constraint if content scrolls within */
    background-color: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    overflow: auto;
    /* Ensure overflow scrolls */
    @apply dark:bg-gray-800 dark:border-gray-700;
}

/* Deep styles for the diff table generated by the library */
:deep(.diff-container table.diff) {
    @apply text-xs leading-snug dark:text-gray-300;
}

:deep(.diff-container td) {
    @apply px-2 py-0.5 border-gray-200 dark:border-gray-600;
}

:deep(.diff-container th) {
    @apply px-2 py-1 bg-gray-100 dark:bg-gray-700 border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-400 sticky top-0;
}

/* Sticky header */
:deep(.diff-container td.lines-no) {
    @apply pr-2 bg-gray-100 dark:bg-gray-700 border-r border-gray-200 dark:border-gray-600 text-gray-400 dark:text-gray-500 sticky left-0;
}

/* Sticky line numbers */
:deep(.diff-container .ChangeDelete .Left) {
    @apply bg-red-100 dark:bg-red-900/30;
}

:deep(.diff-container .ChangeDelete .Left del) {
    @apply bg-red-200 dark:bg-red-800/40 text-red-700 dark:text-red-300;
}

:deep(.diff-container .ChangeInsert .Right) {
    @apply bg-green-100 dark:bg-green-900/30;
}

:deep(.diff-container .ChangeInsert .Right ins) {
    @apply bg-green-200 dark:bg-green-800/40 text-green-800 dark:text-green-300;
    text-decoration: none;
}

:deep(.diff-container .ChangeReplace .Left) {
    @apply bg-red-100 dark:bg-red-900/30;
}

:deep(.diff-container .ChangeReplace .Right) {
    @apply bg-green-100 dark:bg-green-900/30;
}

:deep(.diff-container .ChangeReplace del) {
    @apply bg-red-200 dark:bg-red-800/40 text-red-700 dark:text-red-300;
}

:deep(.diff-container .ChangeReplace ins) {
    @apply bg-green-200 dark:bg-green-800/40 text-green-800 dark:text-green-300;
    text-decoration: none;
}


/* Version Content Preview Style */
.version-content {
    @apply border p-4 rounded-lg max-h-[40vh] overflow-y-auto prose max-w-none text-sm leading-relaxed dark:border-gray-700 dark:prose-invert bg-gray-50 dark:bg-gray-800/30;
    scrollbar-width: thin;
    scrollbar-color: #cbd5e1 #f1f5f9;
    /* Light scrollbar */
    @apply dark:bg-gray-800/30;
        /* Dark scrollbar */
    /* Dark scrollbar */
}

.version-content.border-red-200 {
    border-color: #fecaca;
    @apply dark:border-red-800/50;
}

.version-content.bg-red-50 {
    background-color: #fef2f2;
    @apply dark:bg-red-900/20;
}

.version-content::-webkit-scrollbar {
    width: 6px;
}

.version-content::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 3px;
    @apply dark:bg-gray-700/50;
}

.version-content::-webkit-scrollbar-thumb {
    background-color: #cbd5e1;
    border-radius: 3px;
    @apply dark:bg-gray-600;
}

.alert-error {
    @apply mb-4 p-4 bg-red-100 text-red-700 rounded-md border border-red-200 dark:bg-red-900/40 dark:border-red-800 dark:text-red-300;
}
</style>