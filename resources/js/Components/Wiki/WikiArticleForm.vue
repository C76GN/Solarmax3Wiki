<template>
    <form @submit.prevent="submit" class="space-y-6">
        <!-- 标题输入 -->
        <div>
            <label for="title" class="block text-sm font-medium text-gray-700">文章标题</label>
            <input type="text" id="title" v-model="form.title"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                :class="{ 'border-red-500': form.errors.title }">
            <p v-if="form.errors.title" class="mt-1 text-sm text-red-600">{{ form.errors.title }}</p>
        </div>

        <!-- 分类选择 -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">文章分类</label>
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                <div v-for="category in categories" :key="category.id" class="relative flex items-start">
                    <div class="flex h-5 items-center">
                        <input type="checkbox" :value="category.id" v-model="form.categories"
                            class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    </div>
                    <div class="ml-3 text-sm">
                        <label class="font-medium text-gray-700">{{ category.name }}</label>
                        <p class="text-gray-500">{{ category.description }}</p>
                    </div>
                </div>
            </div>
            <p v-if="form.errors.categories" class="mt-1 text-sm text-red-600">{{ form.errors.categories }}</p>
        </div>

        <!-- 内容编辑器 -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">文章内容</label>
            <WikiEditor id="content" v-model="form.content" :init="editorConfig" />
            <p v-if="form.errors.content" class="mt-1 text-sm text-red-600">{{ form.errors.content }}</p>
        </div>

        <!-- 表单按钮 -->
        <div class="flex justify-end gap-4">
            <Link :href="route('wiki.index')"
                class="px-4 py-2 text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200 transition duration-150 ease-in-out">
            取消
            </Link>
            <button type="submit" :disabled="form.processing"
                class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 transition duration-150 ease-in-out disabled:opacity-50">
                {{ article ? '更新文章' : '创建文章' }}
            </button>
        </div>
    </form>
</template>

<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import WikiEditor from '@/Components/Editor/WikiEditor.vue';
import { useEditor } from '@/plugins/tinymce';

const props = defineProps({
    article: {
        type: Object,
        default: null
    },
    categories: {
        type: Array,
        required: true
    }
});

const { init: editorConfig } = useEditor();

const form = useForm({
    title: props.article?.title || '',
    content: props.article?.content || '',
    categories: props.article?.categories?.map(c => c.id) || []
});

const submit = () => {
    if (props.article) {
        form.put(route('wiki.update', props.article.id));
    } else {
        form.post(route('wiki.store'));
    }
};
</script>