<template>
    <MainLayout :navigationLinks="navigationLinks">

        <Head :title="`比较版本: v${fromVersion.version_number} vs v${toVersion.version_number} - ${page.title}`" />
        <div class="container mx-auto py-6 px-4">
            <div class="bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm rounded-lg shadow-lg p-6">
                <!-- 标题和导航 -->
                <div
                    class="flex flex-col md:flex-row justify-between md:items-center mb-6 border-b pb-4 dark:border-gray-700">
                    <h1 class="text-2xl md:text-3xl font-bold text-gray-800 dark:text-gray-100 mb-2 md:mb-0">
                        {{ page.title }} - 版本比较
                    </h1>
                    <div class="flex items-center space-x-3 text-sm">
                        <Link :href="route('wiki.history', page.slug)"
                            class="text-blue-600 dark:text-blue-400 hover:underline">
                        <font-awesome-icon :icon="['fas', 'arrow-left']" class="mr-1" /> 返回历史版本
                        </Link>
                        <Link :href="route('wiki.show', page.slug)"
                            class="text-blue-600 dark:text-blue-400 hover:underline">
                        <font-awesome-icon :icon="['fas', 'eye']" class="mr-1" /> 返回页面
                        </Link>
                    </div>
                </div>

                <!-- 版本信息卡片 -->
                <div class="mb-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div
                        class="p-4 bg-red-50 dark:bg-red-900/30 rounded-lg border border-red-200 dark:border-red-700/50">
                        <h3 class="font-semibold mb-2 text-red-700 dark:text-red-300">旧版本 (v{{
                            fromVersion.version_number }})</h3>
                        <div class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                            <p><font-awesome-icon :icon="['fas', 'user']" class="mr-1.5 w-3" /> 编辑者: {{
                                fromCreator?.name || '未知用户' }}</p>
                            <p><font-awesome-icon :icon="['fas', 'clock']" class="mr-1.5 w-3" /> 时间: {{
                                formatDateTime(fromVersion.created_at) }}</p>
                            <p><font-awesome-icon :icon="['fas', 'comment']" class="mr-1.5 w-3" /> 说明: {{
                                fromVersion.comment || '无说明' }}</p>
                        </div>
                    </div>
                    <div
                        class="p-4 bg-green-50 dark:bg-green-900/30 rounded-lg border border-green-200 dark:border-green-700/50">
                        <h3 class="font-semibold mb-2 text-green-700 dark:text-green-300">新版本 (v{{
                            toVersion.version_number }})</h3>
                        <div class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                            <p><font-awesome-icon :icon="['fas', 'user']" class="mr-1.5 w-3" /> 编辑者: {{ toCreator?.name
                                || '未知用户' }}</p>
                            <p><font-awesome-icon :icon="['fas', 'clock']" class="mr-1.5 w-3" /> 时间: {{
                                formatDateTime(toVersion.created_at) }}</p>
                            <p><font-awesome-icon :icon="['fas', 'comment']" class="mr-1.5 w-3" /> 说明: {{
                                toVersion.comment || '无说明' }}</p>
                        </div>
                    </div>
                </div>

                <!-- 内容差异区域 -->
                <div class="mb-6">
                    <h2 class="text-xl font-bold mb-4 text-gray-800 dark:text-gray-200">内容差异</h2>
                    <!-- 添加 specific-diff-styling 类 -->
                    <div
                        class="border rounded-lg overflow-x-auto bg-white dark:bg-gray-800 shadow-inner dark:border-gray-700">
                        <div v-if="diffHtml" v-html="diffHtml"
                            class="diff-container specific-diff-styling leading-relaxed"></div>
                        <div v-else class="p-4 text-gray-500 dark:text-gray-400 italic">无法加载差异视图或无差异。</div>
                    </div>
                </div>

                <!-- 恢复版本按钮 -->
                <div v-if="canRevert" class="mt-4 text-right">
                    <button @click="confirmRevert"
                        class="inline-flex items-center px-4 py-2 bg-yellow-500 text-white rounded-md hover:bg-yellow-600 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2 transition text-sm font-medium dark:bg-yellow-600 dark:hover:bg-yellow-700">
                        <font-awesome-icon :icon="['fas', 'undo']" class="mr-1.5 h-4 w-4" />
                        恢复到此版本 (v{{ toVersion.version_number }})
                    </button>
                </div>
            </div>
        </div>

        <!-- 恢复确认模态框 -->
        <Modal :show="showRevertModal" @close="closeRevertModal" maxWidth="md">
            <div class="p-6 bg-white dark:bg-gray-800 rounded-lg">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                    <font-awesome-icon :icon="['fas', 'exclamation-triangle']" class="text-yellow-500 mr-2" />
                    确认恢复版本
                </h3>
                <p class="mb-6 text-sm text-gray-600 dark:text-gray-300">
                    您确定要将页面恢复到 <strong class="font-semibold">版本 v{{ toVersion?.version_number || '' }}</strong> 吗？
                    <br>
                    此操作将在当前版本之后创建一个包含所选版本内容的新版本，并保留所有历史记录。当前版本将不再是最新版本。
                </p>
                <div class="flex justify-end space-x-3">
                    <button @click="closeRevertModal"
                        class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2 text-sm font-medium dark:bg-gray-600 dark:text-gray-200 dark:hover:bg-gray-500">
                        取消
                    </button>
                    <!-- 添加 disabled 和处理中状态 -->
                    <button @click="revertToVersion"
                        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 text-sm font-medium disabled:opacity-50 dark:hover:bg-blue-700"
                        :disabled="isReverting">
                        <font-awesome-icon v-if="isReverting" :icon="['fas', 'spinner']" spin class="mr-1" />
                        {{ isReverting ? '恢复中...' : '确认恢复' }}
                    </button>
                </div>
            </div>
        </Modal>

        <FlashMessage />
    </MainLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { Link, Head, router, usePage } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayouts/MainLayout.vue';
