<template>
    <MainLayout :navigationLinks="navigationLinks">

        <Head :title="`解决冲突 - ${page.title}`" />
        <div class="container mx-auto py-6 px-4">
            <div class="bg-white/80 backdrop-blur-sm rounded-lg shadow-lg p-6">
                <!-- 页面标题和返回链接 -->
                <div class="flex justify-between items-center mb-6 border-b pb-4">
                    <h1 class="text-3xl font-bold">解决编辑冲突: {{ page.title }}</h1>
                    <Link :href="route('wiki.show', page.slug)" class="text-blue-600 hover:text-blue-800">
                    <font-awesome-icon :icon="['fas', 'arrow-left']" class="mr-1" /> 返回页面
                    </Link>
                </div>

                <!-- 全局错误提示 -->
                <div v-if="form.errors.general" class="alert-error mb-4">
                    {{ form.errors.general }}
                </div>
                <div v-else-if="$page.props.errors && $page.props.errors.general" class="alert-error mb-4">
                    {{ $page.props.errors.general }}
                </div>

                <!-- 操作指引 -->
                <div class="mb-6 p-4 bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 rounded-md">
                    <p class="font-medium">请仔细检查以下两个版本的差异，然后使用下方的快捷按钮选择一个版本作为基础，或直接在编辑器中手动编辑合并内容，最后提交解决方案。</p>
                </div>

                <!-- 版本差异对比视图 -->
                <div class="mb-8">
                    <h2 class="text-xl font-semibold mb-4">版本差异对比</h2>
                    <div class="border rounded-lg overflow-hidden bg-white">
                        <div v-if="diffHtml" v-html="diffHtml" class="diff-container p-4 text-sm leading-relaxed"></div>
                        <div v-else class="p-4 text-gray-500 italic">无法加载差异视图。</div>
                    </div>
                </div>

                <!-- 解决方案表单 -->
                <form @submit.prevent="submitResolution">
                    <div class="space-y-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                最终解决方案内容 <span class="text-red-500">*</span>
                            </label>
                            <!-- Tiptap 编辑器用于编辑最终内容 -->
                            <Editor v-model="form.content" ref="resolutionEditor" :editable="true" :autosave="false"
                                placeholder="请在此处编辑或粘贴最终合并后的内容..." />
                            <InputError class="mt-1" :message="form.errors.content" />
                        </div>

                        <!-- 快捷操作按钮和提交 -->
                        <div class="flex items-center justify-between flex-wrap gap-4 mt-4 pt-4 border-t">
                            <!-- 快捷选用版本按钮 -->
                            <div class="flex space-x-2 flex-wrap gap-2">
                                <button type="button" @click="selectVersion('current')" class="btn-secondary text-sm">
                                    <font-awesome-icon :icon="['fas', 'check']" class="mr-1" />
                                    采用“当前”版本 (v{{ conflictVersions.current?.version_number || 'N/A' }})
                                </button>
                                <button type="button" @click="selectVersion('conflict')"
                                    class="btn-secondary text-sm bg-red-100 text-red-700 hover:bg-red-200">
                                    <font-awesome-icon :icon="['fas', 'check']" class="mr-1" />
                                    采用“冲突”版本 (v{{ conflictVersions.conflict?.version_number || 'N/A' }})
                                </button>
                            </div>
                            <!-- 提交和取消按钮 -->
                            <div class="flex space-x-3">
                                <Link :href="route('wiki.show', page.slug)" class="btn-secondary">
                                取消
                                </Link>
                                <button type="submit" class="btn-primary bg-green-600 hover:bg-green-700"
                                    :disabled="form.processing">
                                    <font-awesome-icon v-if="form.processing" :icon="['fas', 'spinner']" spin
                                        class="mr-1" />
                                    {{ form.processing ? '正在提交...' : '提交解决方案' }}
                                </button>
                            </div>
                        </div>

                        <!-- 解决说明 -->
                        <div>
                            <label for="resolution_comment" class="block text-sm font-medium text-gray-700 mb-1">
                                解决说明 <span class="text-xs text-gray-500">(可选，说明如何解决的)</span>
                            </label>
                            <textarea id="resolution_comment" v-model="form.resolution_comment" rows="2"
                                class="textarea-field" placeholder="例如：保留了用户A的修改，合并了用户B的部分内容..."></textarea>
                            <InputError class="mt-1" :message="form.errors.resolution_comment" />
                        </div>
                    </div>
                </form>

                <!-- 原始版本内容展示 -->
                <div class="mt-12 pt-6 border-t grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div>
                        <h3 class="font-semibold mb-2">“当前”内容 (v{{ conflictVersions.current?.version_number }})</h3>
                        <p class="text-xs text-gray-500 mb-2">
                            编辑者: {{ conflictVersions.current?.creator?.name || '未知用户' }}
                            <br>时间: {{ formatDateTime(conflictVersions.current?.created_at) }}
                            <br>说明: {{ conflictVersions.current?.comment || '无' }}
                        </p>
                        <div class="version-content prose max-w-none"
                            v-html="conflictVersions.current?.content || '<em>无内容</em>'"></div>
                    </div>
                    <div>
                        <h3 class="font-semibold mb-2 text-red-600">“冲突”内容 (v{{
                            conflictVersions.conflict?.version_number }})</h3>
                        <p class="text-xs text-gray-500 mb-2">
                            编辑者: {{ conflictVersions.conflict?.creator?.name || '未知用户' }}
                            <br>时间: {{ formatDateTime(conflictVersions.conflict?.created_at) }}
                            <br>说明: {{ conflictVersions.conflict?.comment || '无' }}
                        </p>
                        <div class="version-content prose max-w-none border-red-200 bg-red-50"
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

