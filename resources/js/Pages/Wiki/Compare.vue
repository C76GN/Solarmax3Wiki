<script setup>
import { ref, computed } from 'vue';
import { Link, Head, router, usePage } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayouts/MainLayout.vue';
import Modal from '@/Components/Modal/Modal.vue';
import FlashMessage from '@/Components/Other/FlashMessage.vue';
import { formatDateTime } from '@/utils/formatters'; // 导入日期时间格式化工具
import { mainNavigationLinks } from '@/config/navigationConfig'; // 导入主导航链接配置

// 设置主导航链接
const navigationLinks = mainNavigationLinks;
// 获取 Inertia 页面属性
const pageProps = usePage().props;
// 引用 FlashMessage 组件实例，用于显示消息
const flashMessage = ref(null);

// 定义组件接收的属性
const props = defineProps({
    page: { type: Object, required: true }, // 当前Wiki页面对象
    fromVersion: { type: Object, required: true }, // 对比起始版本对象
    toVersion: { type: Object, required: true }, // 对比目标版本对象
    fromCreator: { type: Object, required: true }, // 起始版本的创建者
    toCreator: { type: Object, required: true }, // 目标版本的创建者
    diffHtml: { type: String, default: '<p>无差异信息</p>' } // HTML格式的代码差异视图内容
});

// 控制恢复版本确认模态框的显示状态
const showRevertModal = ref(false);
// 标记是否正在执行恢复操作
const isReverting = ref(false);
// 存储待恢复的版本对象
const versionToRevert = ref(null);

// 计算属性：检查当前用户是否有恢复Wiki页面的权限
const canRevert = computed(() => {
    // 恢复版本本质上是创建新版本，所以检查用户是否有 'wiki.edit' 权限
    return pageProps.auth?.user?.permissions?.includes('wiki.edit') || false;
});

/**
 * 确认恢复版本操作
 * @param {Object} version - 待恢复的版本对象
 */
const confirmRevert = (version) => {
    versionToRevert.value = version;
    showRevertModal.value = true;
};

// 关闭恢复版本确认模态框
const closeRevertModal = () => {
    showRevertModal.value = false;
    versionToRevert.value = null;
};

/**
 * 执行恢复版本操作
 */
const revertToVersion = () => {
    if (!versionToRevert.value) return; // 如果没有指定版本，则退出
    isReverting.value = true; // 设置恢复中状态

    // 发送 POST 请求到后端恢复指定版本
    router.post(route('wiki.revert-version', {
        page: props.page.slug, // 页面 slug
        version: versionToRevert.value.version_number // 待恢复的版本号
    }), {}, {
        preserveScroll: true, // 保持滚动位置
        onSuccess: (pageResponse) => {
            closeRevertModal(); // 关闭模态框
            // 显示成功消息
            flashMessage.value?.addMessage('success', `页面已成功恢复到 v${versionToRevert.value.version_number}`);
        },
        onError: (errors) => {
            console.error('恢复失败:', errors);
            // 获取并显示错误消息
            const errorMsg = Object.values(errors).flat()[0] || '恢复版本失败，请重试。';
            flashMessage.value?.addMessage('error', errorMsg);
        },
        onFinish: () => {
            isReverting.value = false; // 恢复操作完成
        }
    });
};
</script>