import Modal from '@/Components/Modal/Modal.vue';
import FlashMessage from '@/Components/Other/FlashMessage.vue'; // 引入 FlashMessage
import { formatDateTime } from '@/utils/formatters';
import { mainNavigationLinks } from '@/config/navigationConfig';

const navigationLinks = mainNavigationLinks;
const pageProps = usePage().props;

const props = defineProps({
    page: { type: Object, required: true },
    fromVersion: { type: Object, required: true },
    toVersion: { type: Object, required: true },
    fromCreator: { type: Object, required: true },
    toCreator: { type: Object, required: true },
    diffHtml: { type: String, default: '<p>无差异信息</p>' }
});

const showRevertModal = ref(false);
const isReverting = ref(false); // 添加处理状态

const canRevert = computed(() => {
    // 检查用户是否登录以及是否有 'wiki.edit' 权限
    return pageProps.auth?.user?.permissions?.includes('wiki.edit') || false;
});

const confirmRevert = () => {
    showRevertModal.value = true;
};

const closeRevertModal = () => {
    showRevertModal.value = false;
};

const revertToVersion = () => {
    isReverting.value = true; // 开始处理
    router.post(route('wiki.revert-version', {
        page: props.page.slug,
        version: props.toVersion.version_number // 恢复到较新的版本
    }), {}, {
        preserveScroll: true, // 保留滚动位置
        onSuccess: () => {
            closeRevertModal();
            // Flash 消息应由后端或 Inertia 事件处理
        },
        onError: (errors) => {
            console.error('Revert failed:', errors);
            // 可以在这里用 FlashMessage 显示错误
            // flashMessage.value?.addMessage('error', '恢复版本失败，请重试。');
        },
        onFinish: () => {
            isReverting.value = false; // 结束处理
        }
    });
};

</script>

<style scoped>
/* 针对 jfcherng/php-diff 的优化样式 */
.specific-diff-styling .diff {
    @apply w-full border-collapse text-xs font-mono;
    table-layout: fixed;
    /* 固定布局，避免内容撑开 */
}

.specific-diff-styling .diff th {
    @apply p-1 px-2 text-left bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 font-semibold sticky top-0 z-10;
    /* 表头粘性定位 */
    width: 50%;
    /* 每列占一半 */
}

.specific-diff-styling .diff td {
    @apply p-1 px-2 border border-gray-200 dark:border-gray-600 align-top break-words;
    /* 允许长单词换行 */
    vertical-align: top;
    white-space: pre-wrap;
    /* 保留空白符和换行 */
    word-wrap: break-word;
    word-break: break-all;
    /* 强制断词 */
    line-height: 1.6;
    /* 调整行高 */
}

