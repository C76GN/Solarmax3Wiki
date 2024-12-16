// FileName: /var/www/Solarmax3Wiki/resources/js/Pages/Wiki/Trash.vue
<template>
    <MainLayout
        :navigationLinks="[{ href: '/wiki', label: '游戏维基' }, { href: '#', label: '游戏历史&名人墙' }, { href: '#', label: '自制专区' }, { href: '#', label: '攻略专区' }, { href: '#', label: '论坛' }]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white/80 backdrop-blur-sm shadow-lg rounded-lg overflow-hidden">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-2xl font-semibold text-gray-900">回收站</h2>
                        <Link :href="route('wiki.index')"
                            class="px-4 py-2 text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200 transition duration-150 ease-in-out">
                        返回页面列表
                        </Link>
                    </div>

                    <!-- 页面列表 -->
                    <div class="space-y-6">
                        <div v-for="page in pages.data" :key="page.id"
                            class="border-b border-gray-200 pb-6 last:border-0 last:pb-0">
                            <div class="flex justify-between items-start">
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

        <!-- 恢复确认对话框 -->
        <Modal :show="showRestoreConfirmation" @close="cancelRestore">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">
                    确认恢复
                </h3>
                <p class="text-sm text-gray-500 mb-4">
                    确定要恢复"{{ pageToRestore?.title }}"吗？
                </p>
                <div class="mt-5 flex justify-end gap-4">
                    <button type="button" @click="cancelRestore"
                        class="px-4 py-2 text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200">
                        取消
                    </button>
                    <button type="button" @click="restoreConfirmed"
                        class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600">
                        确认恢复
                    </button>
                </div>
            </div>
        </Modal>

        <!-- 彻底删除确认对话框 -->
        <Modal :show="showForceDeleteConfirmation" @close="cancelForceDelete">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">
                    确认彻底删除
                </h3>
                <p class="text-sm text-gray-500 mb-4">
                    确定要彻底删除"{{ pageToForceDelete?.title }}"吗？此操作无法撤销。
                </p>
                <div class="mt-5 flex justify-end gap-4">
                    <button type="button" @click="cancelForceDelete"
                        class="px-4 py-2 text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200">
                        取消
                    </button>
                    <button type="button" @click="forceDeleteConfirmed"
                        class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
                        彻底删除
                    </button>
                </div>
            </div>
        </Modal>
    </MainLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayouts/MainLayout.vue';
import Modal from '@/Components/Modal/Modal.vue';
import Pagination from '@/Components/Other/Pagination.vue';

const props = defineProps({
    pages: Object,
});

// 恢复相关
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

// 彻底删除相关
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