<template>
    <!-- 主布局组件，传入导航链接数据 -->
    <MainLayout :navigationLinks="navigationLinks">

        <!-- 设置页面标题，显示在浏览器标签页或窗口标题栏 -->

        <Head title="角色管理" />

        <!-- 页面主要内容容器，居中显示并设置内边距 -->
        <div class="container mx-auto py-6 px-4">
            <!-- 白色背景卡片样式，包含模糊效果、圆角和阴影，内部有内边距 -->
            <div class="bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm rounded-lg shadow-lg p-6">
                <!-- 头部区域：包含标题和“创建角色”按钮，并设置底部边框 -->
                <div class="flex justify-between items-center mb-6 pb-4 border-b dark:border-gray-700">
                    <!-- 页面主标题 -->
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">角色管理</h1>
                    <!-- “创建角色”按钮，仅当用户拥有'role.create'权限时显示 -->
                    <Link v-if="$page.props.auth.user.permissions.includes('role.create')" :href="route('roles.create')"
                        class="btn-primary text-sm">
                    <!-- FontAwesome图标和文本 -->
                    <font-awesome-icon :icon="['fas', 'plus']" class="mr-1" /> 创建角色
                    </Link>
                </div>

                <!-- 表格容器，允许内容溢出并提供滚动条 -->
                <div class="overflow-x-auto">
                    <!-- 角色列表表格，宽度占满父容器，文本左对齐 -->
                    <table class="w-full text-left">
                        <!-- 表格头部，设置背景色 -->
                        <thead class="bg-gray-100 dark:bg-gray-700/50">
                            <tr>
                                <!-- 表格列标题，设置宽度、文本对齐等样式 -->
                                <th class="th-cell w-1/4">角色名称</th>
                                <th class="th-cell w-2/5">描述</th>
                                <th class="th-cell w-auto">权限数量</th>
                                <th class="th-cell w-auto">创建时间</th>
                                <th class="th-cell w-auto text-right pr-6">操作</th>
                            </tr>
                        </thead>
                        <!-- 表格主体，行之间有分隔线 -->
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <!-- 如果角色数据为空，显示“没有找到任何角色”的提示行 -->
                            <tr v-if="roles.data.length === 0">
                                <td colspan="5" class="py-6 text-center text-gray-500 dark:text-gray-400 italic">
                                    没有找到任何角色。</td>
                            </tr>
                            <!-- 遍历roles.data数组，为每个角色渲染一行 -->
                            <tr v-for="role in roles.data" :key="role.id"
                                class="hover:bg-gray-50 dark:hover:bg-gray-800/30">
                                <!-- 角色名称列，显示显示名称和内部名称，系统角色会有一个“系统”标签 -->
                                <td class="td-cell align-top">
                                    <div class="flex items-center">
                                        <span class="font-medium text-gray-900 dark:text-gray-100">{{ role.display_name
                                        }}</span>
                                        <span v-if="role.is_system"
                                            class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300">
                                            系统
                                        </span>
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ role.name }}</div>
                                </td>
                                <!-- 描述列，显示角色描述，如果没有则显示横线 -->
                                <td class="td-cell align-top text-gray-700 dark:text-gray-300">{{ role.description ||
                                    '-' }}</td>
                                <!-- 权限数量列，居中显示 -->
                                <td class="td-cell align-top text-center text-gray-900 dark:text-gray-100">{{
                                    role.permissions_count }}</td>
                                <!-- 创建时间列，格式化显示日期，并强制不换行 -->
                                <td class="td-cell align-top text-gray-600 dark:text-gray-400 whitespace-nowrap">
                                    {{ formatDate(role.created_at) }}
                                </td>
                                <!-- 操作列，右对齐显示编辑和删除按钮 -->
                                <td class="td-cell align-top text-right pr-6 whitespace-nowrap">
                                    <div class="flex justify-end space-x-3">
                                        <!-- “编辑”按钮，仅当角色不是系统角色且用户拥有编辑权限时显示 -->
                                        <Link v-if="!role.is_system && $page.props.canEdit"
                                            :href="route('roles.edit', role.id)" class="btn-link text-xs">
                                        <font-awesome-icon :icon="['fas', 'edit']" class="mr-1" /> 编辑
                                        </Link>
                                        <!-- 如果是系统角色且用户有编辑权限，显示“不可编辑”提示 -->
                                        <span v-else-if="role.is_system && $page.props.canEdit"
                                            class="text-xs text-gray-400 italic" title="系统角色不可编辑">不可编辑</span>

                                        <!-- “删除”按钮，仅当角色不是系统角色且用户拥有删除权限时显示，点击时触发确认弹窗 -->
                                        <button v-if="!role.is_system && $page.props.canDelete"
                                            @click="confirmDelete(role)"
                                            class="btn-link text-xs text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300">
                                            <font-awesome-icon :icon="['fas', 'trash']" class="mr-1" /> 删除
                                        </button>
                                        <!-- 如果是系统角色且用户有删除权限，显示“不可删除”提示 -->
                                        <span v-else-if="role.is_system && $page.props.canDelete"
                                            class="text-xs text-gray-400 italic" title="系统角色不可删除">不可删除</span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <!-- 分页组件，接收分页链接数据并居中显示 -->
                <Pagination :links="roles.links" class="mt-6" />
            </div>
        </div>

        <!-- 删除确认模态框，根据confirmingDeletion状态显示隐藏 -->
        <Modal :show="confirmingDeletion" @close="cancelDelete" @confirm="deleteRole" :showFooter="true" dangerAction
            confirmText="确认删除" cancelText="取消" maxWidth="md">
            <!-- 模态框默认内容区域 -->
            <template #default>
                <div class="p-6">
                    <!-- 模态框标题，包含警告图标 -->
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4 flex items-center">
                        <font-awesome-icon :icon="['fas', 'exclamation-triangle']" class="text-red-500 mr-2" />
                        确认删除角色
                    </h3>
                    <!-- 确认删除的提示文本，显示待删除角色的名称 -->
                    <p class="mb-4 text-gray-600 dark:text-gray-300">
                        确定要删除角色 “<strong class="font-semibold text-gray-800 dark:text-gray-200">{{
                            roleToDelete?.display_name }}</strong>” ({{ roleToDelete?.name }}) 吗？
                    </p>
                    <!-- 强调删除操作不可恢复的警告信息 -->
                    <p class="text-sm text-red-600 dark:text-red-400"><font-awesome-icon
                            :icon="['fas', 'exclamation-circle']" class="mr-1" /> 此操作不可恢复，关联用户的此角色也将被移除。</p>
                </div>
            </template>
        </Modal>
        <!-- 闪现消息组件，用于显示全局通知 -->
        <FlashMessage ref="flashMessage" />
    </MainLayout>
