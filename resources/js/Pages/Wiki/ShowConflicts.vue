<template>
    <MainLayout :navigationLinks="navigationLinks">

        <Head :title="`解决冲突 - ${page.title}`" />
        <div class="container mx-auto py-6 px-4">
            <div class="bg-white/80 backdrop-blur-sm rounded-lg shadow-lg p-6">
                <div class="flex justify-between items-center mb-6 border-b pb-4">
                    <h1 class="text-3xl font-bold">解决编辑冲突: {{ page.title }}</h1>
                    <Link :href="route('wiki.show', page.slug)" class="text-blue-600 hover:text-blue-800">
                    <font-awesome-icon :icon="['fas', 'arrow-left']" class="mr-1" /> 返回页面
                    </Link>
                </div>

                <!-- Display general errors passed from backend -->
                <div v-if="$page.props.errors && $page.props.errors.general"
                    class="mb-4 p-4 bg-red-100 text-red-700 rounded-md">
                    {{ $page.props.errors.general }}
                </div>

                <div class="mb-6 p-4 bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 rounded-md">
                    <p class="font-medium">请仔细检查以下两个版本的差异，然后选择一个版本，或者手动编辑合并后的内容进行提交。</p>
                </div>

                <!-- Version Diff Display -->
                <div class="mb-8">
                    <h2 class="text-xl font-semibold mb-4">版本差异对比</h2>
                    <div class="border rounded-lg overflow-hidden bg-white">
                        <!-- Render diffHtml passed from backend -->
                        <div v-if="diffHtml" v-html="diffHtml" class="diff-container p-4 text-sm leading-relaxed"></div>
                        <div v-else class="p-4 text-gray-500 italic">无法加载差异视图。</div>
                    </div>
                </div>

                <!-- Resolution Form -->
                <form @submit.prevent="submitResolution">
                    <div class="space-y-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                解决方案内容 <span class="text-red-500">*</span>
                            </label>
                            <Editor v-model="form.content" ref="resolutionEditor" :editable="true" :autosave="false" />
                            <InputError class="mt-1" :message="form.errors.content" />
                        </div>

                        <div class="flex items-center justify-between flex-wrap gap-4 mt-4">
                            <div class="flex space-x-2 flex-wrap gap-2">
                                <button type="button" @click="selectVersion('current')"
                                    class="px-3 py-1 bg-blue-100 text-blue-700 rounded hover:bg-blue-200 text-sm">
                                    采用“当前”版本 (v{{ conflictVersions.current?.version_number || 'N/A' }})
                                </button>
                                <button type="button" @click="selectVersion('conflict')"
                                    class="px-3 py-1 bg-red-100 text-red-700 rounded hover:bg-red-200 text-sm">
                                    采用“冲突”版本 (v{{ conflictVersions.conflict?.version_number || 'N/A' }})
                                </button>
                            </div>
                            <div class="flex space-x-3">
                                <Link :href="route('wiki.show', page.slug)"
                                    class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                                取消
                                </Link>
                                <button type="submit"
                                    class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition disabled:opacity-50"
                                    :disabled="form.processing">
                                    <font-awesome-icon v-if="form.processing" :icon="['fas', 'spinner']" spin
                                        class="mr-1" />
                                    {{ form.processing ? '正在提交...' : '提交解决方案' }}
                                </button>
                            </div>
                        </div>
                        <div>
                            <label for="resolution_comment" class="block text-sm font-medium text-gray-700 mb-1">
                                解决说明 <span class="text-xs text-gray-500">(可选，说明如何解决的)</span>
                            </label>
                            <textarea id="resolution_comment" v-model="form.resolution_comment" rows="2"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="例如：保留了用户A的修改，合并了用户B的部分内容..."></textarea>
                            <InputError class="mt-1" :message="form.errors.resolution_comment" />
                        </div>

                    </div>
                </form>

                <!-- Original Versions Display (Reference) -->
                <div class="mt-12 pt-6 border-t grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div>
                        <h3 class="font-semibold mb-2">“当前”内容 (v{{ conflictVersions.current?.version_number }})</h3>
                        <p class="text-xs text-gray-500 mb-2">编辑者: {{ conflictVersions.current?.creator?.name || '未知' }}
                            @ {{ formatDateTime(conflictVersions.current?.created_at) }}<br>说明: {{
                                conflictVersions.current?.comment || '无' }}</p>
                        <div class="border p-4 rounded bg-gray-50 max-h-96 overflow-y-auto text-sm prose max-w-none"
                            v-html="conflictVersions.current?.content || '<em>无内容</em>'"></div>
                    </div>
                    <div>
                        <h3 class="font-semibold mb-2 text-red-600">“冲突”内容 (v{{
                            conflictVersions.conflict?.version_number }})</h3>
                        <p class="text-xs text-gray-500 mb-2">编辑者: {{ conflictVersions.conflict?.creator?.name || '未知'
                            }} @ {{ formatDateTime(conflictVersions.conflict?.created_at) }}<br>说明: {{
                                conflictVersions.conflict?.comment || '无' }}</p>
                        <div class="border p-4 rounded bg-red-50 max-h-96 overflow-y-auto text-sm prose max-w-none"
                            v-html="conflictVersions.conflict?.content || '<em>无内容</em>'"></div>
                    </div>
                </div>
            </div>
        </div>
        <FlashMessage />
    </MainLayout>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { Link, Head, useForm, usePage } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayouts/MainLayout.vue';
