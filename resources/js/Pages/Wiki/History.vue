<template>
    <!-- 主要布局容器，引入导航链接 -->
    <MainLayout :navigationLinks="navigationLinks">
        <!-- 设置页面标题，显示当前页面标题和“版本历史” -->

        <Head :title="`${page.title} - 版本历史`" />
        <div class="container mx-auto py-6 px-4">
            <!-- 页面内容主体卡片 -->
            <div class="bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm rounded-lg shadow-lg p-6">
                <!-- 页面头部，包含标题和返回页面按钮 -->
                <div
                    class="flex flex-col md:flex-row justify-between md:items-center mb-6 pb-4 border-b border-gray-200 dark:border-gray-700">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-2 md:mb-0">{{ page.title }} - 版本历史
                    </h1>
                    <!-- 返回当前Wiki页面的链接 -->
                    <Link :href="route('wiki.show', page.slug)" class="btn-link text-sm">
                    <font-awesome-icon :icon="['fas', 'arrow-left']" class="mr-1" /> 返回页面
                    </Link>
                </div>
                <!-- 版本对比区域，只有当版本数量大于1时才显示 -->
                <div v-if="versions.data.length > 1"
                    class="mb-6 p-4 bg-gray-50 dark:bg-gray-800/50 rounded-lg border border-gray-200 dark:border-gray-700">
                    <form @submit.prevent="compareVersions">
                        <div class="flex flex-wrap items-center gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    对比版本
                                </label>
                                <div class="flex items-center gap-2 text-sm">
                                    <!-- 选择对比的起始版本 -->
                                    <select v-model="compareFrom" class="select-field">
                                        <option v-for="version in versions.data" :key="`from-${version.id}`"
                                            :value="version.version_number">
                                            v{{ version.version_number }} ({{ formatDate(version.created_at) }})
                                        </option>
                                    </select>
                                    <span class="text-gray-600 dark:text-gray-400">与</span>
                                    <!-- 选择对比的结束版本 -->
                                    <select v-model="compareTo" class="select-field">
                                        <option v-for="version in versions.data" :key="`to-${version.id}`"
                                            :value="version.version_number">
                                            v{{ version.version_number }} ({{ formatDate(version.created_at) }})
                                        </option>
                                    </select>
                                </div>
                            </div>
                            <!-- 比较版本按钮，当选择的版本相同时禁用 -->
                            <button type="submit" class="btn-primary text-sm" :disabled="compareFrom === compareTo">
                                比较版本
                            </button>
                        </div>
                    </form>
                </div>
                <!-- 版本历史列表区域 -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left table-fixed">
                        <thead class="bg-gray-100 dark:bg-gray-700/50">
                            <tr>
                                <th class="th-cell w-1/6">版本</th>
                                <th class="th-cell w-1/4">时间</th>
                                <th class="th-cell w-1/6">编辑者</th>
                                <th class="th-cell w-1/3">编辑说明</th>
                                <th class="th-cell w-1/6 text-right">操作</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <!-- 遍历并显示每个版本记录 -->
                            <tr v-for="version in versions.data" :key="version.id"
                                class="hover:bg-gray-50 dark:hover:bg-gray-800/30"
                                :class="{ 'bg-blue-50 dark:bg-blue-900/30 font-medium': version.is_current }">
                                <td class="td-cell">
                                    <div class="flex items-center">
                                        <span class="font-medium text-gray-900 dark:text-gray-100">v{{
                                            version.version_number }}</span>
                                        <!-- 如果是当前版本，显示“当前版本”标签 -->
                                        <span v-if="version.is_current"
                                            class="ml-2 px-2 py-0.5 text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300 rounded-full">
                                            当前版本
                                        </span>
                                    </div>
                                </td>
                                <td class="td-cell text-gray-600 dark:text-gray-400">
                                    {{ formatDateTime(version.created_at) }}
                                </td>
                                <td class="td-cell text-gray-800 dark:text-gray-200">
                                    {{ version.creator?.name || '未知用户' }}
                                </td>
                                <td class="td-cell text-gray-700 dark:text-gray-300 truncate"
                                    :title="version.comment || '无说明'">
                                    {{ version.comment || '-' }}
                                </td>
                                <td class="td-cell text-right">
                                    <div class="flex justify-end space-x-3">
                                        <!-- 查看版本详情的链接 -->
                                        <Link
                                            :href="route('wiki.show-version', { page: page.slug, version: version.version_number })"
                                            class="btn-link text-xs">查看</Link>
                                        <!-- 恢复版本按钮，只有当不是当前版本且有恢复权限时显示 -->
                                        <button v-if="!version.is_current && canRevert" @click="confirmRevert(version)"
                                            class="btn-link text-xs text-yellow-600 dark:text-yellow-400 hover:text-yellow-800 dark:hover:text-yellow-300">恢复</button>
                                    </div>
                                </td>
                            </tr>
                            <!-- 如果没有历史版本记录，显示提示信息 -->
                            <tr v-if="versions.data.length === 0">
                                <td colspan="5" class="py-6 text-center text-gray-500 dark:text-gray-400 italic">
                                    没有历史版本记录。</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <!-- 分页组件 -->
                <Pagination :links="versions.links" class="mt-6" />
            </div>
        </div>

        <!-- 确认恢复版本模态框 -->
        <Modal :show="showRevertModal" @close="closeRevertModal" @confirm="revertToVersion" maxWidth="md" title="确认恢复版本"
            :showFooter="true" :confirmDisabled="isReverting" confirmText="确认恢复" cancelText="取消">
            <template #default>
                <div class="p-6">
                    <p class="mb-6 text-sm text-gray-600 dark:text-gray-300">
                        您确定要将页面恢复到 <strong class="font-semibold text-gray-900 dark:text-gray-100">版本 v{{
                            revertVersion?.version_number || '' }}</strong> 吗？
                        <br>此操作将在当前版本之后创建一个包含所选版本内容的新版本，并保留所有历史记录。当前版本将不再是最新版本。
                    </p>
                    <!-- 恢复操作进行中时显示加载动画 -->
                    <div v-if="isReverting" class="flex justify-center items-center mt-4">
                        <font-awesome-icon :icon="['fas', 'spinner']" spin class="text-blue-500 text-xl" />
                        <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">正在处理...</span>
                    </div>
                </div>
            </template>
        </Modal>

        <!-- 闪存消息组件 -->
        <FlashMessage ref="flashMessage" />
    </MainLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayouts/MainLayout.vue';
