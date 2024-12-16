// FileName: /var/www/Solarmax3Wiki/resources/js/Pages/Wiki/Categories/CategoryIndex.vue
<template>
    <MainLayout
        :navigationLinks="[{ href: '/wiki', label: '游戏维基' }, { href: '#', label: '游戏历史&名人墙' }, { href: '#', label: '自制专区' }, { href: '#', label: '攻略专区' }, { href: '#', label: '论坛' }]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white/80 backdrop-blur-sm shadow-lg rounded-lg overflow-hidden">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-2xl font-semibold text-gray-900">Wiki分类管理</h2>
                        <Link v-if="can.create_category" :href="route('wiki.categories.create')"
                            class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 transition duration-150 ease-in-out">
                        创建分类
                        </Link>
                    </div>

                    <!-- 分类列表表格 -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        分类名称
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        父分类
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        文章数量
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        创建时间
                                    </th>
                                    <th
                                        class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        操作
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="category in categories" :key="category.id">
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-gray-900">{{ category.name }}</div>
                                        <div class="text-sm text-gray-500">{{ category.description }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900">
                                            {{ category.parent?.name || '-' }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900">{{ category.articles_count }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900">{{ category.created_at }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-right text-sm font-medium">
                                        <Link v-if="can.edit_category"
                                            :href="route('wiki.categories.edit', category.id)"
                                            class="text-blue-600 hover:text-blue-900 mr-4">编辑</Link>
                                        <button v-if="can.delete_category" @click="confirmDelete(category)"
                                            class="text-red-600 hover:text-red-900">删除</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- 删除确认对话框 -->
        <ConfirmationModal :show="confirmingDeletion" title="确认删除分类"
            :message="'确定要删除分类 ' + categoryToDelete?.name + ' 吗？该分类下的所有文章将被取消分类。'" @close="closeDeleteModal"
            @confirm="deleteCategory" />
    </MainLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayouts/MainLayout.vue';
import ConfirmationModal from '@/Components/Modal/ConfirmationModal.vue';

const props = defineProps({
    categories: {
        type: Array,
        required: true
    },
    can: {
        type: Object,
        required: true
    }
});

// 删除确认状态
const confirmingDeletion = ref(false);
const categoryToDelete = ref(null);

const confirmDelete = (category) => {
    categoryToDelete.value = category;
    confirmingDeletion.value = true;
};

const closeDeleteModal = () => {
    confirmingDeletion.value = false;
    categoryToDelete.value = null;
};

const deleteCategory = () => {
    if (categoryToDelete.value) {
        router.delete(route('wiki.categories.destroy', categoryToDelete.value.id), {
            onSuccess: () => {
                closeDeleteModal();
            }
        });
    }
};
</script>