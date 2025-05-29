<template>
    <!-- 主要布局容器，引入导航链接 -->
    <MainLayout :navigationLinks="navigationLinks">

        <!-- 设置页面标题为解决冲突并显示页面标题 -->

        <Head :title="`解决冲突 - ${page.title}`" />
        <div class="container mx-auto py-8 px-4 md:px-6 lg:px-8">
            <!-- 页面内容主体卡片，背景半透明，圆角和阴影 -->
            <div class="bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm rounded-lg shadow-lg p-6 md:p-8">
                <!-- 页面头部，包含标题和返回按钮 -->
                <div
                    class="flex flex-col md:flex-row justify-between md:items-center mb-6 border-b dark:border-gray-700 pb-4">
                    <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-gray-100 mb-2 md:mb-0">解决编辑冲突: {{
                        page.title }}</h1>
                    <div class="flex items-center space-x-3 text-sm">
                        <!-- 返回页面详情的链接 -->
                        <Link :href="route('wiki.show', page.slug)"
                            class="text-blue-600 dark:text-blue-400 hover:underline flex items-center">
                        <font-awesome-icon :icon="['fas', 'arrow-left']" class="mr-1 h-3 w-3" /> 返回页面
                        </Link>
                    </div>
                </div>

                <!-- 错误消息显示区域 -->
                <div v-if="form.errors.general" class="alert-error mb-4">
                    {{ form.errors.general }}
                </div>
                <div v-else-if="$page.props.errors && $page.props.errors.general" class="alert-error mb-4">
                    {{ $page.props.errors.general }}
                </div>

                <!-- 操作说明横幅 -->
                <div
                    class="mb-6 p-4 bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 rounded-md dark:bg-yellow-900/30 dark:border-yellow-600 dark:text-yellow-300">
                    <p class="font-medium text-sm">请仔细检查以下两个版本的差异，然后使用下方的快捷按钮选择一个版本作为基础，或直接在编辑器中手动编辑合并内容，最后提交解决方案。</p>
                </div>

                <!-- 版本差异对比视图 -->
                <div class="mb-8">
                    <h2 class="text-xl font-semibold mb-4 text-gray-800 dark:text-gray-200">版本差异对比</h2>
                    <div class="border rounded-lg overflow-hidden bg-white dark:bg-gray-800 dark:border-gray-700">
                        <!-- 渲染差异HTML内容 -->
                        <div v-if="diffHtml" v-html="diffHtml"
                            class="diff-container text-sm leading-relaxed max-h-[50vh] overflow-y-auto"></div>
                        <div v-else class="p-4 text-gray-500 dark:text-gray-400 italic">无法加载差异视图。</div>
                    </div>
                </div>

                <!-- 解决方案提交表单 -->
                <form @submit.prevent="submitResolution" class="mt-8">
                    <div class="mb-6">
                        <label class="block text-lg font-semibold text-gray-800 dark:text-gray-200 mb-3">
                            最终解决方案内容 <span class="text-red-500">*</span>
                        </label>
                        <!-- Tiptap 编辑器组件用于编辑合并内容 -->
                        <Editor v-model="form.content" ref="resolutionEditor" :editable="true" :autosave="false"
                            placeholder="请在此处编辑或粘贴最终合并后的内容..." />
                        <!-- 编辑器内容错误提示 -->
                        <InputError class="mt-1" :message="form.errors.content" />
                    </div>

                    <!-- 快捷选择按钮和提交按钮组 -->
                    <div
                        class="flex flex-col sm:flex-row justify-between items-center flex-wrap gap-4 mt-6 pt-6 border-t dark:border-gray-700">
                        <div class="flex flex-wrap gap-2">
                            <!-- 采用“当前”版本内容的按钮 -->
                            <button type="button" @click="selectVersion('current')" class="btn-secondary text-sm">
                                <font-awesome-icon :icon="['fas', 'check']" class="mr-1.5 h-3 w-3" />
                                采用“当前”版本 (v{{ conflictVersions.current?.version_number || 'N/A' }})
                            </button>
                            <!-- 采用“冲突”版本内容的按钮 -->
                            <button type="button" @click="selectVersion('conflict')"
                                class="btn-secondary text-sm bg-red-100 text-red-700 hover:bg-red-200 dark:bg-red-900/40 dark:text-red-300 dark:hover:bg-red-900/60">
                                <font-awesome-icon :icon="['fas', 'check']" class="mr-1.5 h-3 w-3" />
                                采用“冲突”版本 (v{{ conflictVersions.conflict?.version_number || 'N/A' }})
                            </button>
                        </div>
                        <div class="flex space-x-3">
                            <!-- 取消按钮，返回页面详情 -->
                            <Link :href="route('wiki.show', page.slug)" class="btn-secondary">
                            取消
                            </Link>
                            <!-- 提交解决方案按钮，显示处理状态 -->
                            <button type="submit"
                                class="btn-primary bg-green-600 hover:bg-green-700 dark:bg-green-500 dark:hover:bg-green-600"
                                :disabled="form.processing">
                                <font-awesome-icon v-if="form.processing" :icon="['fas', 'spinner']" spin
                                    class="mr-1" />
                                {{ form.processing ? '正在提交...' : '提交解决方案' }}
                            </button>
                        </div>
                    </div>

                    <!-- 解决方案说明输入框 -->
                    <div class="mt-6">
                        <label for="resolution_comment"
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            解决说明 <span class="text-xs text-gray-500 dark:text-gray-400">(可选，说明如何解决的)</span>
                        </label>
                        <textarea id="resolution_comment" v-model="form.resolution_comment" rows="2"
                            class="textarea-field" placeholder="例如：保留了用户A的修改，合并了用户B的部分内容..."></textarea>
                        <!-- 解决说明错误提示 -->
                        <InputError class="mt-1" :message="form.errors.resolution_comment" />
                    </div>
                </form>

                <!-- 冲突版本内容的预览（不需修改） -->
                <div class="mt-12 pt-6 border-t dark:border-gray-700 grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div>
                        <!-- “当前”版本内容预览标题 -->
                        <h3 class="font-semibold mb-2 text-base text-gray-800 dark:text-gray-200">“当前”内容 (v{{
                            conflictVersions.current?.version_number }})</h3>
                        <!-- “当前”版本元数据（编辑者、时间、说明） -->
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">
                            编辑者: {{ conflictVersions.current?.creator?.name || '未知用户' }}
                            <br>时间: {{ formatDateTime(conflictVersions.current?.created_at) }}
                            <br>说明: {{ conflictVersions.current?.comment || '无' }}
                        </p>
                        <!-- “当前”版本内容渲染 -->
                        <div class="version-content prose dark:prose-invert max-w-none text-sm"
                            v-html="conflictVersions.current?.content || '<em>无内容</em>'"></div>
                    </div>
                    <div>
                        <!-- “冲突”版本内容预览标题 -->
                        <h3 class="font-semibold mb-2 text-base text-red-700 dark:text-red-400">“冲突”内容 (v{{
                            conflictVersions.conflict?.version_number }})</h3>
                        <!-- “冲突”版本元数据（编辑者、时间、说明） -->
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">
                            编辑者: {{ conflictVersions.conflict?.creator?.name || '未知用户' }}
                            <br>时间: {{ formatDateTime(conflictVersions.conflict?.created_at) }}
                            <br>说明: {{ conflictVersions.conflict?.comment || '无' }}
                        </p>
                        <!-- “冲突”版本内容渲染，带有红色边框和背景以示区别 -->
                        <div class="version-content prose dark:prose-invert max-w-none text-sm border-red-200 bg-red-50 dark:border-red-800/50 dark:bg-red-900/20"
                            v-html="conflictVersions.conflict?.content || '<em>无内容</em>'"></div>
                    </div>
                </div>
            </div>
        </div>
        <!-- 闪存消息组件 -->
        <FlashMessage />
    </MainLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link, Head, useForm, usePage } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayouts/MainLayout.vue';
