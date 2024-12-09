<template>
    <form @submit.prevent="submit">
        <!-- 模板选择 -->
        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">
                选择模板
            </label>
            <select v-model="form.template_id" @change="handleTemplateChange"
                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                :class="{ 'border-red-500': form.errors.template_id }">
                <option value="">请选择模板</option>
                <option v-for="template in templates" :key="template.id" :value="template.id">
                    {{ template.name }}
                </option>
            </select>
            <div v-if="form.errors.template_id" class="mt-1 text-sm text-red-600">
                {{ form.errors.template_id }}
            </div>
        </div>

        <!-- 标题输入 -->
        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">
                页面标题
            </label>
            <input type="text" v-model="form.title"
                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                :class="{ 'border-red-500': form.errors.title }">
            <div v-if="form.errors.title" class="mt-1 text-sm text-red-600">
                {{ form.errors.title }}
            </div>
        </div>

        <!-- 动态字段 -->
        <template v-if="selectedTemplate">
            <div v-for="field in selectedTemplate.fields" :key="field.name" class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    {{ field.name }}
                    <span v-if="field.required" class="text-red-500">*</span>
                </label>

                <!-- 文本输入 -->
                <input v-if="field.type === 'text'" type="text" v-model="form.content[field.name]"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                    :class="{ 'border-red-500': form.errors[`content.${field.name}`] }">

                <!-- 数字输入 -->
                <input v-else-if="field.type === 'number'" type="number" v-model="form.content[field.name]"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                    :class="{ 'border-red-500': form.errors[`content.${field.name}`] }">

                <!-- 日期输入 -->
                <input v-else-if="field.type === 'date'" type="date" v-model="form.content[field.name]"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                    :class="{ 'border-red-500': form.errors[`content.${field.name}`] }">

                <!-- 布尔值输入 -->
                <div v-else-if="field.type === 'boolean'" class="flex items-center">
                    <input type="checkbox" v-model="form.content[field.name]"
                        class="rounded border-gray-300 text-blue-500 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                    <span class="ml-2 text-sm text-gray-600">是</span>
                </div>

                <!-- Markdown 编辑器 -->
                <textarea v-else-if="field.type === 'markdown'" v-model="form.content[field.name]" rows="5"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                    :class="{ 'border-red-500': form.errors[`content.${field.name}`] }"></textarea>

                <div v-if="form.errors[`content.${field.name}`]" class="mt-1 text-sm text-red-600">
                    {{ form.errors[`content.${field.name}`] }}
                </div>
            </div>
        </template>

        <!-- 提交按钮 -->
        <div class="flex justify-end gap-4">
            <Link :href="route('pages.index')"
                class="px-4 py-2 text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200 transition duration-150 ease-in-out">
            取消
            </Link>
            <button type="submit" :disabled="form.processing"
                class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 transition duration-150 ease-in-out disabled:opacity-50">
                {{ submitButtonText }}
            </button>
        </div>
    </form>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useForm, Link } from '@inertiajs/vue3'

const props = defineProps({
    templates: {
        type: Array,
        required: true
    },
    page: {
        type: Object,
        default: null
    },
    submitButtonText: {
        type: String,
        default: '保存'
    }
})

const selectedTemplate = ref(props.page?.template || null)

const form = useForm({
    template_id: props.page?.template_id || '',
    title: props.page?.title || '',
    content: props.page?.content || {}
})

const handleTemplateChange = () => {
    selectedTemplate.value = props.templates.find(t => t.id === form.template_id)
    // 重置内容
    if (!props.page) {
        form.content = {}
    }
}

// 如果是编辑模式，初始化选中的模板
if (props.page) {
    selectedTemplate.value = props.page.template
}

const submit = () => {
    if (props.page) {
        form.put(route('pages.update', props.page.id))
    } else {
        form.post(route('pages.store'))
    }
}
</script>