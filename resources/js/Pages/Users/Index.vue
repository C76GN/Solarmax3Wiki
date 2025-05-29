<template>
    <!-- 主要布局容器，传入管理员导航链接 -->
    <MainLayout :navigationLinks="navigationLinks">

        <!-- 设置页面标题 -->

        <Head title="用户管理" />
        <div class="container mx-auto py-6 px-4">
            <!-- 页面内容主体卡片 -->
            <div class="bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm rounded-lg shadow-lg p-6">
                <!-- 页面头部，包含标题 -->
                <div class="flex justify-between items-center mb-6 pb-4 border-b dark:border-gray-700">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">用户管理</h1>
                    <!-- 根据权限动态显示“创建用户”按钮（此处未显示，但结构保留） -->
                </div>

                <div class="overflow-x-auto">
                    <!-- 用户列表表格 -->
                    <table class="w-full text-left">
                        <!-- 表格头部 -->
                        <thead class="bg-gray-100 dark:bg-gray-700/50">
                            <tr>
                                <!-- 用户信息列，占据更多空间 -->
                                <th class="th-cell w-2/5">用户信息</th>
                                <!-- 角色列，占据更多空间 -->
                                <th class="th-cell w-2/5">角色</th>
                                <!-- 注册时间列，宽度自适应 -->
                                <th class="th-cell w-auto">注册时间</th>
                                <!-- 操作列，宽度自适应，右对齐 -->
                                <th class="th-cell w-auto text-right pr-6">操作</th>
                            </tr>
                        </thead>
                        <!-- 表格主体，行之间有分隔线 -->
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <!-- 如果没有用户数据，显示提示信息 -->
                            <tr v-if="users.data.length === 0">
                                <td colspan="4" class="py-6 text-center text-gray-500 dark:text-gray-400 italic">
                                    没有找到任何用户。</td>
                            </tr>
                            <!-- 遍历用户数据，为每个用户创建一行 -->
                            <tr v-for="user in users.data" :key="user.id"
                                class="hover:bg-gray-50 dark:hover:bg-gray-800/30">
                                <!-- 用户信息单元格 -->
                                <td class="td-cell align-top">
                                    <div class="flex items-center">
                                        <div>
                                            <div class="font-medium text-gray-900 dark:text-gray-100">{{ user.name }}
                                            </div>
                                            <!-- 用户邮箱，允许换行 -->
                                            <div class="text-xs text-gray-500 dark:text-gray-400 break-all">{{
                                                user.email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <!-- 角色信息单元格 -->
                                <td class="td-cell align-top">
                                    <div class="flex flex-wrap gap-1">
                                        <!-- 如果用户没有角色，显示提示 -->
                                        <span v-if="user.roles.length === 0"
                                            class="text-gray-500 dark:text-gray-400 italic text-xs">无角色</span>
                                        <!-- 遍历用户角色，并为每个角色显示一个标签 -->
                                        <span v-for="role in user.roles" :key="role.id"
                                            class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium whitespace-nowrap"
                                            :class="role.is_system
                                                ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300' // 系统角色样式
                                                : 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300'">
                                            <!-- 普通角色样式 -->
                                            {{ role.display_name }}
                                        </span>
                                    </div>
                                </td>
                                <!-- 注册时间单元格，顶部对齐，不换行 -->
                                <td class="td-cell align-top text-gray-600 dark:text-gray-400 whitespace-nowrap">
                                    {{ formatDate(user.created_at) }}
                                </td>
                                <!-- 操作单元格，顶部对齐，右对齐，不换行 -->
                                <td class="td-cell align-top text-right pr-6 whitespace-nowrap">
                                    <!-- 如果用户有编辑权限，显示“编辑角色”链接 -->
                                    <Link v-if="$page.props.auth.user.permissions.includes('user.edit')"
                                        :href="route('users.edit', user.id)" class="btn-link text-xs">
                                    <font-awesome-icon :icon="['fas', 'edit']" class="mr-1" /> 编辑角色
                                    </Link>
                                    <!-- 否则显示“无操作权限”提示 -->
                                    <span v-else class="text-xs text-gray-400 italic">无操作权限</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <!-- 分页组件 -->
                <Pagination :links="users.links" class="mt-6" />
            </div>
        </div>
    </MainLayout>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3';
import MainLayout from "@/Layouts/MainLayouts/MainLayout.vue";
import Pagination from '@/Components/Other/Pagination.vue';
import { adminNavigationLinks } from '@/config/navigationConfig';
import { formatDate } from '@/utils/formatters';

// 导入管理员导航链接配置
const navigationLinks = adminNavigationLinks;

// 定义组件接收的属性
defineProps({
    users: {
        type: Object, // users 对象包含数据和分页链接
        required: true
    }
})
</script>

<style scoped>
/* 表格头部单元格共享样式 */
.th-cell {
    @apply px-4 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap;
    /* 标题文本不换行 */
}

/* 表格数据单元格共享样式 */
.td-cell {
    @apply px-4 py-4 text-sm;
}

/* 链接按钮样式 */
.btn-link {
    @apply text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 hover:underline transition whitespace-nowrap;
    /* 按钮文本不换行 */
}
</style>