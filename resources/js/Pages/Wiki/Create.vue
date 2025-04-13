<template>
    <MainLayout :navigationLinks="navigationLinks">

        <Head title="创建 Wiki 页面" />
        <div class="container mx-auto py-6 px-4">
            <!-- 应用统一的背景和模糊效果 -->
            <div class="bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm rounded-lg shadow-lg p-6">
                <h1 class="text-2xl font-bold mb-6 text-gray-900 dark:text-gray-100">创建新 Wiki 页面</h1>
                <form @submit.prevent="createPage">
                    <div class="space-y-6">
                        <div>
                            <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                标题 <span class="text-red-500">*</span>
                            </label>
                            <input id="title" v-model="form.title" type="text" class="input-field"
                            required />
                            <InputError class="mt-1" :message="form.errors.title" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                内容 <span class="text-red-500">*</span>
                            </label>
                            <!-- Editor 组件可能需要内部处理暗色模式，或者外部容器提供背景 -->
                            <Editor v-model="form.content" placeholder="开始编辑页面内容..." :editable="true" :autosave="false"
                                :pageId="null" ref="tiptapEditorRef" />
                            <InputError class="mt-1" :message="form.errors.content" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                分类 <span class="text-red-500">*</span>
                            </label>
                            <div class="checkbox-group"> <!-- 使用统一样式类 -->
                                <div v-for="category in categories" :key="category.id" class="flex items-center">
                                    <input type="checkbox" :id="`category-${category.id}`" :value="category.id"
                                        v-model="form.category_ids" class="checkbox" /> <!-- 使用统一样式类 -->
                                    <label :for="`category-${category.id}`"
                                        class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                        {{ category.name }}
                                    </label>
                                </div>
                            </div>
                            <InputError class="mt-1" :message="form.errors.category_ids" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                标签 (可选)
                            </label>
                            <div class="checkbox-group"> <!-- 使用统一样式类 -->
                                <div v-for="tag in tags" :key="tag.id" class="flex items-center">
                                    <input type="checkbox" :id="`tag-${tag.id}`" :value="tag.id" v-model="form.tag_ids"
                                        class="checkbox" /> <!-- 使用统一样式类 -->
                                    <label :for="`tag-${tag.id}`" class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                        {{ tag.name }}
                                    </label>
                                </div>
                            </div>
                            <InputError class="mt-1" :message="form.errors.tag_ids" />
                        </div>

                        <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                            <Link :href="route('wiki.index')" class="btn-secondary"> <!-- 使用统一样式类 -->
                            取消
                            </Link>
                            <button type="submit" class="btn-primary" :disabled="form.processing"> <!-- 使用统一样式类 -->
                                <font-awesome-icon v-if="form.processing" :icon="['fas', 'spinner']" spin
                                    class="mr-1" />
                                {{ form.processing ? '正在创建...' : '创建页面' }}
                            </button>
                        </div>

                        <div v-if="form.errors.general" class="mt-1 text-sm text-red-600 dark:text-red-400 text-right">
                            <font-awesome-icon :icon="['fas', 'exclamation-circle']" class="mr-1" /> {{
                            form.errors.general }}
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </MainLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3'; // 引入 Head
import MainLayout from '@/Layouts/MainLayouts/MainLayout.vue';
import Editor from '@/Components/Wiki/Editor.vue';
import InputError from '@/Components/Other/InputError.vue'; // 确保InputError组件已正确导入
import { mainNavigationLinks } from '@/config/navigationConfig';

const navigationLinks = mainNavigationLinks;

const props = defineProps({
    categories: { type: Array, required: true },
    tags: { type: Array, required: true },
    errors: Object // 接收后端验证错误
});

const form = useForm({
    title: '',
    content: '<p></p>',
    category_ids: [],
    tag_ids: [],
});

const tiptapEditorRef = ref(null);

const createPage = () => {
    if (tiptapEditorRef.value && tiptapEditorRef.value.editor) {
        form.content = tiptapEditorRef.value.editor.getHTML();
    }
    form.post(route('wiki.store'), {
        onError: (pageErrors) => { // 使用 pageErrors 避免与 props.errors 混淆
            console.error("创建页面失败:", pageErrors);
            // 更新表单的错误状态，这会自动触发 InputError 显示
            // useForm 内部通常会处理这个，但如果需要特定逻辑可以放在这里
            if (!pageErrors.title && !pageErrors.content && !pageErrors.category_ids && !pageErrors.tag_ids) {
                form.setError('general', '创建页面时发生未知错误。');
            }
        }
    });
};
</script>

<style scoped>
/* 添加统一的表单元素样式，确保包含暗色模式 */
.input-field,
.textarea-field,
select {
    @apply w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-800 dark:border-gray-600 dark:text-gray-200 dark:placeholder-gray-500;
}

.checkbox-group {
    @apply grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 max-h-60 overflow-y-auto p-3 border border-gray-300 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800/50;
}

.checkbox {
    @apply h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:focus:ring-blue-600 dark:ring-offset-gray-800;
}

.btn-primary {
    @apply px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition disabled:opacity-50 disabled:cursor-not-allowed;
}

.btn-secondary {
    @apply px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition dark:bg-gray-600 dark:text-gray-200 dark:hover:bg-gray-500;
}
</style>