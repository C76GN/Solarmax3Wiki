<template>
    <MainLayout :navigationLinks="navigationLinks">

        <Head title="Wiki 分类管理" />
        <div class="container mx-auto py-6 px-4">
            <div class="bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm rounded-lg shadow-lg p-6">
                <div class="flex justify-between items-center mb-6 pb-4 border-b dark:border-gray-700">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Wiki 分类管理</h1>
                    <Link v-if="$page.props.auth.user.permissions.includes('wiki.manage_categories')"
                        :href="route('wiki.categories.create')" class="btn-primary text-sm">
                    <font-awesome-icon :icon="['fas', 'plus']" class="mr-2" /> 添加分类
                    </Link>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left table-fixed">
                        <thead class="bg-gray-100 dark:bg-gray-700/50">
                            <tr>
                                <th class="th-cell w-1/4">名称</th>
                                <th class="th-cell w-2/5">描述</th>
                                <th class="th-cell w-1/6">父分类</th>
                                <th class="th-cell w-1/12">页面</th>
                                <th class="th-cell w-auto text-right pr-6">操作</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-if="categories.length === 0">
                                <td colspan="5" class="py-6 text-center text-gray-500 dark:text-gray-400 italic">
                                    没有找到任何分类。</td>
                            </tr>
                            <tr v-for="category in categories" :key="category.id"
                                class="hover:bg-gray-50 dark:hover:bg-gray-800/30">
                                <td class="td-cell font-medium text-gray-900 dark:text-gray-100">{{ category.name }}
                                </td>
                                <td class="td-cell text-gray-700 dark:text-gray-300">{{ category.description || '-' }}
                                </td>
                                <td class="td-cell text-gray-600 dark:text-gray-400">{{ category.parent ?
                                    category.parent.name : '-' }}</td>
                                <td class="td-cell text-center text-gray-800 dark:text-gray-200">{{ category.pages_count
                                    }}</td>
                                <td class="td-cell text-right pr-6">
                                    <div class="flex justify-end space-x-3">
                                        <Link
                                            v-if="$page.props.auth.user.permissions.includes('wiki.manage_categories')"
                                            :href="route('wiki.categories.edit', category.id)" class="btn-link text-xs">
                                        <font-awesome-icon :icon="['fas', 'edit']" />
                                        </Link>
                                        <button
                                            v-if="$page.props.auth.user.permissions.includes('wiki.manage_categories')"
                                            @click="confirmDelete(category)"
                                            class="btn-link text-xs text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300">
                                            <font-awesome-icon :icon="['fas', 'trash']" />
                                        </button>
                                        <span v-else class="text-xs text-gray-400 italic">无操作权限</span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <Modal :show="showDeleteConfirm" @close="cancelDelete" @confirm="deleteCategory" :showFooter="true" dangerAction
            confirmText="确认删除" cancelText="取消" maxWidth="md">
            <template #default>
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4 flex items-center">
                        <font-awesome-icon :icon="['fas', 'exclamation-triangle']" class="text-red-500 mr-2" />
                        确认删除分类
                    </h3>
                    <p class="mb-4 text-gray-600 dark:text-gray-300">
                        确定要删除分类 “<strong class="font-semibold text-gray-800 dark:text-gray-200">{{
                            categoryToDelete?.name }}</strong>” 吗？
                    </p>
                    <p class="text-sm text-red-600 dark:text-red-400">
                        <font-awesome-icon :icon="['fas', 'exclamation-circle']" class="mr-1" />
                        此操作不可恢复，相关页面的分类关联也将被删除。如果该分类下有子分类，将无法删除。
                    </p>
                </div>
            </template>
        </Modal>
        <FlashMessage ref="flashMessage" />
    </MainLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayouts/MainLayout.vue';
import Modal from '@/Components/Modal/Modal.vue';
import FlashMessage from '@/Components/Other/FlashMessage.vue';
import Pagination from '@/Components/Other/Pagination.vue'; // Import if using pagination
import { adminNavigationLinks } from '@/config/navigationConfig';
import { formatDate } from '@/utils/formatters';

const navigationLinks = adminNavigationLinks;
const flashMessage = ref(null); // Ref for flash messages

const props = defineProps({
    categories: {
        type: Array,
        required: true
    }
});

const showDeleteConfirm = ref(false);
const categoryToDelete = ref(null);

const confirmDelete = (category) => {
    categoryToDelete.value = category;
    showDeleteConfirm.value = true;
};

const cancelDelete = () => {
    showDeleteConfirm.value = false;
    categoryToDelete.value = null;
};

const deleteCategory = () => {
    if (categoryToDelete.value) {
        router.delete(route('wiki.categories.destroy', categoryToDelete.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                // Flash message handled globally
            },
            onError: (errors) => {
                const errorMsg = Object.values(errors).flat()[0] || '删除分类失败，请重试。';
                if (flashMessage.value) {
                    flashMessage.value.addMessage('error', errorMsg);
                } else {
                    alert(errorMsg); // Fallback
                }
            },
            onFinish: () => {
                cancelDelete(); // Close modal
            }
        });
    }
};
</script>

<style scoped>
/* Shared styles for table cells */
.th-cell {
    @apply px-4 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wider;
}

.td-cell {
    @apply px-4 py-4 text-sm;
}

/* Button styles */
.btn-primary {
    @apply px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm disabled:opacity-50;
}

.btn-link {
    @apply text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 hover:underline transition;
}

/* REMOVED the problematic rule causing circular dependency */
/*
.btn-link.text-red-600 {
    @apply text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300;
}
*/
/* Modal content text styling */
.modal-content p {
    @apply text-gray-600 dark:text-gray-300;
}

.modal-content strong {
    @apply font-semibold text-gray-800 dark:text-gray-200;
}
</style>