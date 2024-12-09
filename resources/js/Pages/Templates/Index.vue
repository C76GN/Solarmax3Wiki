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
                        <div class="space-x-2">
                            <Link v-if="canCreateTemplate" :href="route('templates.create')"
                                class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded">
                            创建新模板
                            </Link>
                            <button v-if="canCreateTemplate" @click="showImportModal = true"
                                class="bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded">
                                导入模板
                            </button>
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
                                        <button @click="previewTemplate(template)"
                                            class="inline-flex items-center px-3 py-1 bg-purple-500 hover:bg-purple-600 text-white text-sm font-medium rounded-md transition duration-150 ease-in-out mr-2">
                                            预览
                                        </button>
                                        <Link v-if="canEditTemplate" :href="route('templates.edit', template.id)"
                                            class="inline-flex items-center px-3 py-1 bg-blue-500 hover:bg-blue-600 text-white text-sm font-medium rounded-md transition duration-150 ease-in-out mr-2">
                                        编辑
                                        </Link>
                                        <button v-if="canDeleteTemplate" @click="deleteTemplate(template.id)"
                                            class="inline-flex items-center px-3 py-1 bg-red-500 hover:bg-red-600 text-white text-sm font-medium rounded-md transition duration-150 ease-in-out mr-2">
                                            删除
                                        </button>
                                        <button v-if="canEditTemplate" @click="exportTemplate(template.id)"
                                            class="inline-flex items-center px-3 py-1 bg-green-500 hover:bg-green-600 text-white text-sm font-medium rounded-md transition duration-150 ease-in-out">
                                            导出
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
        <TemplatePreviewModal :is-open="isPreviewModalOpen" :template="selectedTemplate" @close="closePreviewModal" />
        <TemplateImportModal :is-open="showImportModal" @close="showImportModal = false" />
    </MainLayout>
</template>

<script setup>
import { Link, Head, router, usePage } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import MainLayout from '@/Layouts/MainLayouts/MainLayout.vue'
import Pagination from '@/Components/Other/Pagination.vue'
import FlashMessage from '@/Components/Other/FlashMessage.vue'
import TemplatePreviewModal from '@/Components/Modal/TemplatePreviewModal.vue'
import TemplateImportModal from '@/Components/Modal/TemplateImportModal.vue'

const page = usePage()
const user = page.props.auth.user

const canCreateTemplate = computed(() => user.permissions.includes('template.create'))
const canEditTemplate = computed(() => user.permissions.includes('template.edit'))
const canDeleteTemplate = computed(() => user.permissions.includes('template.delete'))

// 在 setup 中添加
const showImportModal = ref(false)

const exportTemplate = (id) => {
    // 创建一个临时链接并触发点击
    const link = document.createElement('a')
    link.href = route('templates.export', id)
    link.download = true
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
}
const isPreviewModalOpen = ref(false)
const selectedTemplate = ref(null)

// 打开预览模态框
const previewTemplate = (template) => {
    selectedTemplate.value = template
    isPreviewModalOpen.value = true
}

// 关闭预览模态框
const closePreviewModal = () => {
    isPreviewModalOpen.value = false
    selectedTemplate.value = null
}

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