</template>

<script setup>
// 导入Vue的ref函数，用于创建响应式引用
import { ref } from 'vue';
// 导入Inertia相关的组件和工具函数
// Head: 用于设置页面HTML head标签内容，如title
// Link: Inertia的链接组件，用于页面跳转
// router: Inertia的路由器实例，用于发起请求（如删除、更新）
// usePage: 用于访问当前页面的Inertia props
import { Head, Link, router, usePage } from '@inertiajs/vue3';
// 导入主布局组件
import MainLayout from "@/Layouts/MainLayouts/MainLayout.vue";
// 导入分页组件
import Pagination from '@/Components/Other/Pagination.vue';
// 导入模态框组件
import Modal from '@/Components/Modal/Modal.vue';
// 导入闪现消息组件
import FlashMessage from '@/Components/Other/FlashMessage.vue';
// 从配置中导入管理员导航链接
import { adminNavigationLinks } from '@/config/navigationConfig';
// 从工具函数中导入日期格式化函数
import { formatDate } from '@/utils/formatters';

// 设置导航链接为管理员导航链接
const navigationLinks = adminNavigationLinks;
// 获取当前页面的Inertia props，例如用户信息和权限
const pageProps = usePage().props;
// 创建一个ref，用于获取FlashMessage组件的实例，以便调用其方法显示消息
const flashMessage = ref(null);

