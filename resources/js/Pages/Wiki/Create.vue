<template>
    <MainLayout :navigationLinks="navigationLinks">
        <div class="container mx-auto py-6 px-4">
            <div class="bg-white/80 backdrop-blur-sm rounded-lg shadow-lg p-6">
                <h1 class="text-2xl font-bold mb-6">创建新 Wiki 页面</h1>
                <form @submit.prevent="createPage">
                    <div class="space-y-6">
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
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                内容 <span class="text-red-500">*</span>
                            </label>
                            <Editor v-model="form.content" placeholder="开始编辑页面内容..." :editable="true" :autosave="false"
                                :pageId="null" ref="tiptapEditorRef" />
                            <div v-if="form.errors.content" class="mt-1 text-sm text-red-600">
                                {{ form.errors.content }}
                            </div>
                        </div>
                        <!-- 移除父页面选择 -->
                        <!-- <div>
                            <label for="parent_id" class="block text-sm font-medium text-gray-700 mb-1">
                                父页面 (可选)
                            </label>
                            <select id="parent_id" v-model="form.parent_id"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option :value="null">无父页面 (顶级页面)</option>
                                <option v-for="page in pages" :key="page.id" :value="page.id">
                                    {{ page.title }}
                                </option>
                            </select>
                            <div v-if="form.errors.parent_id" class="mt-1 text-sm text-red-600">
                                {{ form.errors.parent_id }}
                            </div>
                        </div> -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                分类 <span class="text-red-500">*</span> (至少选择一个)
                            </label>
                            <div
                                class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 max-h-60 overflow-y-auto p-2 border rounded">
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
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                标签 (可选)
                            </label>
                            <div
                                class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4 max-h-60 overflow-y-auto p-2 border rounded">
                                <div v-for="tag in tags" :key="tag.id" class="flex items-center">
                                    <input type="checkbox" :id="`tag-${tag.id}`" :value="tag.id" v-model="form.tag_ids"
                                        class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500" />
                                    <label :for="`tag-${tag.id}`" class="ml-2 text-sm text-gray-700">
                                        {{ tag.name }}
                                    </label>
                                </div>
                            </div>
                            <div v-if="form.errors.tag_ids" class="mt-1 text-sm text-red-600">
                                {{ form.errors.tag_ids }}
                            </div>
                        </div>
                        <div class="flex justify-end space-x-3 pt-4 border-t">
                            <Link :href="route('wiki.index')"
                                class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                            取消
                            </Link>
                            <button type="submit"
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
                                :disabled="form.processing">
                                {{ form.processing ? '正在创建...' : '创建页面' }}
                            </button>
                        </div>
                        <div v-if="form.errors.general" class="mt-1 text-sm text-red-600 text-right">
                            {{ form.errors.general }}
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </MainLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayouts/MainLayout.vue';
import Editor from '@/Components/Wiki/Editor.vue';
import { mainNavigationLinks } from '@/config/navigationConfig';

const navigationLinks = mainNavigationLinks;

const props = defineProps({
    categories: { type: Array, required: true },
    tags: { type: Array, required: true },
    // pages: { type: Array, required: true }, // 移除父页面列表
});

const form = useForm({
    title: '',
    content: '<p></p>',
    // parent_id: null, // 移除
    category_ids: [],
    tag_ids: [],
});

const tiptapEditorRef = ref(null);

const createPage = () => {
    if (tiptapEditorRef.value && tiptapEditorRef.value.editor) {
        form.content = tiptapEditorRef.value.editor.getHTML();
    }
    form.post(route('wiki.store'), {
        onError: (errors) => {
            console.error("创建页面失败:", errors);
            if (!errors.title && !errors.content && !errors.category_ids && !errors.tag_ids) {
                form.setError('general', '创建页面时发生未知错误。');
            }
        }
    });
};
</script>