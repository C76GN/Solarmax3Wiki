<template>
    <!-- 主要布局容器，引入导航链接 -->
    <MainLayout :navigationLinks="navigationLinks">

        <!-- 设置页面标题 -->

        <Head title="Wiki 分类管理" />
        <div class="container mx-auto py-6 px-4">
            <!-- 页面内容主体卡片 -->
            <div class="bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm rounded-lg shadow-lg p-6">
                <div class="flex justify-between items-center mb-6 pb-4 border-b dark:border-gray-700">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Wiki 分类管理</h1>
                    <!-- 添加分类按钮，仅当用户有管理权限时显示 -->
                    <Link v-if="$page.props.auth.user.permissions.includes('wiki.manage_categories')"
                        :href="route('wiki.categories.create')" class="btn-primary text-sm">
                    <font-awesome-icon :icon="['fas', 'plus']" class="mr-2" /> 添加分类
                    </Link>
                </div>

                <!-- 分类列表表格 -->
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
                            <!-- 如果没有分类，显示提示信息 -->
                            <tr v-if="categories.length === 0">
                                <td colspan="5" class="py-6 text-center text-gray-500 dark:text-gray-400 italic">
                                    没有找到任何分类。</td>
                            </tr>
                            <!-- 遍历显示每个分类 -->
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
                                        <!-- 编辑分类链接，仅当用户有管理权限时显示 -->
                                        <Link
                                            v-if="$page.props.auth.user.permissions.includes('wiki.manage_categories')"
                                            :href="route('wiki.categories.edit', category.id)" class="btn-link text-xs">
                                        <font-awesome-icon :icon="['fas', 'edit']" />
                                        </Link>
                                        <!-- 删除分类按钮，仅当用户有管理权限时显示 -->
                                        <button
                                            v-if="$page.props.auth.user.permissions.includes('wiki.manage_categories')"
                                            @click="confirmDelete(category)"
                                            class="btn-link text-xs text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300">
                                            <font-awesome-icon :icon="['fas', 'trash']" />
                                        </button>
                                        <!-- 如果用户没有操作权限，显示提示 -->
                                        <span v-else class="text-xs text-gray-400 italic">无操作权限</span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 删除确认模态框 -->
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
        <!-- 闪存消息组件，用于显示操作结果反馈 -->
        <FlashMessage ref="flashMessage" />
    </MainLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayouts/MainLayout.vue';
import Modal from '@/Components/Modal/Modal.vue';
import FlashMessage from '@/Components/Other/FlashMessage.vue';
import { adminNavigationLinks } from '@/config/navigationConfig';

// 管理员导航链接配置
const navigationLinks = adminNavigationLinks;
// 引用 FlashMessage 组件实例
const flashMessage = ref(null);

// 定义组件接收的属性
const props = defineProps({
    categories: {
        type: Array,
        required: true
    }
});

// 控制删除确认模态框的显示状态
const showDeleteConfirm = ref(false);
// 存储待删除的分类对象
const categoryToDelete = ref(null);

/**
 * 弹出删除确认模态框。
 * @param {Object} category - 待删除的分类对象。
 */
const confirmDelete = (category) => {
    categoryToDelete.value = category;
    showDeleteConfirm.value = true;
};

/**
 * 取消删除操作并关闭模态框。
 */
const cancelDelete = () => {
    showDeleteConfirm.value = false;
    categoryToDelete.value = null;
};

/**
 * 执行删除分类操作。
 */
const deleteCategory = () => {
    if (categoryToDelete.value) {
        router.delete(route('wiki.categories.destroy', categoryToDelete.value.id), {
            preserveScroll: true, // 保持滚动位置
            onSuccess: () => {
                // 删除成功后，FlashMessage 会自动处理消息显示
            },
            onError: (errors) => {
                // 提取错误信息，如果不存在则使用默认消息
                const errorMsg = Object.values(errors).flat()[0] || '删除分类失败，请重试。';
                // 通过 FlashMessage 显示错误
                if (flashMessage.value) {
                    flashMessage.value.addMessage('error', errorMsg);
                } else {
                    alert(errorMsg); // 备用方案，如果 FlashMessage 未加载
                }
            },
            onFinish: () => {
                cancelDelete(); // 关闭模态框
            }
        });
    }
};
</script>

<style scoped>
/* 表格头部单元格共享样式 */
.th-cell {
    @apply px-4 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wider;
}

/* 表格数据单元格共享样式 */
.td-cell {
    @apply px-4 py-4 text-sm;
}

/* 主要按钮样式 */
.btn-primary {
    @apply px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm disabled:opacity-50;
}

/* 链接样式按钮 */
.btn-link {
    @apply text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 hover:underline transition;
}

/* 模态框内容段落样式 */
.modal-content p {
    @apply text-gray-600 dark:text-gray-300;
}

/* 模态框内容加粗文本样式 */
.modal-content strong {
    @apply font-semibold text-gray-800 dark:text-gray-200;
}
</style>