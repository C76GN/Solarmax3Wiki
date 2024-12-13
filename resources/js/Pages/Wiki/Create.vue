<template>
    <MainLayout
        :navigationLinks="[{ href: '/wiki', label: '游戏维基' }, { href: '#', label: '游戏历史&名人墙' }, { href: '#', label: '自制专区' }, { href: '#', label: '攻略专区' }, { href: '#', label: '论坛' }]"
        :show-dropdown="true">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white/80 backdrop-blur-sm shadow-lg rounded-lg overflow-hidden">
                <div class="p-6">
                    <h2 class="text-2xl font-semibold text-gray-800 mb-6">创建新文章</h2>

                    <form @submit.prevent="submit">
                        <!-- 标题输入 -->
                        <div class="mb-6">
                            <label for="title" class="block text-sm font-medium text-gray-700 mb-2">
                                文章标题
                            </label>
                            <input type="text" id="title" v-model="form.title"
                                class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                :class="{ 'border-red-500': form.errors.title }">
                            <div v-if="form.errors.title" class="mt-1 text-sm text-red-600">
                                {{ form.errors.title }}
                            </div>
                        </div>

                        <!-- TinyMCE 编辑器 -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                文章内容
                            </label>
                            <WikiEditor id="content" v-model="form.content" :init="editorConfig" />
                            <div v-if="form.errors.content" class="mt-1 text-sm text-red-600">
                                {{ form.errors.content }}
                            </div>
                        </div>

                        <!-- 提交按钮 -->
                        <div class="flex justify-end gap-4">
                            <Link :href="route('wiki.index')"
                                class="px-4 py-2 text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200 transition duration-150 ease-in-out">
                            取消
                            </Link>
                            <button type="submit" :disabled="form.processing"
                                class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 transition duration-150 ease-in-out disabled:opacity-50">
                                创建文章
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </MainLayout>
</template>

<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayouts/MainLayout.vue';
import WikiEditor from '@/Components/Editor/WikiEditor.vue';
import { useEditor } from '@/plugins/tinymce';

// 获取编辑器配置
const { init: editorConfig } = useEditor();

// 表单数据
const form = useForm({
    title: '',
    content: ''
});

// 提交表单
const submit = () => {
    form.post(route('wiki.store'), {
        onSuccess: () => {
            form.reset();
        }
    });
};
</script>