<template>
    <!-- 主要布局容器，引入导航链接 -->
    <MainLayout :navigationLinks="navigationLinks">

        <!-- 设置页面标题，显示正在比较的版本信息 -->

        <Head :title="`比较版本: v${fromVersion.version_number} vs v${toVersion.version_number} - ${page.title}`" />

        <div class="container mx-auto py-6 px-4">
            <div class="bg-gray-800/90 text-gray-200 backdrop-blur-sm rounded-lg shadow-lg p-6">

                <!-- 页面和版本信息标题区域 -->
                <div
                    class="flex flex-col md:flex-row justify-between md:items-center mb-6 border-b pb-4 border-gray-700">
                    <h1 class="text-2xl md:text-3xl font-bold text-gray-100 mb-2 md:mb-0 break-words">
                        {{ page.title }} - 版本比较
                    </h1>
                    <div class="flex items-center space-x-3 text-sm">
                        <!-- 返回历史版本列表的链接 -->
                        <Link :href="route('wiki.history', page.slug)"
                            class="text-blue-400 hover:underline flex items-center">
                        <font-awesome-icon :icon="['fas', 'arrow-left']" class="mr-1 h-3 w-3" /> 返回历史版本
                        </Link>
                        <!-- 返回当前页面视图的链接 -->
                        <Link :href="route('wiki.show', page.slug)"
                            class="text-blue-400 hover:underline flex items-center">
                        <font-awesome-icon :icon="['fas', 'eye']" class="mr-1 h-3 w-3" /> 返回页面
                        </Link>
                    </div>
                </div>

                <!-- 版本元数据对比显示区域 -->
                <div class="mb-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- 旧版本信息卡片 -->
                    <div class="p-4 bg-red-900/40 rounded-lg border border-red-700/60 shadow-sm">
                        <h3 class="font-semibold mb-2 text-red-300 flex items-center">
                            <font-awesome-icon :icon="['fas', 'history']" class="mr-2 text-red-400" />
                            旧版本 (v{{ fromVersion.version_number }})
                        </h3>
                        <div class="text-sm text-gray-400 space-y-1">
                            <p><font-awesome-icon :icon="['fas', 'user']" class="mr-1.5 w-3" /> 编辑者: {{
                                fromCreator?.name || '未知用户' }}</p>
                            <p><font-awesome-icon :icon="['fas', 'clock']" class="mr-1.5 w-3" /> 时间: {{
                                formatDateTime(fromVersion.created_at) }}</p>
                            <p><font-awesome-icon :icon="['fas', 'comment']" class="mr-1.5 w-3" /> 说明: {{
                                fromVersion.comment || '无说明' }}</p>
                        </div>
                        <div class="mt-4 text-right" v-if="canRevert">
                            <button @click="confirmRevert(fromVersion)"
                                class="inline-flex items-center px-3 py-1 bg-yellow-600 text-white rounded-md hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2 focus:ring-offset-gray-800 transition text-xs font-medium">
                                <font-awesome-icon :icon="['fas', 'undo']" class="mr-1.5 h-3 w-3" />
                                恢复到此版本
                            </button>
                        </div>
                    </div>
                    <!-- 新版本信息卡片 -->
                    <div class="p-4 bg-green-900/40 rounded-lg border border-green-700/60 shadow-sm">
                        <h3 class="font-semibold mb-2 text-green-300 flex items-center">
                            <font-awesome-icon :icon="['fas', 'history']" class="mr-2 text-green-400" />
                            新版本 (v{{ toVersion.version_number }})
                        </h3>
                        <div class="text-sm text-gray-400 space-y-1">
                            <p><font-awesome-icon :icon="['fas', 'user']" class="mr-1.5 w-3" /> 编辑者: {{ toCreator?.name
                                || '未知用户' }}</p>
                            <p><font-awesome-icon :icon="['fas', 'clock']" class="mr-1.5 w-3" /> 时间: {{
                                formatDateTime(toVersion.created_at) }}</p>
                            <p><font-awesome-icon :icon="['fas', 'comment']" class="mr-1.5 w-3" /> 说明: {{
                                toVersion.comment || '无说明' }}</p>
                        </div>
                        <div class="mt-4 text-right" v-if="canRevert">
                            <button @click="confirmRevert(toVersion)"
                                class="inline-flex items-center px-3 py-1 bg-yellow-600 text-white rounded-md hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2 focus:ring-offset-gray-800 transition text-xs font-medium">
                                <font-awesome-icon :icon="['fas', 'undo']" class="mr-1.5 h-3 w-3" />
                                恢复到此版本
                            </button>
                        </div>
                    </div>
                </div>

                <!-- HTML 源码差异对比区域 -->
                <div class="mb-8">
                    <h2 class="text-xl font-bold mb-4 text-gray-200">源码差异对比</h2>
                    <div class="diff-container specific-diff-styling">
                        <div v-if="diffHtml" v-html="diffHtml" class="leading-relaxed"></div>
                        <div v-else class="p-4 text-gray-400 italic">无法加载差异视图或无差异。</div>
                    </div>
                </div>

                <!-- 页面内容渲染效果预览区域 -->
                <div class="mt-8">
                    <h2 class="text-xl font-bold mb-4 text-gray-200">效果预览</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- 旧版本内容预览 -->
                        <div>
                            <h3 class="text-lg font-semibold mb-3 text-red-300">旧版本 (v{{ fromVersion.version_number }})
                                预览</h3>
                            <div class="preview-pane border border-red-700/60 bg-gray-800/70 prose dark:prose-invert"
                                v-html="fromVersion.content || '<p><em>无内容</em></p>'">
                            </div>
                        </div>
                        <!-- 新版本内容预览 -->
                        <div>
                            <h3 class="text-lg font-semibold mb-3 text-green-300">新版本 (v{{ toVersion.version_number }})
                                预览</h3>
                            <div class="preview-pane border border-green-700/60 bg-gray-800/70 prose dark:prose-invert"
                                v-html="toVersion.content || '<p><em>无内容</em></p>'">
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- 恢复版本确认模态框 -->
        <Modal :show="showRevertModal" @close="closeRevertModal" maxWidth="md">
            <div class="p-6 bg-gray-800 rounded-lg">
                <h3 class="text-lg font-medium text-gray-100 mb-4 flex items-center">
                    <font-awesome-icon :icon="['fas', 'exclamation-triangle']" class="text-yellow-500 mr-2" />
                    确认恢复版本
                </h3>
                <p class="mb-6 text-sm text-gray-300">
                    您确定要将页面恢复到 <strong class="font-semibold text-gray-100">版本 v{{ versionToRevert?.version_number || ''
                        }}</strong> 吗？
                    <br>
                    此操作将在当前版本之后创建一个包含所选版本内容的新版本，并保留所有历史记录。当前版本将不再是最新版本。
                </p>
                <div class="flex justify-end space-x-3">
                    <button @click="closeRevertModal"
                        class="px-4 py-2 bg-gray-600 text-gray-200 rounded-md hover:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2 focus:ring-offset-gray-800 text-sm font-medium">
                        取消
                    </button>
                    <button @click="revertToVersion"
                        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:ring-offset-gray-800 text-sm font-medium disabled:opacity-50"
                        :disabled="isReverting">
                        <font-awesome-icon v-if="isReverting" :icon="['fas', 'spinner']" spin class="mr-1" />
                        {{ isReverting ? '恢复中...' : '确认恢复' }}
                    </button>
                </div>
            </div>
        </Modal>

        <!-- 消息提示组件 -->
        <FlashMessage ref="flashMessage" />

    </MainLayout>
