<template>

    <Head title="Templates" />
    <MainLayout
        :navigationLinks="[{ href: '/gamewiki', label: '游戏维基' }, { href: '#', label: '游戏历史&名人墙' }, { href: '#', label: '自制专区' }, { href: '#', label: '攻略专区' }, { href: '#', label: '论坛' }]"
        :show-dropdown="true">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white/80 backdrop-blur-sm shadow-lg rounded-lg overflow-hidden">
                <div class="p-6">
                    <!-- 标题 -->
                    <h2 class="text-2xl font-semibold text-gray-800 mb-6">
                        模板管理
                    </h2>

                    <!-- 操作栏 -->
                    <div class="mb-4 flex justify-between items-center">
                        <div>
                            <Link :href="route('templates.create')"
                                class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded transition duration-150 ease-in-out">
                            创建新模板
                            </Link>
                        </div>
                    </div>

                    <!-- 模板列表 -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full table-auto">
                            <thead>
                                <tr class="bg-gray-50">
                                    <th class="px-4 py-2 text-left">名称</th>
                                    <th class="px-4 py-2 text-left">描述</th>
                                    <th class="px-4 py-2 text-center">字段数量</th>
                                    <th class="px-4 py-2 text-center">创建时间</th>
                                    <th class="px-4 py-2 text-center">操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="template in templates.data" :key="template.id"
                                    class="border-t border-gray-200 hover:bg-gray-50/50">
                                    <td class="px-4 py-3">{{ template.name }}</td>
                                    <td class="px-4 py-3">{{ template.description }}</td>
                                    <td class="px-4 py-3 text-center">{{ template.fields.length }}</td>
                                    <td class="px-4 py-3 text-center">{{ formatDate(template.created_at) }}</td>
                                    <td class="px-4 py-3 text-center">
                                        <Link :href="route('templates.edit', template.id)"
                                            class="inline-flex items-center px-3 py-1 bg-blue-500 hover:bg-blue-600 text-white text-sm font-medium rounded-md transition duration-150 ease-in-out mr-2">
                                        编辑
                                        </Link>
                                        <button @click="deleteTemplate(template.id)"
                                            class="inline-flex items-center px-3 py-1 bg-red-500 hover:bg-red-600 text-white text-sm font-medium rounded-md transition duration-150 ease-in-out">
                                            删除
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <!-- 分页 -->
                    <div v-if="templates.links" class="mt-6">
                        <Pagination :links="templates.links" />
                    </div>
                </div>
            </div>
        </div>
        <FlashMessage ref="flash" />
    </MainLayout>
</template>

<script setup>
import { Link, Head, router } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayouts/MainLayout.vue'
import Pagination from '@/Components/Other/Pagination.vue'
import FlashMessage from '@/Components/Other/FlashMessage.vue'


const props = defineProps({
    templates: Object
})

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('zh-CN', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    })
}

const deleteTemplate = (id) => {
    if (confirm('确定要删除这个模板吗？')) {
        router.delete(route('templates.destroy', id))
    }
}
</script>