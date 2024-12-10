<template>
    <div class="container mx-auto py-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-900">系统日志</h1>
        </div>

        <!-- 筛选器 -->
        <div class="bg-white rounded-lg shadow mb-6 p-4">
            <form @submit.prevent="search">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- 操作类型 -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            操作类型
                        </label>
                        <select v-model="filters.action"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="">全部</option>
                            <option value="create">创建</option>
                            <option value="update">更新</option>
                            <option value="delete">删除</option>
                            <option value="publish">发布</option>
                            <option value="unpublish">取消发布</option>
                        </select>
                    </div>

                    <!-- 对象类型 -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            对象类型
                        </label>
                        <select v-model="filters.subject_type"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="">全部</option>
                            <option value="Template">模板</option>
                            <option value="Page">页面</option>
                            <option value="Role">角色</option>
                            <option value="User">用户</option>
                        </select>
                    </div>

                    <!-- 开始日期 -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            开始日期
                        </label>
                        <input type="date" v-model="filters.start_date"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <!-- 结束日期 -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            结束日期
                        </label>
                        <input type="date" v-model="filters.end_date"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>

                <div class="mt-4 flex justify-end space-x-3">
                    <button type="button" @click="resetFilters"
                        class="px-4 py-2 text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200">
                        重置
                    </button>
                    <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600">
                        搜索
                    </button>
                </div>
            </form>
        </div>

        <!-- 日志列表 -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            操作时间
                        </th>
                        <th scope="col"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            操作用户
                        </th>
                        <th scope="col"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            操作类型
                        </th>
                        <th scope="col"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            对象
                        </th>
                        <th scope="col"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            IP地址
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="log in logs.data" :key="log.id">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ log.created_at }}
                        </td>
                        <td class="px-6 py-4">
                            <div v-if="log.user" class="text-sm">
                                <div class="font-medium text-gray-900">{{ log.user.name }}</div>
                                <div class="text-gray-500">{{ log.user.email }}</div>
                            </div>
                            <div v-else class="text-sm text-gray-500">系统</div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                :class="getActionClass(log.action)">
                                {{ log.action_text }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm">
                                <div class="font-medium text-gray-900">{{ log.subject_type_text }}</div>
                                <div class="text-gray-500">ID: {{ log.subject_id }}</div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ log.ip_address }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- 分页 -->
        <Pagination :links="logs.links" class="mt-6" />
    </div>
</template>

<script setup>
import { reactive } from 'vue'
import { router } from '@inertiajs/vue3'
import Pagination from '@/Components/Other/Pagination.vue'

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

const filters = reactive({
    action: props.filters.action || '',
    subject_type: props.filters.subject_type || '',
    start_date: props.filters.start_date || '',
    end_date: props.filters.end_date || ''
})

const search = () => {
    router.get(route('activity-logs.index'), filters, {
        preserveState: true,
        preserveScroll: true
    })
}

const resetFilters = () => {
    Object.keys(filters).forEach(key => {
        filters[key] = ''
    })
    search()
}

const getActionClass = (action) => {
    const classes = {
        create: 'bg-green-100 text-green-800',
        update: 'bg-blue-100 text-blue-800',
        delete: 'bg-red-100 text-red-800',
        publish: 'bg-purple-100 text-purple-800',
        unpublish: 'bg-gray-100 text-gray-800'
    }
    return classes[action] || 'bg-gray-100 text-gray-800'
}
</script>