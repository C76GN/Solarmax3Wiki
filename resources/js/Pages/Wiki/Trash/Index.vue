<template>
    <!-- 主布局容器，引入导航链接 -->
    <MainLayout :navigationLinks="navigationLinks">

        <!-- 设置页面标题为“Wiki 回收站” -->

        <Head title="Wiki 回收站" />
        <div class="container mx-auto py-6 px-4">
            <!-- 页面内容主体卡片 -->
            <div class="bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm rounded-lg shadow-lg p-6">
                <!-- 页面头部，包含标题和返回Wiki列表的链接 -->
                <div
                    class="flex flex-col md:flex-row justify-between md:items-center mb-6 pb-4 border-b border-gray-200 dark:border-gray-700">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-2 md:mb-0">Wiki 回收站</h1>
                    <!-- 返回 Wiki 列表的链接 -->
                    <Link :href="route('wiki.index')" class="btn-link text-sm">
                    <font-awesome-icon :icon="['fas', 'arrow-left']" class="mr-1" /> 返回 Wiki 列表
                    </Link>
                </div>
                <!-- 搜索表单区域 -->
                <div class="mb-6">
                    <form @submit.prevent="performSearch" class="flex items-center gap-2">
                        <div class="relative flex-grow">
                            <!-- 搜索输入框 -->
                            <input type="text" v-model="search" placeholder="搜索已删除的页面..."
                                class="py-2 pl-10 pr-4 w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm" />
                            <!-- 搜索图标 -->
                            <div
                                class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 dark:text-gray-500">
                                <font-awesome-icon :icon="['fas', 'search']" />
                            </div>
                        </div>
                        <!-- 搜索按钮 -->
                        <button type="submit" class="btn-primary text-sm">搜索</button>
                        <!-- 清空搜索按钮，当有搜索条件时显示 -->
                        <button type="button" @click="clearSearch" v-if="filters.search"
                            class="btn-secondary text-sm">清空</button>
                    </form>
                </div>
                <!-- 已删除页面列表表格 -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left table-fixed">
                        <thead class="bg-gray-100 dark:bg-gray-700/50">
                            <tr>
                                <th class="th-cell w-2/5">标题</th>
                                <th class="th-cell w-1/5">创建者</th>
                                <th class="th-cell w-1/5">删除时间</th>
                                <th class="th-cell w-1/5 text-right">操作</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <!-- 遍历显示已删除的页面数据 -->
                            <tr v-for="page in trashedPages.data" :key="page.id"
                                class="hover:bg-gray-50 dark:hover:bg-gray-800/30">
                                <td class="td-cell font-medium text-gray-900 dark:text-gray-100 truncate"
                                    :title="page.title">{{ page.title }}</td>
                                <td class="td-cell text-gray-700 dark:text-gray-300">{{ page.creator?.name || '未知用户' }}
                                </td>
                                <td class="td-cell text-gray-600 dark:text-gray-400 whitespace-nowrap">{{
                                    formatDateTime(page.deleted_at)
                                }}</td>
                                <td class="td-cell text-right">
                                    <div class="flex justify-end space-x-3">
                                        <!-- 恢复按钮，需要'wiki.trash.restore'权限 -->
                                        <button v-if="$page.props.auth.user.permissions.includes('wiki.trash.restore')"
                                            @click="confirmRestore(page)"
                                            class="btn-link text-xs text-green-600 dark:text-green-400 hover:text-green-800 dark:hover:text-green-300"
                                            title="恢复">
                                            <font-awesome-icon :icon="['fas', 'undo']" />
                                        </button>
                                        <!-- 永久删除按钮，需要'wiki.trash.force_delete'权限 -->
                                        <button
                                            v-if="$page.props.auth.user.permissions.includes('wiki.trash.force_delete')"
                                            @click="confirmForceDelete(page)"
                                            class="btn-link text-xs text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300"
                                            title="永久删除">
                                            <font-awesome-icon :icon="['fas', 'trash-alt']" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <!-- 没有页面时的提示信息 -->
                            <tr v-if="trashedPages.data.length === 0">
                                <td colspan="4" class="py-6 text-center text-gray-500 dark:text-gray-400 italic">回收站是空的。
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <!-- 分页组件 -->
                <Pagination :links="trashedPages.links" class="mt-6" />
            </div>
        </div>

        <!-- 恢复页面确认模态框 -->
        <Modal :show="showRestoreConfirm" @close="cancelRestore" @confirm="restorePage" title="确认恢复页面"
            :showFooter="true">
            <template #default>
                <div class="p-6">
                    <p class="mb-4 text-gray-600 dark:text-gray-300">确定要恢复页面 “<strong
                            class="font-semibold text-gray-900 dark:text-gray-100">{{ pageToRestore?.title }}</strong>”
                        吗？
                    </p>
                </div>
            </template>
            <template #footer>
                <button @click="cancelRestore" class="btn-secondary">取消</button>
                <button @click="restorePage"
                    class="btn-primary ml-3 bg-green-600 hover:bg-green-700 dark:bg-green-600 dark:hover:bg-green-700"
                    :disabled="restoring">
                    <font-awesome-icon v-if="restoring" :icon="['fas', 'spinner']" spin class="mr-1" />
                    {{ restoring ? '恢复中...' : '确认恢复' }}
                </button>
            </template>
        </Modal>

        <!-- 永久删除确认模态框 -->
        <Modal :show="showForceDeleteConfirm" @close="cancelForceDelete" @confirm="forceDeletePage" title="确认永久删除页面"
            :dangerAction="true" :showFooter="true">
            <template #default>
                <div class="p-6">
                    <p class="mb-4 text-gray-600 dark:text-gray-300">确定要 <strong
                            class="text-red-600 dark:text-red-400">永久删除</strong> 页面 “<strong
                            class="font-semibold text-gray-900 dark:text-gray-100">{{ pageToForceDelete?.title
                            }}</strong>”
                        吗？</p>
                    <p class="text-sm text-red-600 dark:text-red-400 font-medium"><font-awesome-icon
                            :icon="['fas', 'exclamation-triangle']" class="mr-1" /> 此操作不可恢复！</p>
                </div>
            </template>
            <template #footer>
                <button @click="cancelForceDelete" class="btn-secondary">取消</button>
                <button @click="forceDeletePage"
                    class="btn-primary ml-3 bg-red-600 hover:bg-red-700 dark:bg-red-600 dark:hover:bg-red-700"
                    :disabled="forceDeleting">
                    <font-awesome-icon v-if="forceDeleting" :icon="['fas', 'spinner']" spin class="mr-1" />
                    {{ forceDeleting ? '删除中...' : '确认永久删除' }}
                </button>
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
import { formatDateTime } from '@/utils/formatters';
import { adminNavigationLinks } from '@/config/navigationConfig';