import Pagination from '@/Components/Other/Pagination.vue';
import Modal from '@/Components/Modal/Modal.vue';
import FlashMessage from '@/Components/Other/FlashMessage.vue';
import { formatDate, formatDateTime } from '@/utils/formatters';
import { mainNavigationLinks } from '@/config/navigationConfig';

// 主要导航链接配置
const navigationLinks = mainNavigationLinks;
// 获取当前页面属性，包括认证用户信息和权限
const pageProps = usePage().props;

// 定义组件接收的属性
const props = defineProps({
    page: { type: Object, required: true }, // 当前Wiki页面对象
    versions: { type: Object, required: true }, // 页面的历史版本数据，包含分页信息
});

// 用于版本对比的起始版本号，默认是最新版本和次新版本
const compareFrom = ref(props.versions.data.length > 0 ? props.versions.data[0].version_number : 1);
const compareTo = ref(props.versions.data.length > 1 ? props.versions.data[1].version_number : 1);

// 模态框显示状态
const showRevertModal = ref(false);
// 恢复操作进行中的加载状态
const isReverting = ref(false);
// 待恢复到的版本对象
const revertVersion = ref(null);
// 闪存消息组件引用
const flashMessage = ref(null);

// 计算属性：判断用户是否有恢复历史版本的权限
const canRevert = computed(() => {
    return pageProps.auth?.user?.permissions?.includes('wiki.edit') || false;
});

/**
 * 触发版本比较功能。
 * 如果选择的版本相同，则显示警告消息。
 */
const compareVersions = () => {
    if (compareFrom.value === compareTo.value) {
        flashMessage.value?.addMessage('warning', '请选择两个不同的版本进行比较。');
        return;
    }
    // 导航到版本比较页面
    router.get(route('wiki.compare-versions', {
        page: props.page.slug,
        fromVersion: compareFrom.value,
        toVersion: compareTo.value
    }));
};

/**
 * 确认恢复版本操作。
 * 设置待恢复的版本并显示确认模态框。
 * @param {Object} version - 选中的版本对象
 */
const confirmRevert = (version) => {
    revertVersion.value = version;
    showRevertModal.value = true;
};

/**
 * 关闭恢复版本模态框并重置相关状态。
 */
const closeRevertModal = () => {
    showRevertModal.value = false;
    revertVersion.value = null;
};

/**
 * 执行恢复版本操作。
 * 发送POST请求到后端以恢复页面到指定版本。
 */
const revertToVersion = () => {
    if (!revertVersion.value) return; // 如果没有指定版本，则退出

    isReverting.value = true; // 设置加载状态为true

    // 发送POST请求到后端路由
    router.post(route('wiki.revert-version', {
        page: props.page.slug, // 页面slug作为参数
        version: revertVersion.value.version_number // 版本号作为参数
    }), {}, {
        preserveScroll: true, // 保持页面滚动位置
        onSuccess: (pageResponse) => {
            // 恢复成功后显示成功消息
            flashMessage.value?.addMessage('success', `页面已成功恢复到 v${revertVersion.value.version_number}`);
        },
        onError: (errors) => {
            // 恢复失败时记录错误并显示错误消息
            console.error('恢复失败:', errors);
            const errorMsg = Object.values(errors).flat()[0] || '恢复版本失败，请重试。';
            flashMessage.value?.addMessage('error', errorMsg);
        },
        onFinish: () => {
            // 操作完成后，无论成功或失败，都关闭模态框并重置加载状态
            closeRevertModal();
            isReverting.value = false;
        }
    });
};
</script>

<style scoped>
/* 表头单元格样式 */
.th-cell {
    @apply px-4 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wider;
}

/* 表格数据单元格样式 */
.td-cell {
    @apply px-4 py-4 text-sm;
}

/* 下拉选择框样式 */
.select-field {
    @apply rounded border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200;
    padding: 0.4rem 0.8rem;
    min-width: 180px;
}

/* 主要按钮样式 */
.btn-primary {
    @apply px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed;
}

/* 次要按钮样式 */
.btn-secondary {
    @apply px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition dark:bg-gray-600 dark:text-gray-200 dark:hover:bg-gray-500 text-sm font-medium;
}

/* 链接按钮样式 */
.btn-link {
    @apply text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 hover:underline transition;
}
</style>