/* 左右内容区的边框 */
.specific-diff-styling .diff td.Left {
    @apply border-r-2 border-r-gray-300 dark:border-r-gray-500;
    /* 中间分割线加粗 */
}

.specific-diff-styling .diff td.Right {
    border-left: none;
    /* 避免双边框 */
}


/* 行号样式 */
.specific-diff-styling .diff td.lines-no {
    @apply text-right pr-2 text-gray-400 dark:text-gray-500 bg-gray-50 dark:bg-gray-700 select-none sticky left-0;
    /* 行号也粘性定位 */
    width: 45px !important;
    /* 固定行号宽度 */
    min-width: 45px !important;
    z-index: 5;
    /* 比表头低一点 */
}

/* 左侧列的行号需要右边框 */
.specific-diff-styling .diff tr td.lines-no:first-of-type {
    border-right: 1px solid #e5e7eb;
    /* Light border for left line numbers */
    border-color: var(--tw-border-opacity) rgba(75, 85, 99, var(--tw-border-opacity));
}


/* 空白行 */
.specific-diff-styling .diff .Skipped {
    @apply bg-gray-100 dark:bg-gray-700/50;
}

.specific-diff-styling .diff .Skipped td {
    @apply p-0 border-gray-200 dark:border-gray-700;
    line-height: 0.5rem;
    /* 减少空白行高度 */
    height: 0.5rem;
}

.specific-diff-styling .diff .Skipped td span {
    @apply text-gray-400 dark:text-gray-500 text-xs;
    display: block;
    text-align: center;
    line-height: 0.5rem;
}

/* 删除的内容 - 左侧 */
.specific-diff-styling .diff td.Left .ChangeReplace,
.specific-diff-styling .diff td.Left .ChangeDelete {
    @apply bg-red-100/60 dark:bg-red-900/40;
}

.specific-diff-styling .diff td.Left .ChangeReplace del,
.specific-diff-styling .diff td.Left .ChangeDelete del {
    @apply bg-red-200/70 dark:bg-red-700/50 text-red-800 dark:text-red-200 px-0.5 rounded-sm;
    text-decoration: line-through;
}

/* 添加的内容 - 右侧 */
.specific-diff-styling .diff td.Right .ChangeReplace,
.specific-diff-styling .diff td.Right .ChangeInsert {
    @apply bg-green-100/60 dark:bg-green-900/40;
}

.specific-diff-styling .diff td.Right .ChangeReplace ins,
.specific-diff-styling .diff td.Right .ChangeInsert ins {
    @apply bg-green-200/70 dark:bg-green-700/50 text-green-800 dark:text-green-200 px-0.5 rounded-sm;
    text-decoration: none;
    /* ins 默认可能有下划线，去掉 */
}

/* 如果库没有使用 del/ins，可能需要 target 具体的类 (备用) */
.specific-diff-styling .diff .ChangeDelete {
    @apply bg-red-100/60 dark:bg-red-900/40;
}

.specific-diff-styling .diff .ChangeInsert {
    @apply bg-green-100/60 dark:bg-green-900/40;
}

/* 移除 ProseMirror 的样式干扰 */
.specific-diff-styling .prose {
    all: unset;
}

/* 防止 prose 的 margin/padding 干扰 */
.specific-diff-styling td>*,
.specific-diff-styling td del>*,
.specific-diff-styling td ins>* {
    margin: 0 !important;
    padding: 0 !important;
    display: inline;
    /* 确保内部元素不会破坏 pre-wrap */
}

/* 暗黑模式下的滚动条样式 (可选，需要浏览器支持) */
.dark .specific-diff-styling ::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

.dark .specific-diff-styling ::-webkit-scrollbar-track {
    background: #2d3748;
    /* gray-800 */
    border-radius: 10px;
}

.dark .specific-diff-styling ::-webkit-scrollbar-thumb {
    background: #4a5568;
    /* gray-600 */
    border-radius: 10px;
}

.dark .specific-diff-styling ::-webkit-scrollbar-thumb:hover {
    background: #718096;
    /* gray-500 */
}
</style>