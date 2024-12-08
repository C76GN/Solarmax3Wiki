<template>
    <form @submit.prevent="submit">
        <!-- 名称输入 -->
        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">
                模板名称
            </label>
            <input type="text" v-model="form.name"
                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                :class="{ 'border-red-500': form.errors.name }">
            <div v-if="form.errors.name" class="mt-1 text-sm text-red-600">
                {{ form.errors.name }}
            </div>
        </div>

        <!-- 描述输入 -->
        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">
                描述
            </label>
            <textarea v-model="form.description" rows="3"
                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                :class="{ 'border-red-500': form.errors.description }"></textarea>
            <div v-if="form.errors.description" class="mt-1 text-sm text-red-600">
                {{ form.errors.description }}
            </div>
        </div>

        <!-- 字段设置 -->
        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">
                模板字段
            </label>
            <div v-for="(field, index) in form.fields" :key="index" class="flex gap-4 mb-4">
                <div class="flex-1">
                    <input v-model="field.name" type="text" placeholder="字段名称"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div class="flex-1">
                    <select v-model="field.type"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        <option value="text">文本</option>
                        <option value="number">数字</option>
                        <option value="boolean">布尔值</option>
                        <option value="date">日期</option>
                        <option value="markdown">Markdown</option>
                    </select>
                </div>
                <div class="flex-1">
                    <input v-model="field.required" type="checkbox" class="mr-2">
                    <span class="text-sm text-gray-600">必填</span>
                </div>
                <button type="button" @click="removeField(index)" class="px-3 py-2 text-red-600 hover:text-red-800">
                    删除
                </button>
            </div>
            <button type="button" @click="addField"
                class="mt-2 px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 transition duration-150 ease-in-out">
                添加字段
            </button>
        </div>

        <!-- 提交按钮 -->
        <div class="flex justify-end gap-4">
            <Link :href="route('templates.index')"
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
import { useForm, Link } from '@inertiajs/vue3'

const props = defineProps({
    template: {
        type: Object,
        default: () => ({
            name: '',
            description: '',
            fields: []
        })
    },
    submitButtonText: {
        type: String,
        default: '保存'
    }
})

const form = useForm({
    name: props.template.name,
    description: props.template.description,
    fields: props.template.fields.length ? props.template.fields : [{
        name: '',
        type: 'text',
        required: false
    }]
})

const addField = () => {
    form.fields.push({
        name: '',
        type: 'text',
        required: false
    })
}

const removeField = (index) => {
    form.fields.splice(index, 1)
}

const submit = () => {
    if (props.template.id) {
        form.put(route('templates.update', props.template.id))
    } else {
        form.post(route('templates.store'))
    }
}
</script>