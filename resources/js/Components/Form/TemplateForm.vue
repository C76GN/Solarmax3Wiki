<template>
    <form @submit.prevent="submit">
        <!-- 名称和描述部分保持不变 -->
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

        <!-- 增强的字段设置部分 -->
        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">
                模板字段
            </label>
            <div v-for="(field, index) in form.fields" :key="index" class="mb-6 p-4 border border-gray-200 rounded-lg">
                <!-- 基本字段设置 -->
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">字段名称</label>
                        <input v-model="field.name" type="text"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">字段类型</label>
                        <select v-model="field.type"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm"
                            @change="updateFieldValidationRules(index)">
                            <option value="text">文本</option>
                            <option value="number">数字</option>
                            <option value="boolean">布尔值</option>
                            <option value="date">日期</option>
                            <option value="markdown">Markdown</option>
                        </select>
                    </div>
                </div>

                <!-- 字段描述和帮助文本 -->
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">字段描述</label>
                        <input v-model="field.description" type="text"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm"
                            placeholder="描述这个字段的用途">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">帮助文本</label>
                        <input v-model="field.help_text" type="text"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm" placeholder="填写时的提示信息">
                    </div>
                </div>

                <!-- 验证规则设置 -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">验证规则</label>

                    <!-- 文本类型的验证规则 -->
                    <template v-if="field.type === 'text'">
                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm text-gray-600">最小长度</label>
                                <input v-model.number="field.validation_rules.min" type="number"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm">
                            </div>
                            <div>
                                <label class="block text-sm text-gray-600">最大长度</label>
                                <input v-model.number="field.validation_rules.max" type="number"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm">
                            </div>
                            <div>
                                <label class="block text-sm text-gray-600">正则表达式</label>
                                <input v-model="field.validation_rules.pattern" type="text"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm">
                            </div>
                        </div>
                    </template>

                    <!-- 数字类型的验证规则 -->
                    <template v-if="field.type === 'number'">
                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm text-gray-600">最小值</label>
                                <input v-model.number="field.validation_rules.min" type="number"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm">
                            </div>
                            <div>
                                <label class="block text-sm text-gray-600">最大值</label>
                                <input v-model.number="field.validation_rules.max" type="number"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm">
                            </div>
                            <div>
                                <label class="block text-sm text-gray-600">步进值</label>
                                <input v-model.number="field.validation_rules.step" type="number"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm">
                            </div>
                        </div>
                    </template>

                    <!-- 日期类型的验证规则 -->
                    <template v-if="field.type === 'date'">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm text-gray-600">最早日期</label>
                                <input v-model="field.validation_rules.min_date" type="date"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm">
                            </div>
                            <div>
                                <label class="block text-sm text-gray-600">最晚日期</label>
                                <input v-model="field.validation_rules.max_date" type="date"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm">
                            </div>
                        </div>
                    </template>

                    <!-- Markdown类型的验证规则 -->
                    <template v-if="field.type === 'markdown'">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm text-gray-600">最小字符数</label>
                                <input v-model.number="field.validation_rules.min_length" type="number"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm">
                            </div>
                            <div>
                                <label class="block text-sm text-gray-600">最大字符数</label>
                                <input v-model.number="field.validation_rules.max_length" type="number"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm">
                            </div>
                        </div>
                    </template>
                </div>

                <!-- 默认值和占位符 -->
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">默认值</label>
                        <input v-if="field.type !== 'boolean'" v-model="field.default_value"
                            :type="field.type === 'date' ? 'date' : 'text'"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm">
                        <div v-else class="flex items-center mt-2">
                            <input type="checkbox" v-model="field.default_value"
                                class="rounded border-gray-300 text-blue-500 shadow-sm">
                            <span class="ml-2 text-sm text-gray-600">默认选中</span>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">占位文本</label>
                        <input v-model="field.placeholder" type="text"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm"
                            v-if="field.type !== 'boolean'">
                    </div>
                </div>

                <!-- 必填选项 -->
                <div class="flex items-center mb-4">
                    <input v-model="field.required" type="checkbox"
                        class="rounded border-gray-300 text-blue-500 shadow-sm">
                    <span class="ml-2 text-sm text-gray-600">必填字段</span>
                </div>

                <!-- 删除按钮 -->
                <button type="button" @click="removeField(index)"
                    class="text-red-600 hover:text-red-800 text-sm font-medium">
                    删除字段
                </button>
            </div>

            <!-- 添加字段按钮 -->
            <button type="button" @click="addField"
                class="w-full px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 transition duration-150 ease-in-out">
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

const getDefaultValidationRules = (type) => {
    switch (type) {
        case 'text':
            return {
                min: 0,
                max: 255,
                pattern: ''
            }
        case 'number':
            return {
                min: null,
                max: null,
                step: 1
            }
        case 'date':
            return {
                min_date: null,
                max_date: null
            }
        case 'markdown':
            return {
                min_length: 0,
                max_length: 50000
            }
        default:
            return {}
    }
}

const form = useForm({
    name: props.template.name,
    description: props.template.description,
    fields: props.template.fields.length ? props.template.fields : [{
        name: '',
        type: 'text',
        required: false,
        description: '',
        default_value: null,
        validation_rules: getDefaultValidationRules('text'),
        placeholder: '',
        help_text: ''
    }]
})

const addField = () => {
    form.fields.push({
        name: '',
        type: 'text',
        required: false,
        description: '',
        default_value: null,
        validation_rules: getDefaultValidationRules('text'),
        placeholder: '',
        help_text: ''
    })
}

const removeField = (index) => {
    form.fields.splice(index, 1)
}

const updateFieldValidationRules = (index) => {
    const field = form.fields[index]
    field.validation_rules = getDefaultValidationRules(field.type)
    field.default_value = null
}

const submit = () => {
    if (props.template.id) {
        form.put(route('templates.update', props.template.id))
    } else {
        form.post(route('templates.store'))
    }
}
</script>