import Editor from '@/Components/Wiki/Editor.vue';
import InputError from '@/Components/Other/InputError.vue';
import FlashMessage from '@/Components/Other/FlashMessage.vue';
import { formatDateTime } from '@/utils/formatters';
import { mainNavigationLinks } from '@/config/navigationConfig';
// Assuming FontAwesome is globally available

const navigationLinks = mainNavigationLinks;
const pageProps = usePage().props;

const props = defineProps({
    page: { type: Object, required: true },
    conflictVersions: { type: Object, required: true }, // { current: versionObject, conflict: versionObject }
    diffHtml: { type: String, required: true },
});

const resolutionEditor = ref(null);

// Initialize form with the 'current' version content by default
const form = useForm({
    content: props.conflictVersions?.current?.content || '',
    resolution_comment: '', // Start with empty comment
});

// Function to load content from a specific version into the editor
const selectVersion = (versionType) => {
    let newContent = '';
    if (versionType === 'current' && props.conflictVersions?.current) {
        newContent = props.conflictVersions.current.content;
    } else if (versionType === 'conflict' && props.conflictVersions?.conflict) {
        newContent = props.conflictVersions.conflict.content;
    }
    form.content = newContent;
    // Force update the Tiptap editor instance
    if (resolutionEditor.value && resolutionEditor.value.editor) {
        resolutionEditor.value.editor.commands.setContent(newContent, false);
    }
    // Set a default comment based on selection
    if (versionType === 'current') {
        form.resolution_comment = `采用当前版本 (v${props.conflictVersions.current?.version_number})`;
    } else if (versionType === 'conflict') {
        form.resolution_comment = `采用冲突版本 (v${props.conflictVersions.conflict?.version_number})`;
    }
};

// Function to submit the resolved content
const submitResolution = () => {
    // Ensure the form content is up-to-date with the editor state
    if (resolutionEditor.value && resolutionEditor.value.editor) {
        form.content = resolutionEditor.value.editor.getHTML();
    }
    if (!form.resolution_comment) {
        form.resolution_comment = '解决编辑冲突（未提供说明）';
    }
    form.post(route('wiki.resolve-conflict', props.page.slug), {
        preserveScroll: true,
        // onSuccess is handled by Inertia redirect by the controller
        onError: (errors) => {
            console.error("Conflict resolution failed:", errors);
            // Errors are automatically handled by InputError components
        },
    });
};

</script>

<style>
/* Diff viewer styles (reuse from Compare.vue or global scope) */
.diff-container ins {
    background-color: #ccffcc !important;
    /* Ensure high specificity */
    text-decoration: none;
}

.diff-container del {
    background-color: #ffcccc !important;
    /* Ensure high specificity */
    /* text-decoration: line-through; */
    /* Diff lib might handle this */
}

.diff-container pre {
    white-space: pre-wrap;
    word-wrap: break-word;
    font-family: monospace;
    background-color: #f8f9fa;
    border: 1px solid #dee2e6;
    padding: 1rem;
    border-radius: 0.25rem;
    overflow-x: auto;
}

/* php-diff styles if directly embedding its output */
.diff ul {
    background: #fff;
    overflow: auto;
    font-size: 13px;
    list-style: none;
    margin: 0;
    padding: 0;
    display: table;
    width: 100%;
}

.diff del,
.diff ins {
    display: block;
    text-decoration: none;
}

.diff li {
    padding: 0;
    display: table-row;
    margin: 0;
    height: 1em;
}

.diff li.ins {
    background: #dfd;
    color: #080;
}

.diff li.del {
    background: #fee;
    color: #b00;
}

.diff li.unchanged {
    background: #fff;
}

.diff li.diff-prefix {
    padding: 0 .4em;
    min-width: 2.5em;
    text-align: right;
    user-select: none;
    display: table-cell;
    vertical-align: top;
    border-right: 1px solid #ced4da;
    color: #6c757d;
}

.diff li.diff-prefix::before {
    content: attr(data-prefix);
}

.diff code {
    font-family: monospace;
    white-space: pre-wrap;
    word-wrap: break-word;
    display: table-cell;
    width: 100%;
    padding-left: .4em;
    vertical-align: top;
}

/* Prose display adjustments */
.prose img {
    max-width: 100%;
    height: auto;
    margin-top: 1em;
    margin-bottom: 1em;
}

.prose table {
    width: 100%;
    margin-top: 1em;
    margin-bottom: 1em;
}

.prose td,
.prose th {
    border: 1px solid #e2e8f0;
    padding: 0.5rem 0.75rem;
}

.prose th {
    background-color: #f1f5f9;
    font-weight: 600;
}

.prose pre {
    background-color: #f3f4f6;
    padding: 1rem;
    border-radius: 0.375rem;
    overflow-x: auto;
}

.prose code {
    background-color: transparent;
    padding: 0;
    font-size: inherit;
}

/* Reset code styles within prose for diff display */
</style>