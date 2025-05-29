<template>
    <!-- 使用MainLayout作为页面布局，并传入导航链接数据 -->
    <MainLayout :navigationLinks="navigationLinks">

        <!-- 设置页面标题 -->

        <Head title="系统日志" />
        <!-- 主内容区域容器，居中并添加内边距 -->
        <div class="container mx-auto py-6 px-4">
            <!-- 日志展示卡片，包含背景、圆角和阴影样式 -->
            <div class="bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm rounded-lg shadow-lg p-6">
                <!-- 页面标题和可能的额外操作按钮区域 -->
                <div class="flex justify-between items-center mb-6 pb-4 border-b dark:border-gray-700">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">系统日志</h1>
                </div>

                <!-- 筛选表单区域 -->
                <div class="bg-gray-50 dark:bg-gray-800/50 rounded-lg shadow mb-6 p-4 border dark:border-gray-700">
                    <!-- 筛选表单，提交时调用search方法 -->
                    <form @submit.prevent="search">
                        <!-- 表单字段布局，响应式网格 -->
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 items-end">
                            <!-- 操作类型筛选 -->
                            <div>
                                <label for="action-filter"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    操作类型
                                </label>
                                <select id="action-filter" v-model="filters.action" class="select-field">
                                    <option value="">全部</option>
                                    <option value="create">创建</option>
                                    <option value="update">更新</option>
                                    <option value="delete">删除</option>
                                    <option value="publish">发布</option>
                                    <option value="unpublish">取消发布</option>
                                    <option value="revert">恢复版本</option>
                                    <option value="login">登录</option>
                                    <option value="logout">登出</option>
                                </select>
                            </div>
                            <!-- 对象类型筛选 -->
                            <div>
                                <label for="subject-type-filter"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    对象类型
                                </label>
                                <select id="subject-type-filter" v-model="filters.subject_type" class="select-field">
                                    <option value="">全部</option>
                                    <option value="App\Models\WikiPage">页面</option>
                                    <option value="App\Models\WikiCategory">分类</option>
                                    <option value="App\Models\WikiTag">标签</option>
                                    <option value="App\Models\WikiComment">评论</option>
                                    <option value="App\Models\User">用户</option>
                                    <option value="Spatie\Permission\Models\Role">角色</option>
                                </select>
                            </div>
                            <!-- 开始日期筛选 -->
                            <div>
                                <label for="start-date-filter"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    开始日期
                                </label>
                                <input id="start-date-filter" type="date" v-model="filters.start_date"
                                    class="input-field">
                            </div>
                            <!-- 结束日期筛选 -->
                            <div>
                                <label for="end-date-filter"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    结束日期
                                </label>
                                <input id="end-date-filter" type="date" v-model="filters.end_date" class="input-field">
                            </div>
                            <!-- 搜索和重置按钮 -->
                            <div class="flex space-x-2 justify-end">
                                <button type="button" @click="resetFilters" class="btn-secondary text-sm">
                                    重置
                                </button>
                                <button type="submit" class="btn-primary text-sm">
                                    搜索
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- 日志表格区域 -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left table-fixed">
                        <!-- 表头 -->
                        <thead class="bg-gray-100 dark:bg-gray-700/50">
                            <tr>
                                <th class="th-cell w-1/5">时间</th>
                                <th class="th-cell w-1/5">操作用户</th>
                                <th class="th-cell w-1/6">操作类型</th>
                                <th class="th-cell w-1/4">对象</th>
                                <th class="th-cell w-1/6">IP地址</th>
                            </tr>
                        </thead>
                        <!-- 表格内容 -->
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <!-- 如果没有日志记录，显示提示信息 -->
                            <tr v-if="logs.data.length === 0">
                                <td colspan="5" class="py-6 text-center text-gray-500 dark:text-gray-400 italic">
                                    没有找到日志记录。</td>
                            </tr>
                            <!-- 遍历logs.data数组，渲染每一条日志记录 -->
                            <tr v-for="log in logs.data" :key="log.id"
                                class="hover:bg-gray-50 dark:hover:bg-gray-800/30">
                                <!-- 时间列，显示格式化后的时间，并提供完整时间作为tooltip -->
                                <td class="td-cell text-gray-600 dark:text-gray-400 whitespace-nowrap"
                                    :title="formatDateTime(log.created_at)">
                                    {{ log.created_at }}
                                </td>
                                <!-- 操作用户列 -->
                                <td class="td-cell">
                                    <div v-if="log.user" class="flex items-center">
                                        <span class="font-medium text-gray-900 dark:text-gray-100">{{ log.user.name
                                        }}</span>
                                        <span class="text-gray-500 dark:text-gray-400 ml-1">({{ log.user.email
                                        }})</span>
                                    </div>
                                    <div v-else class="text-sm text-gray-500 dark:text-gray-400 italic">系统</div>
                                </td>
                                <!-- 操作类型列，根据操作类型动态应用样式和文本 -->
                                <td class="td-cell">
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                        :class="getActionClass(log.action)">
                                        {{ log.action_text }}
                                    </span>
                                </td>
                                <!-- 对象列，显示对象类型和ID，并根据类型显示对应图标 -->
                                <td class="td-cell">
                                    <div class="flex items-center">
                                        <font-awesome-icon :icon="getActivityIcon(log.subject_type_text)"
                                            class="mr-1.5 h-3 w-3 text-gray-400" />
                                        <span class="font-medium text-gray-700 dark:text-gray-300 mr-1">{{
                                            log.subject_type_text }}</span>
                                        <span class="text-gray-500 dark:text-gray-400">#{{ log.subject_id }}</span>
                                    </div>
                                </td>
                                <!-- IP地址列 -->
                                <td class="td-cell text-gray-600 dark:text-gray-400">
                                    {{ log.ip_address }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- 分页组件，传入从后端获取的链接数据 -->
                <Pagination :links="logs.links" class="mt-6" />
            </div>
        </div>
    </MainLayout>
</template>

<script setup>
// 导入reactive用于创建响应式数据，Head用于设置页面head信息，router用于页面跳转和状态管理
import { reactive } from 'vue'
import { Head, router } from '@inertiajs/vue3'
// 导入MainLayout作为页面整体布局
import MainLayout from '@/Layouts/MainLayouts/MainLayout.vue'
// 导入Pagination组件用于表格分页
import Pagination from '@/Components/Other/Pagination.vue'
// 导入formatDateTime工具函数，用于日期时间格式化
import { formatDateTime } from '@/utils/formatters';
// 导入adminNavigationLinks，用于MainLayout的导航链接
import { adminNavigationLinks } from '@/config/navigationConfig';

// 将管理员导航链接赋值给navigationLinks
const navigationLinks = adminNavigationLinks;

// 定义组件接收的props
const props = defineProps({
    // logs对象，包含日志数据和分页链接
    logs: {
        type: Object,
        required: true
    },
    // filters对象，包含当前应用的筛选条件
    filters: {
        type: Object,
        default: () => ({}) // 默认值为空对象
    }
})

// 创建一个响应式filters对象，用于绑定到表单输入，并初始化为props中传入的筛选值
const filters = reactive({
    action: props.filters.action || '', // 操作类型
    subject_type: props.filters.subject_type || '', // 对象类型
    start_date: props.filters.start_date || '', // 开始日期
    end_date: props.filters.end_date || '', // 结束日期
    user_id: props.filters.user_id || '', // 用户ID (如果需要用户筛选)
})

/**
 * 执行搜索/筛选操作。
 * 构建一个干净的筛选对象，移除空值，然后通过Inertia.js发送GET请求到当前路由。
 */
const search = () => {
    const activeFilters = {}; // 用于存储非空筛选条件的临时对象
    // 遍历filters对象，将有值的筛选条件添加到activeFilters
    for (const key in filters) {
        if (filters[key]) {
            activeFilters[key] = filters[key];
        }
    }
    // 使用Inertia的router.get方法发送请求
    router.get(route('activity-logs.index'), activeFilters, {
        preserveState: true, // 保留当前页面的滚动位置和表单状态
        preserveScroll: true, // 仅保留滚动位置
        replace: true, // 替换浏览器历史记录中的当前条目，避免堆积筛选历史
    })
}

/**
 * 重置所有筛选条件并执行搜索。
 */
const resetFilters = () => {
    // 将filters对象中的所有属性重置为空字符串
    Object.keys(filters).forEach(key => {
        filters[key] = ''
    })
    search() // 调用search方法执行一次空筛选，以清除后端已应用的筛选
}

/**
 * 根据日志操作类型返回对应的CSS类，用于视觉标识。
 * @param {string} action - 日志操作类型字符串。
 * @returns {string} 对应的CSS类名。
 */
const getActionClass = (action) => {
    const classes = {
        create: 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300', // 创建操作
        update: 'bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300', // 更新操作
        delete: 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300', // 删除操作 (软删除)
        publish: 'bg-purple-100 text-purple-800 dark:bg-purple-900/50 dark:text-purple-300', // 发布操作
        unpublish: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-300', // 取消发布操作
        login: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/50 dark:text-indigo-300', // 登录操作
        logout: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300', // 登出操作
        revert: 'bg-orange-100 text-orange-800 dark:bg-orange-900/50 dark:text-orange-300', // 恢复版本操作
        force_delete: 'bg-red-200 text-red-900 dark:bg-red-800 dark:text-red-200 font-semibold', // 永久删除操作 (更醒目)
        restore: 'bg-green-200 text-green-900 dark:bg-green-800 dark:text-green-200 font-semibold', // 恢复操作 (更醒目)
    }
    // 如果没有匹配的动作类型，返回一个默认样式
    return classes[action] || 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
}

/**
 * 根据日志对象类型返回对应的FontAwesome图标。
 * @param {string} subjectType - 日志对象类型字符串 (如"页面", "分类", "用户"等)。
 * @returns {Array} 包含FontAwesome图标前缀和名称的数组。
 */
const getActivityIcon = (subjectType) => {
    switch (subjectType) {
        case '页面': return ['fas', 'file-alt']; // 页面
        case '分类': return ['fas', 'folder']; // 分类
        case '标签': return ['fas', 'tag']; // 标签
        case '评论': return ['fas', 'comment']; // 评论
        case '用户': return ['fas', 'user']; // 用户
        case '角色': return ['fas', 'user-shield']; // 角色
        default: return ['fas', 'question-circle']; // 未知类型
    }
}
</script>

<style scoped>
/* 表格头单元格的共享样式 */
.th-cell {
    @apply px-4 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wider;
}

/* 表格数据单元格的共享样式 */
.td-cell {
    @apply px-4 py-3 text-sm;
}

/* 输入框和选择框的共享样式 */
.input-field,
.select-field {
    @apply w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 dark:placeholder-gray-400 text-sm;
}

/* 主要按钮样式 */
.btn-primary {
    @apply px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm disabled:opacity-50;
}

/* 次要按钮样式 */
.btn-secondary {
    @apply px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition dark:bg-gray-600 dark:text-gray-200 dark:hover:bg-gray-500 text-sm;
}
</style>