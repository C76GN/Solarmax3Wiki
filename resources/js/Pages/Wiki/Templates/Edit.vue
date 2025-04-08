// 新文件: resources/js/Pages/Wiki/Templates/Edit.vue

<template>
    <MainLayout :navigationLinks="navigationLinks">
        <div class="container mx-auto py-6 px-4">
            <div class="bg-white/80 backdrop-blur-sm rounded-lg shadow-lg p-6">
                <div class="flex justify-between items-center mb-6">
                    <h1 class="text-2xl font-bold">编辑模板: {{ template.name }}</h1>
                    <Link :href="route('wiki.templates.index')" class="text-blue-600 hover:text-blue-800">
                    返回模板列表
                    </Link>
                </div>

                <form @submit.prevent="submitForm">
                    <div class="space-y-6">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                                模板名称 <span class="text-red-500">*</span>
                            </label>
                            <input id="name" v-model="form.name" type="text" class=" w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none
                                focus:ring-2 focus:ring-blue-500 focus:border-transparent" required />
                            <div v-if="form.errors.name" class="mt-1 text-sm text-red-600">
                                {{ form.errors.name }}
                            </div>
                        </div>

                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700 mb-1">
                                模板描述
                            </label>
                            <textarea id="description" v-model="form.description" rows="3"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"></textarea>
                            <div v-if="form.errors.description" class="mt-1 text-sm text-red-600">
                                {{ form.errors.description }}
                            </div>
                        </div>

                        <div>
                            <div class="flex justify-between items-center mb-2">
                                <label class="block text-sm font-medium text-gray-700">
                                    模板字段 <span class="text-red-500">*</span>
                                </label>
                                <button type="button" @click="addField"
                                    class="px-3 py-1 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm">
                                    添加字段
                                </button>
                            </div>

                            <div v-if="form.structure.length === 0"
                                class="bg-gray-50 p-4 rounded-lg text-center text-gray-500">
                                请添加至少一个字段
                            </div>

                            <div v-for="(field, index) in form.structure" :key="index"
                                class="bg-gray-50 p-4 rounded-lg mb-3 border border-gray-200">
                                <div class="flex justify-between mb-2">
                                    <h3 class="font-medium">字段 #{{ index + 1 }}</h3>
                                    <button type="button" @click="removeField(index)"
                                        class="text-red-500 hover:text-red-700">
                                        <font-awesome-icon :icon="['fas', 'trash']" />
                                    </button>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label :for="`field-name-${index}`"
                                            class="block text-sm font-medium text-gray-700 mb-1">
                                            字段标识 <span class="text-red-500">*</span>
                                        </label>
                                        <input :id="`field-name-${index}`" v-model="field.name" type="text"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                            required />
                                    </div>

                                    <div>
                                        <label :for="`field-label-${index}`"
                                            class="block text-sm font-medium text-gray-700 mb-1">
                                            字段名称 <span class="text-red-500">*</span>
                                        </label>
                                        <input :id="`field-label-${index}`" v-model="field.label" type="text"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                            required />
                                    </div>

                                    <div>
                                        <label :for="`field-type-${index}`"
                                            class="block text-sm font-medium text-gray-700 mb-1">
                                            字段类型 <span class="text-red-500">*</span>
                                        </label>
                                        <select :id="`field-type-${index}`" v-model="field.type"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                            required>
                                            <option value="text">文本</option>
                                            <option value="textarea">多行文本</option>
                                            <option value="select">下拉选择</option>
                                            <option value="number">数字</option>
                                            <option value="date">日期</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label :for="`field-default-${index}`"
                                            class="block text-sm font-medium text-gray-700 mb-1">
                                            默认值
                                        </label>
                                        <input :id="`field-default-${index}`" v-model="field.default" type="text"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
                                    </div>

                                    <div>
                                        <div class="flex items-center mt-4">
                                            <input :id="`field-required-${index}`" v-model="field.required"
                                                type="checkbox"
                                                class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500" />
                                            <label :for="`field-required-${index}`" class="ml-2 text-sm text-gray-700">
                                                必填字段
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <!-- 如果是选择类型，显示选项管理 -->
                                <div v-if="field.type === 'select'" class="mt-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        选项 <span class="text-red-500">*</span>
                                    </label>
                                    <div v-for="(option, optIndex) in field.options || []"
                                        :key="`opt-${index}-${optIndex}`" class="flex items-center mb-2">
                                        <input v-model="option.label" placeholder="选项名称"
                                            class="px-4 py-2 border border-gray-300 rounded-lg mr-2" />
                                        <input v-model="option.value" placeholder="选项值"
                                            class="px-4 py-2 border border-gray-300 rounded-lg" />
                                        <button type="button" @click="removeOption(field, optIndex)"
                                            class="ml-2 text-red-500 hover:text-red-700">
                                            <font-awesome-icon :icon="['fas', 'times']" />
                                        </button>
                                    </div>
                                    <button type="button" @click="addOption(field)"
                                        class="px-3 py-1 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 text-sm">
                                        添加选项
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end space-x-3">
                            <Link :href="route('wiki.templates.index')"
                                class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                            取消
                            </Link>
                            <button type="submit"
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
                                :disabled="form.processing">
                                保存模板
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </MainLayout>
</template>

<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayouts/MainLayout.vue';

const props = defineProps({
    template: {
        type: Object,
        required: true
    }
});

import { adminNavigationLinks } from '@/config/navigationConfig';

const navigationLinks = adminNavigationLinks;

const form = useForm({
    name: props.template.name,
    description: props.template.description || '',
    structure: Array.isArray(props.template.structure) ?
        JSON.parse(JSON.stringify(props.template.structure)) : []
});

const addField = () => {
    form.structure.push({
        name: '',
        label: '',
        type: 'text',
        required: false,
        default: '',
        options: []
    });
};

const removeField = (index) => {
    form.structure.splice(index, 1);
};

const addOption = (field) => {
    if (!field.options) {
        field.options = [];
    }
    field.options.push({ label: '', value: '' });
};

const removeOption = (field, index) => {
    field.options.splice(index, 1);
};

const submitForm = () => {
    // 验证字段
    let isValid = true;

    // 检查必要的字段
    if (form.structure.length === 0) {
        alert('请至少添加一个字段');
        isValid = false;
        return;
    }

    // 验证每个字段
    form.structure.forEach((field, index) => {
        if (!field.name || !field.label || !field.type) {
            alert(`字段 #${index + 1} 的信息不完整`);
            isValid = false;
            return;
        }

        // 如果是选择类型，必须有选项
        if (field.type === 'select') {
            if (!field.options || field.options.length === 0) {
                alert(`字段 "${field.label}" 是选择类型，但没有选项`);
                isValid = false;
                return;
            }

            // 检查每个选项
            field.options.forEach((option, optIndex) => {
                if (!option.label || !option.value) {
                    alert(`字段 "${field.label}" 的选项 #${optIndex + 1} 不完整`);
                    isValid = false;
                    return;
                }
            });
        }
    });

    if (isValid) {
        form.put(route('wiki.templates.update', props.template.id));
    }
};
</script>