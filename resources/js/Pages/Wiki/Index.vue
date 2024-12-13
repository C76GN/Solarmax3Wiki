<template>
    <MainLayout
        :navigationLinks="[{ href: '/wiki', label: '游戏维基' }, { href: '#', label: '游戏历史&名人墙' }, { href: '#', label: '自制专区' }, { href: '#', label: '攻略专区' }, { href: '#', label: '论坛' }]"
        :show-dropdown="true">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white/80 backdrop-blur-sm shadow-lg rounded-lg overflow-hidden">
                <div class="p-6">
                    <!-- 标题和操作按钮 -->
                    <div class="mb-6 flex justify-between items-center">
                        <h2 class="text-2xl font-semibold text-gray-800">Wiki 文章管理</h2>
                        <Link v-if="can.create_article" :href="route('wiki.create')"
                            class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded transition duration-150 ease-in-out">
                        创建新文章
                        </Link>
                    </div>

                    <!-- 搜索和筛选 -->
                    <div class="mb-6 flex gap-4 bg-gray-50 p-4 rounded-lg">
                        <div class="flex-1">
                            <input type="text" v-model="form.search" @input="search"
                                class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                placeholder="搜索文章...">
                        </div>
                        <div>
                            <select v-model="form.status" @change="search"
                                class="border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                <option value="">全部状态</option>
                                <option value="draft">草稿</option>
                                <option value="published">已发布</option>
                            </select>
                        </div>
                    </div>

                    <!-- 文章列表 -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        标题
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        作者
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        状态
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        浏览量
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        最后编辑
                                    </th>
                                    <th
                                        class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        操作
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="article in articles.data" :key="article.id">
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-gray-900">{{ article.title }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-500">{{ article.creator?.name || '未知' }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span :class="[
                                            'px-2 py-1 text-xs font-medium rounded-full',
                                            {
                                                'bg-gray-100 text-gray-800': article.status === 'draft',
                                                'bg-green-100 text-green-800': article.status === 'published'
                                            }
                                        ]">
                                            {{ article.status === 'draft' ? '草稿' : '已发布' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-500">{{ article.view_count }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-500">
                                            {{ formatDate(article.created_at) }}
                                            <div>{{ article.lastEditor?.name || '未知' }}</div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-right text-sm font-medium">
                                        <Link :href="route('wiki.show', article.id)"
                                            class="text-blue-600 hover:text-blue-900 mr-4">查看</Link>
                                        <Link v-if="can.edit_article" :href="route('wiki.edit', article.id)"
                                            class="text-blue-600 hover:text-blue-900 mr-4">编辑</Link>
                                        <button v-if="can.delete_article" @click="confirmDelete(article)"
                                            class="text-red-600 hover:text-red-900">删除</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- 分页 -->
                    <div class="mt-6">
                        <Pagination :links="articles.links" />
                    </div>
                </div>
            </div>
        </div>

        <!-- 确认删除对话框 -->
        <ConfirmationModal :show="confirmingDeletion" title="确认删除文章" message="确定要删除该文章吗？此操作不可恢复。"
            @close="closeDeleteModal" @confirm="deleteArticle" />

        <!-- 消息提示 -->
        <FlashMessage ref="flash" />
    </MainLayout>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayouts/MainLayout.vue'
import Pagination from '@/Components/Other/Pagination.vue'
import ConfirmationModal from '@/Components/Modal/ConfirmationModal.vue'
import FlashMessage from '@/Components/Other/FlashMessage.vue'

const props = defineProps({
    articles: Object,
    filters: Object,
    can: Object
})

// 表单数据
const form = reactive({
    search: props.filters.search || '',
    status: props.filters.status || '',
})

// 删除确认
const confirmingDeletion = ref(false)
const articleToDelete = ref(null)

const search = () => {
    router.get(route('wiki.index'), form, {
        preserveState: true,
        preserveScroll: true
    })
}

const confirmDelete = (article) => {
    articleToDelete.value = article
    confirmingDeletion.value = true
}

const closeDeleteModal = () => {
    confirmingDeletion.value = false
    articleToDelete.value = null
}

const deleteArticle = () => {
    if (articleToDelete.value) {
        router.delete(route('wiki.destroy', articleToDelete.value.id), {
            onSuccess: () => {
                closeDeleteModal()
            }
        })
    }
}

const formatDate = (date) => {
    if (!date) return ''
    return new Date(date).toLocaleString('zh-CN', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    })
}
</script>