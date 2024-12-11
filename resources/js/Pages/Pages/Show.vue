<template>
    <MainLayout
        :navigationLinks="[{ href: '/gamewiki', label: '游戏维基' }, { href: '#', label: '游戏历史&名人墙' }, { href: '#', label: '自制专区' }, { href: '#', label: '攻略专区' }, { href: '#', label: '论坛' }]"
        :show-dropdown="true">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white/80 backdrop-blur-sm shadow-lg rounded-lg overflow-hidden">
                <div class="p-6">
                    <!-- 页面标题和元信息 -->
                    <div class="border-b border-gray-200 pb-6 mb-6">
                        <h1 class="text-3xl font-bold text-gray-900 mb-2">{{ page.title }}</h1>
                        <div class="flex items-center justify-between text-sm text-gray-500">
                            <div class="flex items-center space-x-4">
                                <span>模板：{{ page.template.name }}</span>
                                <span>{{ page.is_published ? '已发布' : '草稿' }}</span>
                                <span v-if="page.published_at">
                                    发布时间：{{ formatDate(page.published_at) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- 页面内容 -->
                    <div class="space-y-8">
                        <template v-for="field in page.template.fields" :key="field.name">
                            <div class="space-y-2">
                                <h3 class="text-lg font-medium text-gray-900">
                                    {{ field.name }}
                                    <span v-if="field.required" class="text-red-500">*</span>
                                </h3>

                                <!-- 根据字段类型显示不同的内容 -->
                                <div class="mt-1">
                                    <!-- Markdown 内容 -->
                                    <div v-if="field.type === 'markdown'" class="prose prose-sm max-w-none"
                                        v-html="markdownToHtml(page.content[field.name])">
                                    </div>

                                    <!-- 布尔值 -->
                                    <div v-else-if="field.type === 'boolean'">
                                        <span :class="[
                                            'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                                            page.content[field.name]
                                                ? 'bg-green-100 text-green-800'
                                                : 'bg-red-100 text-red-800'
                                        ]">
                                            {{ page.content[field.name] ? '是' : '否' }}
                                        </span>
                                    </div>

                                    <!-- 日期 -->
                                    <div v-else-if="field.type === 'date'" class="text-gray-900">
                                        {{ formatDate(page.content[field.name]) }}
                                    </div>

                                    <!-- 数字 -->
                                    <div v-else-if="field.type === 'number'" class="text-gray-900">
                                        {{ page.content[field.name] }}
                                    </div>

                                    <!-- 默认文本显示 -->
                                    <div v-else class="text-gray-900">
                                        {{ page.content[field.name] }}
                                    </div>
                                </div>

                                <!-- 字段描述 -->
                                <p v-if="field.description" class="text-sm text-gray-500">
                                    {{ field.description }}
                                </p>
                            </div>
                        </template>
                    </div>

                    <!-- 操作按钮 -->
                    <div class="mt-8 flex justify-end space-x-4">
                        <Link :href="route('pages.index')"
                            class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200">
                        返回列表
                        </Link>
                        <Link v-if="$page.props.auth.user?.permissions.includes('page.edit')"
                            :href="route('pages.edit', page.id)"
                            class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600">
                        编辑页面
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </MainLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayouts/MainLayout.vue'
import { marked } from 'marked'

const props = defineProps({
    page: {
        type: Object,
        required: true
    }
})

const formatDate = (date) => {
    if (!date) return ''
    return new Date(date).toLocaleString('zh-CN', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}

const markdownToHtml = (markdown) => {
    if (!markdown) return ''
    return marked(markdown)
}
</script>