// 定义组件的props，从父组件接收数据
const props = defineProps({
    // 角色数据，包含分页信息
    roles: {
        type: Object,
        required: true
    },
    // 用户是否拥有编辑角色的权限
    canEdit: {
        type: Boolean,
        default: false,
    },
    // 用户是否拥有删除角色的权限
    canDelete: {
        type: Boolean,
        default: false,
    }
});

// 定义一个响应式引用，控制删除确认模态框的显示状态
const confirmingDeletion = ref(false);
// 定义一个响应式引用，存储待删除的角色对象
const roleToDelete = ref(null);

/**
 * 确认删除操作：设置待删除角色并显示模态框
 * @param {Object} role - 要删除的角色对象
 */
const confirmDeleteAction = (role) => {
    // 系统角色（如'admin'）不允许通过此方式删除
    if (role.is_system) return;
    // 存储待删除的角色
    roleToDelete.value = role;
    // 显示确认删除模态框
    confirmingDeletion.value = true;
};

/**
 * 取消删除操作：关闭模态框并清空待删除角色
 */
const cancelDelete = () => {
    // 关闭确认删除模态框
    confirmingDeletion.value = false;
    // 清空待删除的角色对象
    roleToDelete.value = null;
};

/**
 * 执行删除角色操作：向后端发送DELETE请求
 */
const deleteRole = () => {
    // 确保有待删除的角色且不是系统角色
    if (roleToDelete.value && !roleToDelete.value.is_system) {
        // 使用Inertia的router发送DELETE请求
        router.delete(route('roles.destroy', roleToDelete.value.id), {
            preserveScroll: true, // 保留滚动位置
            // 请求成功回调
            onSuccess: () => {
                // Flash message通常由全局Inertia middleware处理，这里无需手动添加
            },
            // 请求失败回调
            onError: (errors) => {
                // 从错误对象中提取第一条错误信息，或使用默认消息
                const errorMsg = Object.values(errors).flat()[0] || '删除角色失败，请重试。';
                // 通过ref调用FlashMessage组件的addMessage方法显示错误通知
                if (flashMessage.value) {
                    flashMessage.value.addMessage('error', errorMsg);
                } else {
                    alert(errorMsg); // 如果FlashMessage组件不可用，则使用浏览器alert作为备用
                }
            },
            // 请求完成（无论成功或失败）回调
            onFinish: () => {
                cancelDelete(); // 无论结果如何，都关闭模态框
            }
        });
    }
};

// 暴露confirmDelete函数给模板使用
const confirmDelete = (role) => {
    confirmDeleteAction(role);
};
</script>

<style scoped>
/* 定义表格头部单元格的共享样式 */
.th-cell {
    @apply px-4 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap;
    /* 添加 nowrap 以防止标题换行 */
}

/* 定义表格数据单元格的共享样式 */
.td-cell {
    @apply px-4 py-4 text-sm;
}

/* 定义主要按钮的样式 */
.btn-primary {
    @apply px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm disabled:opacity-50;
}

/* 定义链接样式按钮的样式 */
.btn-link {
    @apply text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 hover:underline transition whitespace-nowrap;
    /* 添加 nowrap 以防止按钮文本换行 */
}

/* 模态框内容中段落的文本样式 */
.modal-content p {
    @apply text-gray-600 dark:text-gray-300;
}

/* 模态框内容中strong标签的文本样式 */
.modal-content strong {
    @apply font-semibold text-gray-800 dark:text-gray-200;
}
</style>