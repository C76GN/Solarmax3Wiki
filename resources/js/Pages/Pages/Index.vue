<template>
    <MainLayout
        :navigationLinks="[{ href: '/gamewiki', label: '游戏维基' }, { href: '#', label: '游戏历史&名人墙' }, { href: '#', label: '自制专区' }, { href: '#', label: '攻略专区' }, { href: '#', label: '论坛' }]"
        :show-dropdown="true">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white/80 backdrop-blur-sm shadow-lg rounded-lg overflow-hidden">
                <div class="p-6">
                    <!-- 标题 -->
                    <h2 class="text-2xl font-semibold text-gray-800 mb-6">
                        页面管理
                    </h2>

                    <!-- 操作栏 -->
                    <div class="mb-4 flex justify-between items-center">
                        <div>
                            <Link :href="route('pages.create')"
                                class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded transition duration-150 ease-in-out">
                            创建新页面
                            </Link>
                        </div>
                    </div>

                    <!-- 页面列表 -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full table-auto">
                            <thead>
                                <tr class="bg-gray-50">
                                    <th class="px-4 py-2 text-left">标题</th>
                                    <th class="px-4 py-2 text-left">模板</th>
                                    <th class="px-4 py-2 text-center">状态</th>
                                    <th class="px-4 py-2 text-center">发布时间</th>
                                    <th class="px-4 py-2 text-center">操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="page in pages.data" :key="page.id"
                                    class="border-t border-gray-200 hover:bg-gray-50/50">
                                    <td class="px-4 py-3">{{ page.title }}</td>
                                    <td class="px-4 py-3">{{ page.template.name }}</td>
                                    <td class="px-4 py-3 text-center">
                                        <span :class="[
                                            'px-2 py-1 text-sm font-medium rounded-full',
                                            page.is_published
                                                ? 'bg-green-100 text-green-800'
                                                : 'bg-yellow-100 text-yellow-800'
                                        ]">
                                            {{ page.is_published ? '已发布' : '草稿' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        {{ page.published_at ? formatDate(page.published_at) : '未发布' }}
                                    </td>
                                    <td class="px-4 py-3 text-center space-x-2">
                                        <Link :href="route('pages.edit', page.id)"
                                            class="inline-flex items-center px-3 py-1 bg-blue-500 hover:bg-blue-600 text-white text-sm font-medium rounded-md transition duration-150 ease-in-out">
                                        编辑
                                        </Link>
                                        <button v-if="!page.is_published" @click="publishPage(page)"
                                            class="inline-flex items-center px-3 py-1 bg-green-500 hover:bg-green-600 text-white text-sm font-medium rounded-md transition duration-150 ease-in-out">
                                            发布
                                        </button>
                                        <button v-else @click="unpublishPage(page)"
                                            class="inline-flex items-center px-3 py-1 bg-yellow-500 hover:bg-yellow-600 text-white text-sm font-medium rounded-md transition duration-150 ease-in-out">
                                            取消发布
                                        </button>
                                        <button @click="deletePage(page.id)"
                                            class="inline-flex items-center px-3 py-1 bg-red-500 hover:bg-red-600 text-white text-sm font-medium rounded-md transition duration-150 ease-in-out">
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
        <FlashMessage ref="flash" />
    </MainLayout>
</template>

<script setup>
import { Link, router } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayouts/MainLayout.vue'
import Pagination from '@/Components/Other/Pagination.vue'
import FlashMessage from '@/Components/Other/FlashMessage.vue'

const props = defineProps({
    pages: Object
})

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('zh-CN', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    })
}

const deletePage = (id) => {
    if (confirm('确定要删除这个页面吗？')) {
        router.delete(route('pages.destroy', id))
    }
}

const publishPage = (page) => {
    router.post(route('pages.publish', page.id))
}

const unpublishPage = (page) => {
    router.post(route('pages.unpublish', page.id))
}
</script>