import Editor from '@/Components/Wiki/Editor.vue'; // 导入自定义的编辑器组件
import InputError from '@/Components/Other/InputError.vue';
import FlashMessage from '@/Components/Other/FlashMessage.vue';
import { formatDateTime } from '@/utils/formatters';
import { mainNavigationLinks } from '@/config/navigationConfig';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

// 主要导航链接配置
const navigationLinks = mainNavigationLinks;
// 获取当前页面属性 (usePage().props)
const pageProps = usePage().props;

// 定义组件接收的属性
const props = defineProps({
    page: { type: Object, required: true }, // 当前页面对象
    conflictVersions: { type: Object, required: true }, // 包含“当前”和“冲突”版本内容的对象
    diffHtml: { type: String, required: true }, // 两个版本之间的HTML差异内容
});

// 用于获取 Editor 组件实例的引用
const resolutionEditor = ref(null);

// 表单数据，用于提交解决方案
const form = useForm({
    // 初始内容设置为当前版本的内容，作为合并的起点
    content: props.conflictVersions?.current?.content || '<p></p>',
    resolution_comment: '', // 解决方案的说明，默认为空
});

/**
 * 根据选择的版本类型（'current' 或 'conflict'）设置编辑器内容和解决说明。
 * @param {string} versionType - 'current' 或 'conflict'
 */
const selectVersion = (versionType) => {
    let newContent = '<p></p>'; // 默认内容
    let commentPrefix = ''; // 解决说明前缀

    // 根据选择的版本类型获取对应的内容和说明
    if (versionType === 'current' && props.conflictVersions?.current) {
        newContent = props.conflictVersions.current.content || newContent;
        commentPrefix = `采用“当前”版本 (v${props.conflictVersions.current.version_number || 'N/A'}) 的内容。`;
    } else if (versionType === 'conflict' && props.conflictVersions?.conflict) {
        newContent = props.conflictVersions.conflict.content || newContent;
        commentPrefix = `采用“冲突”版本 (v${props.conflictVersions.conflict.version_number || 'N/A'}) 的内容。`;
    }

    // 更新表单内容和解决说明
    form.content = newContent;
    form.resolution_comment = commentPrefix;

    // 如果编辑器实例存在，则通过其命令更新编辑器内容
    if (resolutionEditor.value && resolutionEditor.value.editor) {
        // 使用 setContent 更新编辑器，并设置第二个参数为 false，以避免触发 onUpdate 事件
        resolutionEditor.value.editor.commands.setContent(newContent, false);
    }
};

