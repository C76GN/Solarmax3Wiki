<template>
    <MainLayout :navigationLinks="navigationLinks">

        <Head title="Wiki 回收站" />
        <div class="container mx-auto py-6 px-4">
            <div class="bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm rounded-lg shadow-lg p-6">
                <div
                    class="flex flex-col md:flex-row justify-between md:items-center mb-6 pb-4 border-b border-gray-200 dark:border-gray-700">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-2 md:mb-0">Wiki 回收站</h1>
                    <Link :href="route('wiki.index')" class="btn-link text-sm">
                    <font-awesome-icon :icon="['fas', 'arrow-left']" class="mr-1" /> 返回 Wiki 列表
                    </Link>
                </div>
                <div class="mb-6">
                    <form @submit.prevent="performSearch" class="flex items-center gap-2">
                        <div class="relative flex-grow">
                            <input type="text" v-model="search" placeholder="搜索已删除的页面..."
                                class="py-2 pl-10 pr-4 w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm" />
                            <div
                                class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 dark:text-gray-500">
                                <font-awesome-icon :icon="['fas', 'search']" />
                            </div>
                        </div>
                        <button type="submit" class="btn-primary text-sm">搜索</button>
                        <button type="button" @click="clearSearch" v-if="filters.search"
                            class="btn-secondary text-sm">清空</button>
                    </form>
                </div>
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
                                        <button v-if="$page.props.auth.user.permissions.includes('wiki.trash.restore')"
                                            @click="confirmRestore(page)"
                                            class="btn-link text-xs text-green-600 dark:text-green-400 hover:text-green-800 dark:hover:text-green-300"
                                            title="恢复">
                                            <font-awesome-icon :icon="['fas', 'undo']" />
                                        </button>
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
                            <tr v-if="trashedPages.data.length === 0">
                                <td colspan="4" class="py-6 text-center text-gray-500 dark:text-gray-400 italic">回收站是空的。
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <Pagination :links="trashedPages.links" class="mt-6" />
            </div>
        </div>

        <!-- Restore Confirmation Modal - Added :showFooter="true" -->
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

        <!-- Force Delete Confirmation Modal - Added :showFooter="true" -->
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

const navigationLinks = adminNavigationLinks;
const pageProps = usePage().props;

const props = defineProps({
    trashedPages: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) }
});

const search = ref(props.filters.search || '');
const flashMessage = ref(null);

// --- Modal State ---
const showRestoreConfirm = ref(false);
const pageToRestore = ref(null);
const restoring = ref(false);
const showForceDeleteConfirm = ref(false);
const pageToForceDelete = ref(null);
const forceDeleting = ref(false);

// --- Methods ---
const performSearch = () => {
    const currentFilters = { ...props.filters };
    if (search.value.trim()) {
        currentFilters.search = search.value.trim();
    } else {
        delete currentFilters.search;
    }
    router.get(route('wiki.trash.index'), currentFilters, {
        preserveState: true,
        preserveScroll: true,
        replace: true
    });
};

const clearSearch = () => {
    search.value = '';
    performSearch(); // 执行一次空搜索以清除过滤器
};

// --- Restore Methods ---
const confirmRestore = (page) => {
    pageToRestore.value = page;
    showRestoreConfirm.value = true;
};

const cancelRestore = () => {
    showRestoreConfirm.value = false;
    pageToRestore.value = null;
};

const restorePage = () => {
    if (!pageToRestore.value) return;
    restoring.value = true;
    router.put(route('wiki.trash.restore', pageToRestore.value.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            flashMessage.value?.addMessage('success', `页面 "${pageToRestore.value.title}" 已成功恢复。`);
            cancelRestore();
        },
        onError: (errors) => {
            const errorMsg = Object.values(errors).flat()[0] || '恢复页面失败，请重试。';
            flashMessage.value?.addMessage('error', errorMsg);
        },
        onFinish: () => {
            restoring.value = false;
        }
    });
};

// --- Force Delete Methods ---
const confirmForceDelete = (page) => {
    pageToForceDelete.value = page;
    showForceDeleteConfirm.value = true;
};

const cancelForceDelete = () => {
    showForceDeleteConfirm.value = false;
    pageToForceDelete.value = null;
};

const forceDeletePage = () => {
    if (!pageToForceDelete.value) return;
    forceDeleting.value = true;
    router.delete(route('wiki.trash.force-delete', pageToForceDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            flashMessage.value?.addMessage('success', `页面 "${pageToForceDelete.value.title}" 已被永久删除。`);
            cancelForceDelete();
        },
        onError: (errors) => {
            const errorMsg = Object.values(errors).flat()[0] || '永久删除页面失败，请重试。';
            flashMessage.value?.addMessage('error', errorMsg);
        },
        onFinish: () => {
            forceDeleting.value = false;
        }
    });
};
</script>

<style scoped>
/* Existing styles remain the same */
.th-cell {
    @apply px-4 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wider;
}

.td-cell {
    @apply px-4 py-4 text-sm whitespace-nowrap;
    /* Added whitespace-nowrap here too */
}

.td-cell.truncate {
    /* Keep truncate for title */
    white-space: normal;
    overflow: hidden;
    text-overflow: ellipsis;
}

.btn-primary {
    @apply px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm disabled:opacity-50;
}

.btn-secondary {
    @apply px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition dark:bg-gray-600 dark:text-gray-200 dark:hover:bg-gray-500 text-sm;
}

.btn-link {
    @apply text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 hover:underline transition cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed;
}

.modal-content p {
    @apply text-gray-600;
}

.modal-content strong {
    @apply font-semibold text-gray-900;
}

.dark .modal-content p {
    @apply text-gray-300;
}

.dark .modal-content strong {
    @apply text-gray-100;
}

.dark .modal-content .dark\:text-red-400 {
    color: #fc8181;
    /* Tailwind red-400 */
}

.modal-content .text-red-600 {
    color: #e53e3e;
    /* Tailwind red-600 */
}

/* Ensure modal footer buttons have minimum width if needed */
.modal-footer button {
    min-width: 80px;
}
</style>