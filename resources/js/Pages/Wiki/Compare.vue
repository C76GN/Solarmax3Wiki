<script setup>
import { ref, computed } from 'vue';
import { Link, Head, router, usePage } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayouts/MainLayout.vue';
import Modal from '@/Components/Modal/Modal.vue';
import FlashMessage from '@/Components/Other/FlashMessage.vue';
import { formatDateTime } from '@/utils/formatters';
import { mainNavigationLinks } from '@/config/navigationConfig';

const navigationLinks = mainNavigationLinks;
const pageProps = usePage().props;
const flashMessage = ref(null);

const props = defineProps({
    page: { type: Object, required: true },
    fromVersion: { type: Object, required: true },
    toVersion: { type: Object, required: true },
    fromCreator: { type: Object, required: true },
    toCreator: { type: Object, required: true },
    diffHtml: { type: String, default: '<p>无差异信息</p>' } // HTML for the code diff view
});

const showRevertModal = ref(false);
const isReverting = ref(false);
const versionToRevert = ref(null);

const canRevert = computed(() => {
    // 检查用户是否有 'wiki.edit' 权限，因为恢复版本本质上是创建新版本
    return pageProps.auth?.user?.permissions?.includes('wiki.edit') || false;
});

const confirmRevert = (version) => {
    versionToRevert.value = version;
    showRevertModal.value = true;
};

const closeRevertModal = () => {
    showRevertModal.value = false;
    versionToRevert.value = null;
};

const revertToVersion = () => {
    if (!versionToRevert.value) return;
    isReverting.value = true;

    router.post(route('wiki.revert-version', {
        page: props.page.slug,
        version: versionToRevert.value.version_number
    }), {}, {
        preserveScroll: true,
        onSuccess: (pageResponse) => {
            closeRevertModal();
            // 手动设置 flash 消息，因为 onSuccess 可能不会自动更新 props.flash
            // router.page.props.flash = { message: { type: 'success', text: `页面已成功恢复到 v${versionToRevert.value.version_number}` } };
            // 改为调用 flashMessage 组件的方法
            flashMessage.value?.addMessage('success', `页面已成功恢复到 v${versionToRevert.value.version_number}`);
        },
        onError: (errors) => {
            console.error('Revert failed:', errors);
            const errorMsg = Object.values(errors).flat()[0] || '恢复版本失败，请重试。';
            // router.page.props.flash = { message: { type: 'error', text: errorMsg } };
            flashMessage.value?.addMessage('error', errorMsg);
        },
        onFinish: () => {
            isReverting.value = false;
        }
    });
};
</script>

<template>
    <MainLayout :navigationLinks="navigationLinks">

        <Head :title="`比较版本: v${fromVersion.version_number} vs v${toVersion.version_number} - ${page.title}`" />

        <div class="container mx-auto py-6 px-4">
            <div class="bg-gray-800/90 text-gray-200 backdrop-blur-sm rounded-lg shadow-lg p-6">

                <!-- 页面和版本信息标题 -->
                <div
                    class="flex flex-col md:flex-row justify-between md:items-center mb-6 border-b pb-4 border-gray-700">
                    <h1 class="text-2xl md:text-3xl font-bold text-gray-100 mb-2 md:mb-0 break-words">
                        {{ page.title }} - 版本比较
                    </h1>
                    <div class="flex items-center space-x-3 text-sm">
                        <Link :href="route('wiki.history', page.slug)"
                            class="text-blue-400 hover:underline flex items-center">
                        <font-awesome-icon :icon="['fas', 'arrow-left']" class="mr-1 h-3 w-3" /> 返回历史版本
                        </Link>
                        <Link :href="route('wiki.show', page.slug)"
                            class="text-blue-400 hover:underline flex items-center">
                        <font-awesome-icon :icon="['fas', 'eye']" class="mr-1 h-3 w-3" /> 返回页面
                        </Link>
                    </div>
                </div>

                <!-- 版本元数据 -->
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

                <!-- HTML 源码差异对比 -->
                <div class="mb-8">
                    <h2 class="text-xl font-bold mb-4 text-gray-200">源码差异对比</h2>
                    <div class="diff-container specific-diff-styling">
                        <div v-if="diffHtml" v-html="diffHtml" class="leading-relaxed"></div>
                        <div v-else class="p-4 text-gray-400 italic">无法加载差异视图或无差异。</div>
                    </div>
                </div>

                <!-- 新增：渲染效果预览 -->
                <div class="mt-8">
                    <h2 class="text-xl font-bold mb-4 text-gray-200">效果预览</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- 旧版本预览 -->
                        <div>
                            <h3 class="text-lg font-semibold mb-3 text-red-300">旧版本 (v{{ fromVersion.version_number }})
                                预览</h3>
                            <div class="preview-pane border border-red-700/60 bg-gray-800/70 prose dark:prose-invert"
                                v-html="fromVersion.content || '<p><em>无内容</em></p>'">
                            </div>
                        </div>
                        <!-- 新版本预览 -->
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

        <!-- 恢复版本确认 Modal -->
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

        <FlashMessage ref="flashMessage" />

    </MainLayout>