/**
 * 提交最终解决冲突的内容。
 */
const submitResolution = () => {
    // 确保编辑器中的最新内容同步到表单模型
    if (resolutionEditor.value && resolutionEditor.value.editor) {
        form.content = resolutionEditor.value.editor.getHTML();
    }

    // 如果用户未提供解决说明，则设置一个默认说明
    if (!form.resolution_comment.trim()) {
        form.resolution_comment = '解决编辑冲突（未提供说明）';
    }

    // 发送 POST 请求到后端解决冲突的路由
    form.post(route('wiki.resolve-conflict', props.page.slug), {
        preserveScroll: true, // 提交后保持页面滚动位置
        onError: (errors) => {
            // 处理提交失败时的错误
            console.error("Conflict resolution failed:", errors);
            const firstError = Object.values(errors).flat()[0]; // 获取第一个错误信息
            // 使用 alert 弹窗提示错误，或者可以集成到 FlashMessage 组件中
            alert(firstError || '提交解决方案时出错，请检查内容或稍后再试。');
        },
        // onSuccess 通常会由控制器重定向来处理
    });
};

</script>

<style scoped>
/* 主要按钮样式 */
.btn-primary {
    @apply inline-flex items-center justify-center px-5 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 transition;
}

/* 次要按钮样式 */
.btn-secondary {
    @apply inline-flex items-center justify-center px-5 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 transition;
}

/* 文本区域输入框样式 */
.textarea-field {
    @apply w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 dark:placeholder-gray-400 text-sm;
}

/* 差异容器样式 */
.diff-container {
    background-color: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    overflow: auto;
    @apply dark:bg-gray-800 dark:border-gray-700;
}

/* 差异表格的深度选择器样式 */
:deep(.diff-container table.diff) {
    @apply text-xs leading-snug dark:text-gray-300;
}

:deep(.diff-container td) {
    @apply px-2 py-0.5 border-gray-200 dark:border-gray-600;
}

:deep(.diff-container th) {
    @apply px-2 py-1 bg-gray-100 dark:bg-gray-700 border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-400 sticky top-0;
}

:deep(.diff-container td.lines-no) {
    @apply pr-2 bg-gray-100 dark:bg-gray-700 border-r border-gray-200 dark:border-gray-600 text-gray-400 dark:text-gray-500 sticky left-0;
    width: 40px !important;
}

/* 差异中删除部分的样式 */
:deep(.diff-container .ChangeDelete .Left) {
    @apply bg-red-100 dark:bg-red-900/30;
}

:deep(.diff-container .ChangeDelete .Left del) {
    @apply bg-red-200 dark:bg-red-800/40 text-red-700 dark:text-red-300;
}

/* 差异中插入部分的样式 */
:deep(.diff-container .ChangeInsert .Right) {
    @apply bg-green-100 dark:bg-green-900/30;
}

:deep(.diff-container .ChangeInsert .Right ins) {
    @apply bg-green-200 dark:bg-green-800/40 text-green-800 dark:text-green-300;
    text-decoration: none;
}

/* 差异中替换部分的样式 */
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

/* 版本内容预览区域的通用样式 */
.version-content {
    @apply border p-4 rounded-lg max-h-[40vh] overflow-y-auto prose max-w-none text-sm leading-relaxed dark:border-gray-700 dark:prose-invert bg-gray-50 dark:bg-gray-800/30;
    scrollbar-width: thin;
    scrollbar-color: #cbd5e1 #f1f5f9;
    @apply dark:bg-gray-800/30;
}

/* 红色边框和背景的特殊版本内容样式 */
.version-content.border-red-200 {
    border-color: #fecaca;
    @apply dark:border-red-800/50;
}

.version-content.bg-red-50 {
    background-color: #fef2f2;
    @apply dark:bg-red-900/20;
}

/* 滚动条样式 */
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

/* 错误提示框样式 */
.alert-error {
    @apply mb-4 p-4 bg-red-100 text-red-700 rounded-md border border-red-200 dark:bg-red-900/40 dark:border-red-800 dark:text-red-300;
}

/* Tiptap 编辑器组件的深度选择器样式 */
:deep(.tiptap-editor) {
    border: 1px solid #4b5563;
    /* 假定深色模式下的边框颜色 */
    border-radius: 0.5rem;
    min-height: 300px;
    /* 根据需要调整最小高度 */
    display: flex;
    flex-direction: column;
}

/* 编辑器内容区域的深度选择器样式 */
:deep(.editor-content) {
    flex-grow: 1;
    overflow-y: auto;
}
</style>