<template>

    <Head title="仪表盘" />

    <!-- 使用 AuthenticatedLayout 作为页面骨架 -->
    <AuthenticatedLayout>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <h2 class="text-2xl font-semibold mb-6">欢迎回来, {{ $page.props.auth.user.name }}!</h2>

                        <!-- 仅管理员可见内容，通过 is_admin 属性控制 -->
                        <div v-if="is_admin">
                            <!-- 核心统计数据卡片布局 -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                                <!-- 注册用户统计卡片 -->
                                <div class="bg-blue-100 dark:bg-blue-900/50 p-4 rounded-lg shadow flex items-center">
                                    <font-awesome-icon :icon="['fas', 'users']" class="text-blue-500 text-3xl mr-4" />
                                    <div>
                                        <div class="text-sm text-blue-700 dark:text-blue-300">注册用户</div>
                                        <div class="text-2xl font-bold text-blue-900 dark:text-blue-100">{{
                                            stats.user_count ?? 'N/A' }}</div>
                                    </div>
                                </div>
                                <!-- 已发布页面统计卡片 -->
                                <div class="bg-green-100 dark:bg-green-900/50 p-4 rounded-lg shadow flex items-center">
                                    <font-awesome-icon :icon="['fas', 'file-alt']"
                                        class="text-green-500 text-3xl mr-4" />
                                    <div>
                                        <div class="text-sm text-green-700 dark:text-green-300">已发布页面</div>
                                        <div class="text-2xl font-bold text-green-900 dark:text-green-100">{{
                                            stats.page_count ?? 'N/A' }}</div>
                                    </div>
                                </div>
                                <!-- Wiki 分类数统计卡片 -->
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
                            </div>

                            <!-- 快捷入口区域 -->
                            <div class="mb-8">
                                <h3 class="text-xl font-semibold mb-4">快捷入口</h3>
                                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
                                    <!-- 遍历 quickLinks 并创建链接 -->
                                    <Link v-for="link in quickLinks" :key="link.routeName" :href="route(link.routeName)"
                                        class="bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 p-4 rounded-lg text-center transition duration-150 ease-in-out flex flex-col items-center justify-center">
                                    <font-awesome-icon :icon="link.icon"
                                        class="text-2xl mb-2 text-gray-600 dark:text-gray-400" />
                                    <span class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ link.name
                                    }}</span>
                                    </Link>
                                </div>
                            </div>

                            <!-- 最近活动区域，通过 canViewLogs 权限控制显示 -->
                            <div v-if="canViewLogs">
                                <h3 class="text-xl font-semibold mb-4">最近活动</h3>
                                <div class="overflow-x-auto">
                                    <!-- 活动日志表格 -->
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
                                            <!-- 如果没有活动记录，显示提示信息 -->
                                            <tr v-if="recent_activities.length === 0">
                                                <td colspan="4"
                                                    class="px-4 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                                                    暂无活动记录</td>
                                            </tr>
                                            <!-- 遍历并显示最近的活动记录 -->
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
                                                        <!-- 根据对象类型显示对应图标 -->
                                                        <font-awesome-icon
                                                            :icon="getActivityIcon(activity.subject_type_text)"
                                                            class="mr-1.5 text-gray-400" />
                                                        <!-- 如果有对象链接，则将对象名称设为链接 -->
                                                        <span v-if="activity.subject_link">
                                                            <Link :href="activity.subject_link"
                                                                class="text-blue-600 hover:underline">{{
                                                                    activity.subject_type_text }} #{{ activity.subject_id }}
                                                            </Link>
                                                        </span>
                                                        <!-- 否则只显示对象名称和ID -->
                                                        <span v-else>
                                                            {{ activity.subject_type_text }} #{{ activity.subject_id }}
                                                        </span>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <!-- 查看所有日志的链接 -->
                                <div class="mt-4 text-right">
                                    <Link :href="route('activity-logs.index')"
                                        class="text-sm text-blue-600 dark:text-blue-400 hover:underline">
                                    查看所有日志 →
                                    </Link>
                                </div>
                            </div>
                        </div>
                        <!-- 非管理员用户可见内容 -->
                        <div v-else>
                            <p>这里是你的仪表盘。你可以从这里访问你的个人资料或开始浏览 Wiki。</p>
                            <!-- 非管理员用户的常用链接 -->
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

<script setup>
import AuthenticatedLayout from '@/Layouts/UserLayouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { formatDate, formatDateTime } from '@/utils/formatters'; // 导入日期时间格式化工具函数

// 定义组件接收的 props
const props = defineProps({
    stats: {
        type: Object,
        default: () => ({}) // 网站统计数据，如用户数、页面数等
    },
    recent_activities: {
        type: Array,
        default: () => [] // 最近的活动日志列表
    },
    is_admin: {
        type: Boolean,
        default: false, // 标识当前用户是否为管理员
    }
});

// 获取当前登录用户的权限列表，用于控制页面元素的显示
const authUserPermissions = computed(() => usePage().props.auth.user?.permissions || []);

// 检查用户是否拥有查看用户列表的权限
const canViewUsers = computed(() => authUserPermissions.value.includes('user.view'));
// 检查用户是否拥有查看角色列表的权限
const canViewRoles = computed(() => authUserPermissions.value.includes('role.view'));
// 检查用户是否拥有查看系统日志的权限
const canViewLogs = computed(() => authUserPermissions.value.includes('log.view'));
// 检查用户是否拥有管理 Wiki 内容的权限（包括分类、标签、创建页面等）
const canManageWikiContent = computed(() =>
    authUserPermissions.value.includes('wiki.manage_categories') ||
    authUserPermissions.value.includes('wiki.manage_tags') ||
    authUserPermissions.value.includes('wiki.create')
);

// 仪表盘上的快捷链接配置
const quickLinks = computed(() => [
    { name: '用户管理', routeName: 'users.index', icon: ['fas', 'users'], permission: 'user.view' },
    { name: '角色管理', routeName: 'roles.index', icon: ['fas', 'user-shield'], permission: 'role.view' },
    { name: 'Wiki页面列表', routeName: 'wiki.index', icon: ['fas', 'file-alt'] }, // 默认所有登录用户都可以查看 Wiki 列表
    { name: '创建Wiki页面', routeName: 'wiki.create', icon: ['fas', 'plus-circle'], permission: 'wiki.create' },
    { name: '分类管理', routeName: 'wiki.categories.index', icon: ['fas', 'folder-open'], permission: 'wiki.manage_categories' },
    { name: '标签管理', routeName: 'wiki.tags.index', icon: ['fas', 'tags'], permission: 'wiki.manage_tags' },
    { name: '活动日志', routeName: 'activity-logs.index', icon: ['fas', 'clipboard-list'], permission: 'log.view' },
].filter(link => !link.permission || authUserPermissions.value.includes(link.permission))); // 过滤掉用户没有权限的链接


/**
 * 根据活动日志中的对象类型返回对应的 Font Awesome 图标
 * @param {string} subjectType - 活动日志中的对象类型文本
 * @returns {Array} - Font Awesome 图标数组
 */
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

<style scoped>
/* 可以根据需要添加组件特有的样式 */
</style>