</template>

<style scoped>
.diff-container {
    max-height: 60vh;
    /* 调整源码对比区域的最大高度 */
    overflow-y: auto;
    background-color: #111827;
    /* 深色背景 */
    border: 1px solid #4b5563;
    /* 边框颜色 */
    border-radius: 0.5rem;
}

/* jfcherng/php-diff 样式微调（如果需要） */
:deep(.diff-container table.diff) {
    font-size: 0.75rem;
    /* 调小字体 */
    line-height: 1.4;
    /* 调整行高 */
    color: #d1d5db;
    background-color: #111827;
}

:deep(.diff-container td) {
    border-color: #374151 !important;
    /* 更深的边框色 */
    color: #d1d5db;
    padding-top: 0.1rem !important;
    /* 减少上下内边距 */
    padding-bottom: 0.1rem !important;
}

:deep(.diff-container th) {
    background-color: #1f2937 !important;
    /* 稍亮的表头背景 */
    border-color: #4b5563 !important;
    color: #f3f4f6 !important;
    padding: 0.25rem 0.5rem !important;
    /* 调整表头内边距 */
}

:deep(.diff-container td.lines-no) {
    width: 30px !important;
    /* 缩小行号列宽 */
    min-width: 30px !important;
    padding-right: 0.5rem;
    background-color: #1f2937 !important;
    color: #6b7280 !important;
    border-right-color: #4b5563 !important;
}

/* 删除和插入行的背景和文本颜色保持明显 */
:deep(.diff-container .ChangeDelete .Left) {
    background-color: rgba(127, 29, 29, 0.3) !important;
}

:deep(.diff-container .ChangeDelete .Left del) {
    background-color: rgba(185, 28, 28, 0.4) !important;
    color: #fca5a5 !important;
}

:deep(.diff-container .ChangeInsert .Right) {
    background-color: rgba(6, 78, 59, 0.3) !important;
}

:deep(.diff-container .ChangeInsert .Right ins) {
    background-color: rgba(4, 120, 87, 0.4) !important;
    color: #a7f3d0 !important;
}

/* 替换行的样式 */
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
    /* 使用 Tailwind 的 prose 类来格式化 wiki 内容 */
}

/* 确保 prose 样式在预览窗格内生效 */
.preview-pane :deep(h1),
.preview-pane :deep(h2),
.preview-pane :deep(h3),
.preview-pane :deep(h4),
.preview-pane :deep(h5),
.preview-pane :deep(h6) {
    @apply mt-4 mb-2;
    /* 调整标题间距 */
}

.preview-pane :deep(p) {
    @apply mb-3 leading-relaxed;
    /* 段落间距和行高 */
}

/* 其他必要的 prose 覆盖或调整 */
</style>