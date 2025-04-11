<script setup>
import AuthenticatedLayout from '@/Layouts/UserLayouts/AuthenticatedLayout.vue'; // 或使用 MainLayout，取决于你的路由保护
// import MainLayout from '@/Layouts/MainLayouts/MainLayout.vue';
import { Head, Link,usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { formatDate, formatDateTime } from '@/utils/formatters'; // 假设你有这个工具函数

const props = defineProps({
    stats: {
        type: Object,
        default: () => ({})
    },
    recent_activities: {
        type: Array,
        default: () => []
    },
    is_admin: {
        type: Boolean,
        default: false, // 根据后端传递的值
    }
});

// 获取用户权限，用于控制显示内容
const authUserPermissions = computed(() => usePage().props.auth.user?.permissions || []);

const canViewUsers = computed(() => authUserPermissions.value.includes('user.view'));
const canViewRoles = computed(() => authUserPermissions.value.includes('role.view'));
const canViewLogs = computed(() => authUserPermissions.value.includes('log.view'));
const canManageWikiContent = computed(() =>
    authUserPermissions.value.includes('wiki.manage_categories') ||
    authUserPermissions.value.includes('wiki.manage_tags') ||
    authUserPermissions.value.includes('wiki.create')
);

// 快捷链接
const quickLinks = computed(() => [
    { name: '用户管理', routeName: 'users.index', icon: ['fas', 'users'], permission: 'user.view' },
    { name: '角色管理', routeName: 'roles.index', icon: ['fas', 'user-shield'], permission: 'role.view' },
    { name: 'Wiki页面列表', routeName: 'wiki.index', icon: ['fas', 'file-alt'] }, // 假设所有登录用户都能看列表
    { name: '创建Wiki页面', routeName: 'wiki.create', icon: ['fas', 'plus-circle'], permission: 'wiki.create' },
    { name: '分类管理', routeName: 'wiki.categories.index', icon: ['fas', 'folder-open'], permission: 'wiki.manage_categories' },
    { name: '标签管理', routeName: 'wiki.tags.index', icon: ['fas', 'tags'], permission: 'wiki.manage_tags' },
    { name: '活动日志', routeName: 'activity-logs.index', icon: ['fas', 'clipboard-list'], permission: 'log.view' },
].filter(link => !link.permission || authUserPermissions.value.includes(link.permission)));


// 根据活动类型获取图标
const getActivityIcon = (subjectType) => {
    switch (subjectType) {
        case '页面': return ['fas', 'file-alt'];
        case '分类': return ['fas', 'folder'];
        case '标签': return ['fas', 'tag'];
        case '评论': return ['fas', 'comment'];
        case '用户': return ['fas', 'user'];
        case '角色': return ['fas', 'user-shield'];
        default: return ['fas', 'question-circle'];
    }
};

</script>

<template>

    <Head title="仪表盘" />

    <!-- 根据你的认证布局选择 AuthenticatedLayout 或 MainLayout -->
    <AuthenticatedLayout>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <h2 class="text-2xl font-semibold mb-6">欢迎回来, {{ $page.props.auth.user.name }}!</h2>

                        <!-- 仅管理员可见内容 -->
                        <div v-if="is_admin">
                            <!-- 统计卡片 -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                                <div class="bg-blue-100 dark:bg-blue-900/50 p-4 rounded-lg shadow flex items-center">
                                    <font-awesome-icon :icon="['fas', 'users']" class="text-blue-500 text-3xl mr-4" />
                                    <div>
                                        <div class="text-sm text-blue-700 dark:text-blue-300">注册用户</div>
                                        <div class="text-2xl font-bold text-blue-900 dark:text-blue-100">{{
                                            stats.user_count ?? 'N/A' }}</div>
                                    </div>
                                </div>
                                <div class="bg-green-100 dark:bg-green-900/50 p-4 rounded-lg shadow flex items-center">
                                    <font-awesome-icon :icon="['fas', 'file-alt']"
                                        class="text-green-500 text-3xl mr-4" />
                                    <div>
                                        <div class="text-sm text-green-700 dark:text-green-300">已发布页面</div>
                                        <div class="text-2xl font-bold text-green-900 dark:text-green-100">{{
                                            stats.page_count ?? 'N/A' }}</div>
                                    </div>
                                </div>
                                <div
                                    class="bg-purple-100 dark:bg-purple-900/50 p-4 rounded-lg shadow flex items-center">
                                    <font-awesome-icon :icon="['fas', 'folder-open']"
                                        class="text-purple-500 text-3xl mr-4" />
                                    <div>
                                        <div class="text-sm text-purple-700 dark:text-purple-300">Wiki分类数</div>
                                        <div class="text-2xl font-bold text-purple-900 dark:text-purple-100">{{
                                            stats.category_count ?? 'N/A' }}</div>
                                    </div>
                                </div>
                                <!-- 可以添加更多卡片 -->
                            </div>

                            <!-- 快捷入口 -->
                            <div class="mb-8">
                                <h3 class="text-xl font-semibold mb-4">快捷入口</h3>
                                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
                                    <Link v-for="link in quickLinks" :key="link.routeName" :href="route(link.routeName)"
                                        class="bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 p-4 rounded-lg text-center transition duration-150 ease-in-out flex flex-col items-center justify-center">
                                    <font-awesome-icon :icon="link.icon"
                                        class="text-2xl mb-2 text-gray-600 dark:text-gray-400" />
                                    <span class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ link.name
                                        }}</span>
                                    </Link>
                                </div>
                            </div>


                            <!-- 最近活动 -->
                            <div v-if="canViewLogs">
                                <h3 class="text-xl font-semibold mb-4">最近活动</h3>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                        <thead class="bg-gray-50 dark:bg-gray-700/50">
                                            <tr>
                                                <th scope="col"
                                                    class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                    时间</th>
                                                <th scope="col"
                                                    class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                    用户</th>
                                                <th scope="col"
                                                    class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                    操作</th>
                                                <th scope="col"
                                                    class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                    对象</th>
                                            </tr>
                                        </thead>
                                        <tbody
                                            class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                            <tr v-if="recent_activities.length === 0">
                                                <td colspan="4"
                                                    class="px-4 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                                                    暂无活动记录</td>
                                            </tr>
                                            <tr v-for="activity in recent_activities" :key="activity.id"
                                                class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400"
                                                    :title="formatDateTime(activity.created_at)">{{
                                                    activity.created_at_relative }}</td>
                                                <td
                                                    class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-200">
                                                    {{ activity.user_name }}</td>
                                                <td
                                                    class="px-4 py-3 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">
                                                    {{ activity.action_text }}</td>
                                                <td
                                                    class="px-4 py-3 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">
                                                    <div class="flex items-center">
                                                        <font-awesome-icon
                                                            :icon="getActivityIcon(activity.subject_type_text)"
                                                            class="mr-1.5 text-gray-400" />
                                                        <span v-if="activity.subject_link">
                                                            <Link :href="activity.subject_link"
                                                                class="text-blue-600 hover:underline">{{
                                                            activity.subject_type_text }} #{{ activity.subject_id }}
                                                            </Link>
                                                        </span>
                                                        <span v-else>
                                                            {{ activity.subject_type_text }} #{{ activity.subject_id }}
                                                        </span>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="mt-4 text-right">
                                    <Link :href="route('activity-logs.index')"
                                        class="text-sm text-blue-600 dark:text-blue-400 hover:underline">
                                    查看所有日志 →
                                    </Link>
                                </div>
                            </div>
                        </div>
                        <!-- 普通用户可见内容 -->
                        <div v-else>
                            <p>这里是你的仪表盘。你可以从这里访问你的个人资料或开始浏览 Wiki。</p>
                            <!-- 可以添加一些非管理员用户的常用链接 -->
                            <div class="mt-4 space-x-4">
                                <Link :href="route('profile.edit')" class="text-blue-600 hover:underline">编辑个人资料</Link>
                                <Link :href="route('wiki.index')" class="text-blue-600 hover:underline">前往 Wiki</Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* 可以添加一些额外的 scoped 样式 */
</style>