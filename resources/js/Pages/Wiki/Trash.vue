<template>
    <MainLayout
        :navigationLinks="[{ href: '/wiki', label: '游戏维基' }, { href: '#', label: '游戏历史&名人墙' }, { href: '#', label: '自制专区' }, { href: '#', label: '攻略专区' }, { href: '#', label: '论坛' }]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white/80 backdrop-blur-sm shadow-lg rounded-lg overflow-hidden">
                <div class="p-6">
                    <!-- 标题和返回按钮 -->
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-2xl font-semibold text-gray-900">回收站</h2>
                        <Link :href="route('wiki.index')"
                            class="px-4 py-2 text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200 transition duration-150 ease-in-out">
                        返回页面列表
                        </Link>
                    </div>

                    <!-- 统计信息 -->
                    <div class="bg-gray-50 rounded-lg p-4 mb-6" v-if="props.stats">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="text-center">
                                <div class="text-2xl font-bold text-blue-600">{{ props.stats.total }}</div>
                                <div class="text-sm text-gray-500">回收站中的页面</div>
                            </div>
                            <div class="text-center" v-if="props.stats.oldest_deleted_at">
                                <div class="text-sm font-medium">最早删除于</div>
                                <div class="text-gray-600">{{ props.stats.oldest_deleted_at }}</div>
                            </div>
                            <div class="text-center" v-if="props.stats.newest_deleted_at">
                                <div class="text-sm font-medium">最近删除于</div>
                                <div class="text-gray-600">{{ props.stats.newest_deleted_at }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- 批量操作区域 -->
                    <div class="mb-4 bg-gray-50 p-4 rounded-lg">
                        <div class="flex justify-between items-center">
                            <div class="flex items-center space-x-4">
                                <div class="flex items-center">
                                    <input type="checkbox" v-model="selectAll"
                                        class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <span class="ml-2 text-sm text-gray-700">全选</span>
                                </div>
                                <div>
                                    <button @click="confirmRestoreSelected" :disabled="selectedPages.length === 0"
                                        class="px-3 py-1 bg-blue-500 text-white text-sm rounded-md hover:bg-blue-600 disabled:opacity-50">
                                        批量恢复
                                    </button>
                                    <button @click="confirmDeleteSelected" :disabled="selectedPages.length === 0"
                                        class="px-3 py-1 bg-red-600 text-white text-sm rounded-md hover:bg-red-700 disabled:opacity-50 ml-2">
                                        批量删除
                                    </button>
                                </div>
                            </div>
                            <div v-if="selectedPages.length > 0" class="text-sm text-gray-600">
                                已选择 {{ selectedPages.length }} 个页面
                            </div>
                        </div>

                        <!-- 搜索和筛选区域 -->
                        <div class="mt-4 flex flex-wrap gap-4">
                            <div class="flex-1">
                                <input type="text" v-model="filters.search" @input="search"
                                    class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="搜索页面标题...">
                            </div>
                            <div>
                                <select v-model="filters.sort" @change="applyFilters"
                                    class="border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                    <option value="deleted_at_desc">最近删除</option>
                                    <option value="deleted_at_asc">最早删除</option>
                                    <option value="title">按标题</option>
                                    <option value="title_desc">按标题倒序</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- 页面列表 -->
                    <div class="space-y-6">
                        <div v-for="page in pages.data" :key="page.id"
                            class="border-b border-gray-200 pb-6 last:border-0 last:pb-0">
                            <div class="flex justify-between items-start">
                                <div class="flex">
                                    <div class="mr-4 pt-1">
                                        <input type="checkbox" :value="page.id" v-model="selectedPages"
                                            class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    </div>
                                    <div>
                                        <div class="text-xl font-medium text-gray-900">
                                            {{ page.title }}
                                        </div>
                                        <div class="mt-2 text-sm text-gray-500">
                                            <span>删除于: {{ page.deleted_at }}</span>
                                            <span class="mx-2">•</span>
                                            <span>创建者: {{ page.creator?.name || '未知' }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex gap-2">
                                    <button @click="confirmRestore(page)"
                                        class="text-blue-600 hover:text-blue-900">恢复</button>
                                    <button @click="confirmForceDelete(page)"
                                        class="text-red-600 hover:text-red-900">彻底删除</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 分页 -->
                    <div class="mt-6">
                        <Pagination :links="pages.links" />
                    </div>
                </div>
            </div>
        </div>
        <ConfirmModal :show="showRestoreConfirmation" @close="cancelRestore" @confirm="restoreConfirmed" title="确认恢复"
            :message="'确定要恢复' + pageToRestore?.title + '吗？'" confirmText="确认恢复" />
        <ConfirmModal :show="showForceDeleteConfirmation" @close="cancelForceDelete" @confirm="forceDeleteConfirmed"
            title="确认强制删除" :message="'确定要强制删除' + pageToForceDelete?.title + '吗？'" confirmText="确认删除" />
    </MainLayout>
</template>

<script setup>
import { ref, reactive, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayouts/MainLayout.vue';
import ConfirmModal from '@/Components/Modal/ConfirmModal.vue';
import Pagination from '@/Components/Other/Pagination.vue';
import { debounce } from 'lodash';

const props = defineProps({
    pages: Object,
    filters: Object,
    stats: Object,
});

// 筛选和搜索
const filters = reactive({
    sort: props.filters?.sort || 'deleted_at_desc',
    search: props.filters?.search || '',
});

const applyFilters = () => {
    router.get(route('wiki.trash'), filters, {
        preserveState: true,
        preserveScroll: true
    });
};

// 搜索防抖处理
const search = debounce(() => {
    applyFilters();
}, 300);

// 批量操作相关
const selectedPages = ref([]);
const selectAll = ref(false);

// 全选/取消全选
const toggleSelectAll = () => {
    if (selectAll.value) {
        selectedPages.value = props.pages.data.map(page => page.id);
    } else {
        selectedPages.value = [];
    }
};

// 监听selectAll变化
watch(selectAll, toggleSelectAll);

// 检查是否所有页面都被选中
const checkSelectAll = () => {
    selectAll.value = selectedPages.value.length === props.pages.data.length && props.pages.data.length > 0;
};

// 监听selectedPages变化
watch(selectedPages, checkSelectAll);

// 批量恢复确认
const confirmRestoreSelected = () => {
    if (selectedPages.value.length === 0) return;
    // 确认是否批量恢复
    if (confirm(`确定要恢复已选择的 ${selectedPages.value.length} 个页面吗？`)) {
        restoreSelected();
    }
};

// 执行批量恢复
const restoreSelected = () => {
    router.post(route('wiki.restore-selected'), { ids: selectedPages.value }, {
        onSuccess: () => {
            selectedPages.value = [];
            selectAll.value = false;
        },
    });
};

// 批量删除确认
const confirmDeleteSelected = () => {
    if (selectedPages.value.length === 0) return;
    // 确认是否批量永久删除
    if (confirm(`确定要永久删除已选择的 ${selectedPages.value.length} 个页面吗？此操作无法撤销。`)) {
        deleteSelected();
    }
};

// 执行批量删除
const deleteSelected = () => {
    router.delete(route('wiki.force-delete-selected'), { data: { ids: selectedPages.value } }, {
        onSuccess: () => {
            selectedPages.value = [];
            selectAll.value = false;
        },
    });
};

// 单个恢复相关
const showRestoreConfirmation = ref(false);
const pageToRestore = ref(null);

const confirmRestore = (page) => {
    pageToRestore.value = page;
    showRestoreConfirmation.value = true;
};

const cancelRestore = () => {
    showRestoreConfirmation.value = false;
    pageToRestore.value = null;
};

const restoreConfirmed = () => {
    if (pageToRestore.value) {
        router.post(route('wiki.restore', pageToRestore.value.id), {}, {
            onSuccess: () => {
                cancelRestore();
            },
        });
    }
};

// 单个彻底删除相关
const showForceDeleteConfirmation = ref(false);
const pageToForceDelete = ref(null);

const confirmForceDelete = (page) => {
    pageToForceDelete.value = page;
    showForceDeleteConfirmation.value = true;
};

const cancelForceDelete = () => {
    showForceDeleteConfirmation.value = false;
    pageToForceDelete.value = null;
};

const forceDeleteConfirmed = () => {
    if (pageToForceDelete.value) {
        router.delete(route('wiki.force-delete', pageToForceDelete.value.id), {
            onSuccess: () => {
                cancelForceDelete();
            },
        });
    }
};
</script>