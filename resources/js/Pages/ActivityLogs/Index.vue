<template>
    <MainLayout :navigationLinks="navigationLinks">

        <Head title="系统日志" />
        <div class="container mx-auto py-6 px-4">
            <div class="bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm rounded-lg shadow-lg p-6">
                <div class="flex justify-between items-center mb-6 pb-4 border-b dark:border-gray-700">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">系统日志</h1>
                    <!-- 可以添加操作按钮，例如清除日志（如果需要） -->
                </div>

                <!-- Filter Form -->
                <div class="bg-gray-50 dark:bg-gray-800/50 rounded-lg shadow mb-6 p-4 border dark:border-gray-700">
                    <form @submit.prevent="search">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 items-end">
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
                                    <!-- Add more actions as needed -->
                                </select>
                            </div>
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
                                    <!-- Add more models as needed -->
                                </select>
                            </div>
                            <div>
                                <label for="start-date-filter"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    开始日期
                                </label>
                                <input id="start-date-filter" type="date" v-model="filters.start_date"
                                    class="input-field">
                            </div>
                            <div>
                                <label for="end-date-filter"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    结束日期
                                </label>
                                <input id="end-date-filter" type="date" v-model="filters.end_date" class="input-field">
                            </div>
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

                <!-- Logs Table -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left table-fixed">
                        <thead class="bg-gray-100 dark:bg-gray-700/50">
                            <tr>
                                <th class="th-cell w-1/5">时间</th>
                                <th class="th-cell w-1/5">操作用户</th>
                                <th class="th-cell w-1/6">操作类型</th>
                                <th class="th-cell w-1/4">对象</th>
                                <th class="th-cell w-1/6">IP地址</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-if="logs.data.length === 0">
                                <td colspan="5" class="py-6 text-center text-gray-500 dark:text-gray-400 italic">
                                    没有找到日志记录。</td>
                            </tr>
                            <tr v-for="log in logs.data" :key="log.id"
                                class="hover:bg-gray-50 dark:hover:bg-gray-800/30">
                                <td class="td-cell text-gray-600 dark:text-gray-400 whitespace-nowrap"
                                    :title="formatDateTime(log.created_at)">
                                    {{ log.created_at }} <!-- 使用后端格式化的相对时间 -->
                                </td>
                                <td class="td-cell">
                                    <div v-if="log.user" class="flex items-center">
                                        <!-- Add avatar if available -->
                                        <span class="font-medium text-gray-900 dark:text-gray-100">{{ log.user.name
                                            }}</span>
                                        <span class="text-gray-500 dark:text-gray-400 ml-1">({{ log.user.email
                                            }})</span>
                                    </div>
                                    <div v-else class="text-sm text-gray-500 dark:text-gray-400 italic">系统</div>
                                </td>
                                <td class="td-cell">
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                        :class="getActionClass(log.action)">
                                        {{ log.action_text }}
                                    </span>
                                </td>
                                <td class="td-cell">
                                    <div class="flex items-center">
                                        <font-awesome-icon :icon="getActivityIcon(log.subject_type_text)"
                                            class="mr-1.5 h-3 w-3 text-gray-400" />
                                        <span class="font-medium text-gray-700 dark:text-gray-300 mr-1">{{
                                            log.subject_type_text }}</span>
                                        <span class="text-gray-500 dark:text-gray-400">#{{ log.subject_id }}</span>
                                        <!-- 可选：如果subject有链接，显示链接 -->
                                    </div>
                                </td>
                                <td class="td-cell text-gray-600 dark:text-gray-400">
                                    {{ log.ip_address }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <Pagination :links="logs.links" class="mt-6" />
            </div>
        </div>
    </MainLayout>
</template>

<script setup>
import { reactive } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayouts/MainLayout.vue'
import Pagination from '@/Components/Other/Pagination.vue'
import { formatDateTime } from '@/utils/formatters';
import { adminNavigationLinks } from '@/config/navigationConfig'; // 假设导航配置放在这里

const navigationLinks = adminNavigationLinks; // 使用管理员导航

const props = defineProps({
    logs: {
        type: Object,
        required: true
    },
    filters: {
        type: Object,
        default: () => ({})
    }
})

// Reactive filters object bound to the form inputs
const filters = reactive({
    action: props.filters.action || '',
    subject_type: props.filters.subject_type || '',
    start_date: props.filters.start_date || '',
    end_date: props.filters.end_date || '',
    user_id: props.filters.user_id || '', // Added user filter if needed
})

// Function to trigger search/filtering
const search = () => {
    // Create a clean filters object, removing empty values
    const activeFilters = {};
    for (const key in filters) {
        if (filters[key]) {
            activeFilters[key] = filters[key];
        }
    }
    router.get(route('activity-logs.index'), activeFilters, {
        preserveState: true,
        preserveScroll: true,
        replace: true, // Use replace to avoid bloating browser history with filter changes
    })
}

// Function to reset all filters and perform search
const resetFilters = () => {
    Object.keys(filters).forEach(key => {
        filters[key] = ''
    })
    search() // Perform search with empty filters
}

// Function to get CSS class based on log action for visual indication
const getActionClass = (action) => {
    const classes = {
        create: 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300',
        update: 'bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300',
        delete: 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300',
        publish: 'bg-purple-100 text-purple-800 dark:bg-purple-900/50 dark:text-purple-300',
        unpublish: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-300',
        login: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/50 dark:text-indigo-300',
        logout: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
        revert: 'bg-orange-100 text-orange-800 dark:bg-orange-900/50 dark:text-orange-300',
        force_delete: 'bg-red-200 text-red-900 dark:bg-red-800 dark:text-red-200 font-semibold',
        restore: 'bg-green-200 text-green-900 dark:bg-green-800 dark:text-green-200 font-semibold',
    }
    return classes[action] || 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' // Default style
}

// Function to get FontAwesome icon based on subject type
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
}
</script>

<style scoped>
/* Shared styles for table cells */
.th-cell {
    @apply px-4 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wider;
}

.td-cell {
    @apply px-4 py-3 text-sm;
}

/* Shared form field styles */
.input-field,
.select-field {
    @apply w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 dark:placeholder-gray-400 text-sm;
}

/* Button styles */
.btn-primary {
    @apply px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm disabled:opacity-50;
}

.btn-secondary {
    @apply px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition dark:bg-gray-600 dark:text-gray-200 dark:hover:bg-gray-500 text-sm;
}
</style>