const navigationLinks = mainNavigationLinks;
const pageProps = usePage().props;

const props = defineProps({
    page: { type: Object, required: true },
    conflictVersions: { type: Object, required: true }, // 包含 current 和 conflict 对象
    diffHtml: { type: String, required: true },
});

const resolutionEditor = ref(null); // Ref to the Tiptap editor component

// 初始化表单，默认使用"当前"版本的内容
const form = useForm({
    content: props.conflictVersions?.current?.content || '<p></p>', // 确保有默认值
    resolution_comment: '', // 默认解决说明为空
});

// 选用特定版本内容的函数
const selectVersion = (versionType) => {
    let newContent = '<p></p>'; // 默认空内容
    let commentPrefix = '';

    if (versionType === 'current' && props.conflictVersions?.current) {
        newContent = props.conflictVersions.current.content || newContent;
        commentPrefix = `采用“当前”版本 (v${props.conflictVersions.current.version_number}) 的内容。`;
    } else if (versionType === 'conflict' && props.conflictVersions?.conflict) {
        newContent = props.conflictVersions.conflict.content || newContent;
        commentPrefix = `采用“冲突”版本 (v${props.conflictVersions.conflict.version_number}) 的内容。`;
    }

    form.content = newContent;
    form.resolution_comment = commentPrefix; // 自动填充解决说明

    // 确保 Tiptap 编辑器内容被更新
    if (resolutionEditor.value && resolutionEditor.value.editor) {
        // 使用 Tiptap 的 setContent 命令来更新编辑器内容
        resolutionEditor.value.editor.commands.setContent(newContent, false); // false 表示不触发 update 事件
    }
};

// 提交解决方案
const submitResolution = () => {
    // 从 Tiptap 编辑器获取最新的 HTML 内容
    if (resolutionEditor.value && resolutionEditor.value.editor) {
        form.content = resolutionEditor.value.editor.getHTML();
    }
    // 如果没有填写解决说明，给一个默认值
    if (!form.resolution_comment.trim()) {
        form.resolution_comment = '解决编辑冲突（未提供说明）';
    }
    // 发送 POST 请求到后端处理
    form.post(route('wiki.resolve-conflict', props.page.slug), {
        preserveScroll: true, // 保留滚动位置
        onError: (errors) => {
            console.error("Conflict resolution failed:", errors);
            // 可以在这里添加更具体的错误处理，例如显示FlashMessage
        },
    });
};

</script>

<style>
/* 保持原有样式 */
.diff-container ins {
    background-color: #ccffcc !important;
    text-decoration: none;
}

.diff-container del {
    background-color: #ffcccc !important;
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

/* 改进的样式 */
.alert-error {
    @apply mb-4 p-4 bg-red-100 text-red-700 rounded-md border border-red-200;
}

.alert-warning {
    @apply mb-6 p-4 bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 rounded-md;
}

.version-content {
    @apply border p-4 rounded bg-gray-50 max-h-96 overflow-y-auto text-sm prose max-w-none;
}

.version-content.border-red-200 {
    border-color: #fecaca;
    /* 对应 bg-red-50 */
}

.version-content.bg-red-50 {
    background-color: #fef2f2;
}

/* 按钮基础样式 */
.btn-secondary {
    @apply px-3 py-1 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 text-sm transition;
}

.btn-primary {
    @apply px-6 py-2 text-white rounded-lg transition disabled:opacity-50;
}

.textarea-field {
    @apply w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent;
}
</style>