<template>
    <MainLayout
        :navigationLinks="[{ href: '/gamewiki', label: '游戏维基' }, { href: '#', label: '游戏历史&名人墙' }, { href: '#', label: '自制专区' }, { href: '#', label: '攻略专区' }, { href: '#', label: '论坛' }]"
        :show-dropdown="true">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white/80 backdrop-blur-sm shadow-lg rounded-lg overflow-hidden">
                <div class="p-6">
                    <!-- 标题和操作按钮 -->
                    <div class="mb-6 flex justify-between items-center">
                        <h2 class="text-2xl font-semibold text-gray-800">
                            页面管理
                        </h2>
                        <Link v-if="can.create_page" :href="route('pages.create')"
                            class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded transition duration-150 ease-in-out">
                        创建新页面
                        </Link>
                    </div>

                    <!-- 搜索和筛选 -->
                    <div class="bg-gray-50 p-4 rounded-lg mb-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            <!-- 搜索框 -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    搜索
                                </label>
                                <input type="text" v-model="form.search"
                                    class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="搜索标题或 slug...">
                            </div>

                            <!-- 状态筛选 -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    状态
                                </label>
                                <select v-model="form.status"
                                    class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">全部</option>
                                    <option value="draft">草稿</option>
                                    <option value="published">已发布</option>
                                    <option value="archived">已归档</option>
                                </select>
                            </div>

                            <!-- 模板筛选 -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    模板
                                </label>
                                <select v-model="form.template_id"
                                    class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">全部</option>
                                    <option v-for="template in templates" :key="template.id" :value="template.id">
                                        {{ template.name }}
                                    </option>
                                </select>
                            </div>

                            <!-- 日期范围 -->
                            <div class="space-y-2">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        开始日期
                                    </label>
                                    <input type="date" v-model="form.from_date"
                                        class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        结束日期
                                    </label>
                                    <input type="date" v-model="form.to_date"
                                        class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                </div>
                            </div>
                        </div>

                        <!-- 筛选操作按钮 -->
                        <div class="mt-4 flex justify-end space-x-3">
                            <button @click="resetFilters"
                                class="px-4 py-2 text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200 transition duration-150 ease-in-out">
                                重置
                            </button>
                            <button @click="search"
                                class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 transition duration-150 ease-in-out">
                                搜索
                            </button>
                        </div>
                    </div>

                    <!-- 页面列表 -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th v-for="column in columns" :key="column.key" scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer"
                                        @click="sort(column.key)">
                                        <div class="flex items-center">
                                            {{ column.label }}
                                            <template v-if="form.sort === column.key">
                                                <svg v-if="form.direction === 'asc'" class="ml-2 h-4 w-4" fill="none"
                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M5 15l7-7 7 7" />
                                                </svg>
                                                <svg v-else class="ml-2 h-4 w-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M19 9l-7 7-7-7" />
                                                </svg>
                                            </template>
                                        </div>
                                    </th>
                                    <th scope="col" class="relative px-6 py-3">
                                        <span class="sr-only">操作</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="page in pages.data" :key="page.id">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center">
                                            <div class="text-sm font-medium text-gray-900">
                                                {{ page.title }}
                                            </div>
                                        </div>
                                        <div class="text-sm text-gray-500">{{ page.slug }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span :class="[
                                            'px-2 py-1 text-xs font-medium rounded-full',
                                            {
                                                'bg-gray-100 text-gray-800': page.status === 'draft',
                                                'bg-green-100 text-green-800': page.status === 'published',
                                                'bg-yellow-100 text-yellow-800': page.status === 'archived'
                                            }
                                        ]">
                                            {{ getStatusText(page.status) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">
                                        {{ page.template.name }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">
                                        {{ page.view_count }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">
                                        <div>
                                            {{ formatDateTime(page.last_edited_at) }}
                                        </div>
                                        <div>
                                            {{ page.last_editor?.name }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-right text-sm font-medium space-x-2">
                                        <Link :href="route('pages.show', page.id)"
                                            class="text-blue-600 hover:text-blue-900">
                                        查看
                                        </Link>
                                        <Link v-if="can.edit_page" :href="route('pages.edit', page.id)"
                                            class="text-blue-600 hover:text-blue-900">
                                        编辑
                                        </Link>
                                        <button v-if="can.delete_page" @click="confirmDelete(page)"
                                            class="text-red-600 hover:text-red-900">
                                            删除
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- 分页 -->
                    <div v-if="pages.links" class="mt-6">
                        <Pagination :links="pages.links" />
                    </div>
                </div>
            </div>
        </div>

        <!-- 确认删除对话框 -->
        <ConfirmationModal :show="confirmingDeletion" title="确认删除页面" message="确定要删除该页面吗？此操作不可恢复。"
            @close="confirmingDeletion = false" @confirm="deletePage" />

        <!-- 消息提示 -->
        <FlashMessage ref="flash" />
    </MainLayout>
</template>

<script setup>
import { ref, reactive, watch } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayouts/MainLayout.vue'
import Pagination from '@/Components/Other/Pagination.vue'
import ConfirmationModal from '@/Components/Modal/ConfirmationModal.vue'
import FlashMessage from '@/Components/Other/FlashMessage.vue'

const props = defineProps({
    pages: Object,
    templates: Array,
    filters: Object,
    can: Object
})

// 表格列定义
const columns = [
    { key: 'title', label: '标题' },
    { key: 'status', label: '状态' },
    { key: 'template', label: '模板' },
    { key: 'view_count', label: '浏览量' },
    { key: 'last_edited_at', label: '最后编辑' }
]

// 表单数据
const form = reactive({
    search: props.filters.search || '',
    status: props.filters.status || '',
    template_id: props.filters.template_id || '',
    from_date: props.filters.from_date || '',
    to_date: props.filters.to_date || '',
    sort: props.filters.sort || 'created_at',
    direction: props.filters.direction || 'desc'
})

// 删除确认
const confirmingDeletion = ref(false)
const pageToDelete = ref(null)

// 方法定义
const confirmDelete = (page) => {
    pageToDelete.value = page
    confirmingDeletion.value = true
}

const deletePage = () => {
    if (pageToDelete.value) {
        router.delete(route('pages.destroy', pageToDelete.value.id), {
            onSuccess: () => {
                confirmingDeletion.value = false
                pageToDelete.value = null
            }
        })
    }
}

const search = () => {
    router.get(route('pages.index'), form, {
        preserveState: true,
        preserveScroll: true
    })
}

const resetFilters = () => {
    form.search = ''
    form.status = ''
    form.template_id = ''
    form.from_date = ''
    form.to_date = ''
    form.sort = 'created_at'
    form.direction = 'desc'
    search()
}

const sort = (column) => {
    if (form.sort === column) {
        form.direction = form.direction === 'asc' ? 'desc' : 'asc'
    } else {
        form.sort = column
        form.direction = 'asc'
    }
    search()
}

const getStatusText = (status) => {
    const statusMap = {
        draft: '草稿',
        published: '已发布',
        archived: '已归档'
    }
    return statusMap[status] || status
}

const formatDateTime = (datetime) => {
    if (!datetime) return ''
    return new Date(datetime).toLocaleString('zh-CN', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}

// 监听表单变化
watch(
    () => [form.search, form.status, form.template_id, form.from_date, form.to_date],
    () => {
        search()
    }
)
</script>