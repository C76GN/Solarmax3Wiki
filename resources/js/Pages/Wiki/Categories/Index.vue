<template>
    <MainLayout :navigationLinks="navigationLinks">
        <div class="container mx-auto py-6 px-4">
            <div class="bg-white/80 backdrop-blur-sm rounded-lg shadow-lg p-6">
                <div class="flex justify-between items-center mb-6">
                    <h1 class="text-2xl font-bold">Wiki 分类管理</h1>
                    <Link :href="route('wiki.categories.create')"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                    <font-awesome-icon :icon="['fas', 'plus']" class="mr-2" />
                    添加分类
                    </Link>
                </div>

                <!-- 分类列表 -->
                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white">
                        <thead class="bg-gray-100">
                            <tr>
                                <th
                                    class="py-3 px-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    名称</th>
                                <th
                                    class="py-3 px-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    描述</th>
                                <th
                                    class="py-3 px-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    父分类</th>
                                <th
                                    class="py-3 px-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    页面数量</th>
                                <th
                                    class="py-3 px-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    操作</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-for="category in categories" :key="category.id">
                                <td class="py-4 px-6 text-sm">{{ category.name }}</td>
                                <td class="py-4 px-6 text-sm">{{ category.description || '-' }}</td>
                                <td class="py-4 px-6 text-sm">{{ category.parent ? category.parent.name : '-' }}</td>
                                <td class="py-4 px-6 text-sm">{{ category.pages_count }}</td>
                                <td class="py-4 px-6 text-sm space-x-2">
                                    <Link :href="route('wiki.categories.edit', category.id)"
                                        class="text-blue-600 hover:text-blue-800">
                                    <font-awesome-icon :icon="['fas', 'edit']" />
                                    </Link>
                                    <button @click="confirmDelete(category)" class="text-red-600 hover:text-red-800">
                                        <font-awesome-icon :icon="['fas', 'trash']" />
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 删除确认对话框 -->
        <ConfirmModal :show="showDeleteConfirm" title="确认删除分类"
            :message="'确定要删除分类 ' + categoryToDelete?.name + ' 吗？此操作不可恢复，相关页面的分类关联也将被删除。'" confirm-text="删除"
            cancel-text="取消" :danger-action="true" @close="cancelDelete" @confirm="deleteCategory" />
    </MainLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayouts/MainLayout.vue';
import ConfirmModal from '@/Components/Modal/ConfirmModal.vue';

const navigationLinks = [
    { href: '/wiki', label: 'Wiki' },
    { href: '/wiki/categories', label: '分类管理' },
    { href: '/wiki/tags', label: '标签管理' },
    { href: '#', label: '模板管理' },
];

const props = defineProps({
    categories: {
        type: Array,
        required: true
    }
});

// 删除确认相关
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
            onSuccess: () => {
                showDeleteConfirm.value = false;
                categoryToDelete.value = null;
            }
        });
    }
};
</script>