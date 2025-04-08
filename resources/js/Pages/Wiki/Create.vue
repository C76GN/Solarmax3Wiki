<template>
    <MainLayout :navigationLinks="navigationLinks">
        <div class="container mx-auto py-6 px-4">
            <div class="bg-white/80 backdrop-blur-sm rounded-lg shadow-lg p-6">
                <h1 class="text-2xl font-bold mb-6">创建新 Wiki 页面</h1>

                <form @submit.prevent="createPage">
                    <div class="space-y-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                页面模板
                            </label>
                            <select v-model="form.template_id" @change="applyTemplate"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option :value="null">不使用模板</option>
                                <option v-for="template in templates" :key="template.id" :value="template.id">
                                    {{ template.name }} - {{ template.description }}
                                </option>
                            </select>
                            <div v-if="form.errors.template_id" class="mt-1 text-sm text-red-600">
                                {{ form.errors.template_id }}
                            </div>
                        </div>
                        <div v-if="selectedTemplate && form.template_id"
                            class="space-y-4 p-4 bg-gray-50 rounded-lg border border-gray-200">
                            <h3 class="text-lg font-medium">模板字段</h3>
                            <div v-for="(field, index) in selectedTemplate.structure" :key="index" class="space-y-2">
                                <label :for="`field-${index}`" class="block text-sm font-medium text-gray-700">
                                    {{ field.label }}
                                    <span v-if="field.required" class="text-red-500">*</span>
                                </label>

                                <!-- 根据字段类型渲染不同的输入控件 -->
                                <input v-if="field.type === 'text'" :id="`field-${index}`"
                                    v-model="templateFields[field.name]" type="text"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    :required="field.required" />

                                <textarea v-else-if="field.type === 'textarea'" :id="`field-${index}`"
                                    v-model="templateFields[field.name]" rows="3"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    :required="field.required"></textarea>

                                <select v-else-if="field.type === 'select'" :id="`field-${index}`"
                                    v-model="templateFields[field.name]"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    :required="field.required">
                                    <option v-for="option in field.options" :key="option.value" :value="option.value">
                                        {{ option.label }}
                                    </option>
                                </select>
                            </div>
                        </div>
                        <!-- 标题 -->
                        <div>
                            <label for="title" class="block text-sm font-medium text-gray-700 mb-1">
                                标题 <span class="text-red-500">*</span>
                            </label>
                            <input id="title" v-model="form.title" type="text"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                required />
                            <div v-if="form.errors.title" class="mt-1 text-sm text-red-600">
                                {{ form.errors.title }}
                            </div>
                        </div>


                        <!-- 内容编辑器 -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                内容 <span class="text-red-500">*</span>
                            </label>
                            <Editor v-model="form.content" placeholder="开始编辑页面内容..." />
                            <div v-if="form.errors.content" class="mt-1 text-sm text-red-600">
                                {{ form.errors.content }}
                            </div>
                        </div>

                        <!-- 父页面 -->
                        <div>
                            <label for="parent_id" class="block text-sm font-medium text-gray-700 mb-1">
                                父页面
                            </label>
                            <select id="parent_id" v-model="form.parent_id"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option :value="null">无父页面</option>
                                <!-- 这里需要获取可用的父页面列表 -->
                            </select>
                        </div>

                        <!-- 分类 -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                分类 <span class="text-red-500">*</span>
                            </label>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div v-for="category in categories" :key="category.id" class="flex items-center">
                                    <input type="checkbox" :id="`category-${category.id}`" :value="category.id"
                                        v-model="form.category_ids"
                                        class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500" />
                                    <label :for="`category-${category.id}`" class="ml-2 text-sm text-gray-700">
                                        {{ category.name }}
                                    </label>
                                </div>
                            </div>
                            <div v-if="form.errors.category_ids" class="mt-1 text-sm text-red-600">
                                {{ form.errors.category_ids }}
                            </div>
                        </div>

                        <!-- 标签 -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                标签
                            </label>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div v-for="tag in tags" :key="tag.id" class="flex items-center">
                                    <input type="checkbox" :id="`tag-${tag.id}`" :value="tag.id" v-model="form.tag_ids"
                                        class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500" />
                                    <label :for="`tag-${tag.id}`" class="ml-2 text-sm text-gray-700">
                                        {{ tag.name }}
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- 提交按钮 -->
                        <div class="flex justify-end space-x-3">
                            <Link :href="route('wiki.index')"
                                class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                            取消
                            </Link>

                            <button type="submit"
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
                                :disabled="form.processing">
                                创建页面
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </MainLayout>
</template>

<script setup>
import { ref, watch } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayouts/MainLayout.vue';
import Editor from '@/Components/Wiki/Editor.vue';
import { mainNavigationLinks } from '@/config/navigationConfig';

const navigationLinks = mainNavigationLinks;

const props = defineProps({
    categories: {
        type: Array,
        required: true
    },
    tags: {
        type: Array,
        required: true
    },
    templates: {
        type: Array,
        default: () => []
    }
});

const form = useForm({
    title: '',
    content: '',
    parent_id: null,
    category_ids: [],
    tag_ids: [],
    template_id: null,
    template_fields: {}
});
const selectedTemplate = ref(null);
const templateFields = ref({});
watch(() => form.template_id, (newId) => {
    if (newId) {
        const selectedTemplate = props.templates.find(t => t.id === newId);
        if (selectedTemplate) {
            applyTemplate(selectedTemplate);
        }
    }
});


// 初始化模板字段
const initTemplateFields = () => {
    if (!selectedTemplate.value) return;

    const fields = {};
    selectedTemplate.value.structure.forEach(field => {
        fields[field.name] = field.default || '';
    });

    templateFields.value = fields;
};

// 应用模板
const applyTemplate = (template) => {
    if (!template) return;

    // 初始化模板字段
    const fields = {};
    if (template.structure && Array.isArray(template.structure)) {
        template.structure.forEach(field => {
            fields[field.name] = field.default || '';
        });
    }

    templateFields.value = fields;

    // 如果模板有默认内容格式，可以应用
    if (template.structure) {
        // 生成模板默认内容
        let defaultContent = '';
        template.structure.forEach(field => {
            defaultContent += `## ${field.label}\n\n`;

            if (field.type === 'textarea') {
                defaultContent += `在此处填写${field.label}的详细信息...\n\n`;
            } else if (field.type === 'select') {
                defaultContent += `选择一个${field.label}选项...\n\n`;
            } else {
                defaultContent += `${field.default || ''}\n\n`;
            }
        });

        form.content = defaultContent;
    }
};

const createPage = () => {
    // 将模板字段添加到表单中
    if (form.template_id && Object.keys(templateFields.value).length > 0) {
        form.template_fields = templateFields.value;
    }

    form.post(route('wiki.store'));
};
</script>