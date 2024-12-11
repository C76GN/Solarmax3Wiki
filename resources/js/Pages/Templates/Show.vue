<template>
    <MainLayout
        :navigationLinks="[{ href: '/gamewiki', label: '游戏维基' }, { href: '#', label: '游戏历史&名人墙' }, { href: '#', label: '自制专区' }, { href: '#', label: '攻略专区' }, { href: '#', label: '论坛' }]"
        :show-dropdown="true">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white/80 backdrop-blur-sm shadow-lg rounded-lg overflow-hidden">
                <div class="p-6">
                    <!-- 模板基本信息 -->
                    <div class="border-b border-gray-200 pb-6 mb-6">
                        <h1 class="text-3xl font-bold text-gray-900 mb-2">{{ template.name }}</h1>
                        <p class="text-gray-500">{{ template.description }}</p>
                    </div>

                    <!-- 字段列表 -->
                    <div class="space-y-6">
                        <h2 class="text-xl font-semibold text-gray-900">字段定义</h2>

                        <div class="grid gap-6">
                            <div v-for="field in template.fields" :key="field.name"
                                class="border border-gray-200 rounded-lg p-4">
                                <!-- 字段标题和类型 -->
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="text-lg font-medium text-gray-900">
                                        {{ field.name }}
                                        <span v-if="field.required" class="text-red-500">*</span>
                                    </h3>
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        {{ getFieldTypeName(field.type) }}
                                    </span>
                                </div>

                                <!-- 字段配置详情 -->
                                <div class="space-y-2">
                                    <!-- 基本配置 -->
                                    <p v-if="field.description" class="text-sm text-gray-600">
                                        描述：{{ field.description }}
                                    </p>
                                    <p v-if="field.help_text" class="text-sm text-gray-600">
                                        帮助文本：{{ field.help_text }}
                                    </p>
                                    <p v-if="field.placeholder" class="text-sm text-gray-600">
                                        占位文本：{{ field.placeholder }}
                                    </p>

                                    <!-- 验证规则 -->
                                    <div v-if="hasValidationRules(field)" class="mt-4">
                                        <h4 class="text-sm font-medium text-gray-900 mb-2">验证规则：</h4>
                                        <ul class="list-disc list-inside space-y-1">
                                            <li v-for="(value, rule) in field.validation_rules" :key="rule"
                                                class="text-sm text-gray-600" v-show="value !== null && value !== ''">
                                                {{ formatValidationRule(rule, value) }}
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 操作按钮 -->
                    <div class="mt-8 flex justify-end space-x-4">
                        <Link :href="route('templates.index')"
                            class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200">
                        返回列表
                        </Link>
                        <Link v-if="$page.props.auth.user?.permissions.includes('template.edit')"
                            :href="route('templates.edit', template.id)"
                            class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600">
                        编辑模板
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

const props = defineProps({
    template: {
        type: Object,
        required: true
    }
})

const getFieldTypeName = (type) => {
    const types = {
        text: '文本',
        number: '数字',
        boolean: '布尔值',
        date: '日期',
        markdown: 'Markdown'
    }
    return types[type] || type
}

const hasValidationRules = (field) => {
    return field.validation_rules && Object.values(field.validation_rules).some(value => value !== null && value !== '')
}

const formatValidationRule = (rule, value) => {
    const rules = {
        min: '最小值',
        max: '最大值',
        min_length: '最小长度',
        max_length: '最大长度',
        pattern: '正则表达式',
        step: '步进值',
        min_date: '最早日期',
        max_date: '最晚日期'
    }

    return `${rules[rule] || rule}：${value}`
}
</script>