</template>

<style scoped>
/* 差异视图容器样式 */
.diff-container {
    max-height: 60vh;
    /* 源码对比区域的最大高度 */
    overflow-y: auto;
    /* 允许垂直滚动 */
    background-color: #111827;
    /* 深色背景 */
    border: 1px solid #4b5563;
    /* 边框颜色 */
    border-radius: 0.5rem;
    /* 圆角 */
}

/* 针对 jfcherng/php-diff 库生成的表格样式微调 */
:deep(.diff-container table.diff) {
    font-size: 0.75rem;
    /* 调小字体 */
    line-height: 1.4;
    /* 调整行高 */
    color: #d1d5db;
    /* 文本颜色 */
    background-color: #111827;
    /* 背景色 */
}

:deep(.diff-container td) {
    border-color: #374151 !important;
    /* 更深的边框色 */
    color: #d1d5db;
    /* 文本颜色 */
    padding-top: 0.1rem !important;
    /* 减少上下内边距 */
    padding-bottom: 0.1rem !important;
}

:deep(.diff-container th) {
    background-color: #1f2937 !important;
    /* 稍亮的表头背景 */
    border-color: #4b5563 !important;
    /* 表头边框颜色 */
    color: #f3f4f6 !important;
    /* 表头文本颜色 */
    padding: 0.25rem 0.5rem !important;
    /* 调整表头内边距 */
}

:deep(.diff-container td.lines-no) {
    width: 30px !important;
    /* 缩小行号列宽 */
    min-width: 30px !important;
    /* 最小宽度 */
    padding-right: 0.5rem;
    /* 右内边距 */
    background-color: #1f2937 !important;
    /* 行号列背景色 */
    color: #6b7280 !important;
    /* 行号文本颜色 */
    border-right-color: #4b5563 !important;
    /* 行号列右侧边框颜色 */
}

/* 删除行的背景和文本颜色 */
:deep(.diff-container .ChangeDelete .Left) {
    background-color: rgba(127, 29, 29, 0.3) !important;
}

:deep(.diff-container .ChangeDelete .Left del) {
    background-color: rgba(185, 28, 28, 0.4) !important;
    color: #fca5a5 !important;
}

/* 插入行的背景和文本颜色 */
:deep(.diff-container .ChangeInsert .Right) {
    background-color: rgba(6, 78, 59, 0.3) !important;
}

:deep(.diff-container .ChangeInsert .Right ins) {
    background-color: rgba(4, 120, 87, 0.4) !important;
    color: #a7f3d0 !important;
}

/* 替换行的样式（左右两侧背景不同，文本颜色区分） */
:deep(.diff-container .ChangeReplace .Left) {
    background-color: rgba(127, 29, 29, 0.3) !important;
}

:deep(.diff-container .ChangeReplace .Right) {
    background-color: rgba(6, 78, 59, 0.3) !important;
}

:deep(.diff-container td.Left .ChangeReplace del) {
    background-color: rgba(185, 28, 28, 0.4) !important;
    color: #fecaca !important;
}

:deep(.diff-container td.Right .ChangeReplace ins) {
    background-color: rgba(4, 120, 87, 0.4) !important;
    color: #a7f3d0 !important;
}

/* 预览窗格样式 */
.preview-pane {
    @apply p-4 rounded-lg max-h-[60vh] overflow-y-auto;
    /* 预览区最大高度和滚动 */
}

/* 确保 prose 样式在预览窗格内生效，并调整标题和段落间距 */
.preview-pane :deep(h1),
.preview-pane :deep(h2),
.preview-pane :deep(h3),
.preview-pane :deep(h4),
.preview-pane :deep(h5),
.preview-pane :deep(h6) {
    @apply mt-4 mb-2;
}

.preview-pane :deep(p) {
    @apply mb-3 leading-relaxed;
}
</style>