// 管理员导航链接配置
const navigationLinks = adminNavigationLinks;
// 获取 Inertia 页面属性
const pageProps = usePage().props;

// 定义组件接收的属性
const props = defineProps({
    trashedPages: { type: Object, required: true }, // 已删除页面数据，包含分页信息
    filters: { type: Object, default: () => ({}) } // 当前应用的过滤器
});

// 搜索框的响应式数据，初始化为过滤器中的搜索值
const search = ref(props.filters.search || '');
// 闪存消息组件的引用
const flashMessage = ref(null);

// --- 模态框状态管理 ---
// 恢复确认模态框的显示状态
const showRestoreConfirm = ref(false);
// 待恢复的页面对象
const pageToRestore = ref(null);
// 恢复操作的加载状态
const restoring = ref(false);
// 永久删除确认模态框的显示状态
const showForceDeleteConfirm = ref(false);
// 待永久删除的页面对象
const pageToForceDelete = ref(null);
// 永久删除操作的加载状态
const forceDeleting = ref(false);

// --- 方法定义 ---
/**
 * 根据搜索条件执行页面搜索和过滤
 */
const performSearch = () => {
    // 复制当前过滤器，避免直接修改props
    const currentFilters = { ...props.filters };
    if (search.value.trim()) {
        // 如果搜索框有值，则添加到过滤器
        currentFilters.search = search.value.trim();
    } else {
        // 如果搜索框为空，则从过滤器中移除搜索条件
        delete currentFilters.search;
    }
    // 发送 Inertia 请求，更新页面数据，保持状态和滚动位置
    router.get(route('wiki.trash.index'), currentFilters, {
        preserveState: true,
        preserveScroll: true,
        replace: true // 替换当前历史记录，而不是添加新记录
    });
};

/**
 * 清空搜索框并重新执行搜索
 */
const clearSearch = () => {
    search.value = ''; // 清空搜索框的值
    performSearch(); // 执行一次空搜索以清除过滤器
};

// --- 恢复页面相关方法 ---
/**
 * 显示恢复确认模态框
 * @param {Object} page - 要恢复的页面对象
 */
const confirmRestore = (page) => {
    pageToRestore.value = page;
    showRestoreConfirm.value = true;
};

/**
 * 取消恢复操作并关闭模态框
 */
const cancelRestore = () => {
    showRestoreConfirm.value = false;
    pageToRestore.value = null;
};

/**
 * 执行页面恢复操作
 */
const restorePage = () => {
    if (!pageToRestore.value) return; // 如果没有待恢复页面，则直接返回
    restoring.value = true; // 设置加载状态
    // 发送 PUT 请求到恢复路由
    router.put(route('wiki.trash.restore', pageToRestore.value.id), {}, {
        preserveScroll: true, // 保持滚动位置
        onSuccess: () => {
            // 恢复成功，显示成功消息
            flashMessage.value?.addMessage('success', `页面 "${pageToRestore.value.title}" 已成功恢复。`);
            cancelRestore(); // 关闭模态框
        },
        onError: (errors) => {
            // 恢复失败，显示错误消息
            const errorMsg = Object.values(errors).flat()[0] || '恢复页面失败，请重试。';
            flashMessage.value?.addMessage('error', errorMsg);
        },
        onFinish: () => {
            restoring.value = false; // 结束加载状态
        }
    });
};

// --- 永久删除页面相关方法 ---
/**
 * 显示永久删除确认模态框
 * @param {Object} page - 要永久删除的页面对象
 */
const confirmForceDelete = (page) => {
    pageToForceDelete.value = page;
    showForceDeleteConfirm.value = true;
};

/**
 * 取消永久删除操作并关闭模态框
 */
const cancelForceDelete = () => {
    showForceDeleteConfirm.value = false;
    pageToForceDelete.value = null;
};

/**
 * 执行页面永久删除操作
 */
const forceDeletePage = () => {
    if (!pageToForceDelete.value) return; // 如果没有待永久删除页面，则直接返回
    forceDeleting.value = true; // 设置加载状态
    // 发送 DELETE 请求到永久删除路由
    router.delete(route('wiki.trash.force-delete', pageToForceDelete.value.id), {
        preserveScroll: true, // 保持滚动位置
        onSuccess: () => {
            // 永久删除成功，显示成功消息
            flashMessage.value?.addMessage('success', `页面 "${pageToForceDelete.value.title}" 已被永久删除。`);
            cancelForceDelete(); // 关闭模态框
        },
        onError: (errors) => {
            // 永久删除失败，显示错误消息
            const errorMsg = Object.values(errors).flat()[0] || '永久删除页面失败，请重试。';
            flashMessage.value?.addMessage('error', errorMsg);
        },
        onFinish: () => {
            forceDeleting.value = false; // 结束加载状态
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
    @apply px-4 py-4 text-sm whitespace-nowrap;
}

/* 截断长文本的样式 */
.td-cell.truncate {
    white-space: normal;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* 主要按钮样式 */
.btn-primary {
    @apply px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm disabled:opacity-50;
}

/* 次要按钮样式 */
.btn-secondary {
    @apply px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition dark:bg-gray-600 dark:text-gray-200 dark:hover:bg-gray-500 text-sm;
}

/* 链接按钮样式 */
.btn-link {
    @apply text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 hover:underline transition cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed;
}

/* 模态框内容段落样式 */
.modal-content p {
    @apply text-gray-600;
}

/* 模态框内容加粗文本样式 */
.modal-content strong {
    @apply font-semibold text-gray-900;
}

/* 深色模式下模态框内容段落样式 */
.dark .modal-content p {
    @apply text-gray-300;
}

/* 深色模式下模态框内容加粗文本样式 */
.dark .modal-content strong {
    @apply text-gray-100;
}

/* 深色模式下模态框红色文本样式 */
.dark .modal-content .dark\:text-red-400 {
    color: #fc8181;
}

/* 模态框红色文本样式 */
.modal-content .text-red-600 {
    color: #e53e3e;
}

/* 模态框底部按钮最小宽度 */
.modal-footer button {
    min-width: 80px;
}
</style>