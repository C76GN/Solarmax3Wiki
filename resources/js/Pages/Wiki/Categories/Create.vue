<template>
    <MainLayout :navigationLinks="navigationLinks">

        <Head title="创建 Wiki 分类" />
        <div class="container mx-auto py-6 px-4">
            <div class="max-w-2xl mx-auto bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm rounded-lg shadow-lg p-6">
                <div class="flex justify-between items-center mb-6 pb-4 border-b dark:border-gray-700">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">创建新分类</h1>
                    <Link :href="route('wiki.categories.index')"
                        class="text-blue-600 dark:text-blue-400 hover:underline text-sm">
                    <font-awesome-icon :icon="['fas', 'arrow-left']" class="mr-1" /> 返回列表
                    </Link>
                </div>

                <form @submit.prevent="createCategory">
                    <div class="space-y-6">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                分类名称 <span class="text-red-500">*</span>
                            </label>
                            <input id="name" v-model="form.name" type="text" class="input-field" required />
                            <InputError class="mt-1" :message="form.errors.name" />
                        </div>
                        <div>
                            <label for="description"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                分类描述
                            </label>
                            <textarea id="description" v-model="form.description" rows="3"
                                class="textarea-field"></textarea>
                            <InputError class="mt-1" :message="form.errors.description" />
                        </div>
                        <div>
                            <label for="parent_id"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                父分类
                            </label>
                            <select id="parent_id" v-model="form.parent_id" class="select-field">
                                <option :value="null">-- 无（设为顶级分类） --</option>
                                <option v-for="category in categories" :key="category.id" :value="category.id">
                                    {{ category.name }}
                                </option>
                            </select>
                            <InputError class="mt-1" :message="form.errors.parent_id" />
                        </div>
                        <div>
                            <label for="order" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                排序顺序
                            </label>
                            <input id="order" v-model="form.order" type="number" min="0" class="input-field" />
                            <InputError class="mt-1" :message="form.errors.order" />
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                数字越小，排序越靠前。默认为0。
                            </p>
                        </div>

                        <div class="flex justify-end gap-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                            <Link :href="route('wiki.categories.index')" class="btn-secondary">
                            取消
                            </Link>
                            <button type="submit" class="btn-primary" :disabled="form.processing">
                                <font-awesome-icon v-if="form.processing" :icon="['fas', 'spinner']" spin
                                    class="mr-1" />
                                {{ form.processing ? '创建中...' : '创建分类' }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </MainLayout>
</template>

<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayouts/MainLayout.vue';
import InputError from '@/Components/Other/InputError.vue'; // Make sure this path is correct
import { adminNavigationLinks } from '@/config/navigationConfig';

const navigationLinks = adminNavigationLinks;

const props = defineProps({
    categories: { // List of potential parent categories
        type: Array,
        required: true
    },
    errors: Object // Passed by Inertia on validation failure
});

const form = useForm({
    name: '',
    description: '',
    parent_id: null, // Default to top-level
    order: 0
});

const createCategory = () => {
    form.post(route('wiki.categories.store'), {
        preserveScroll: true,
        onError: (pageErrors) => {
            console.error("创建分类失败:", pageErrors);
            // Optional: Add a general error message if specific field errors aren't helpful
            if (!pageErrors.name && !pageErrors.parent_id) {
                form.setError('general', '创建分类时发生未知错误。');
            }
        }
    });
};
</script>

<style scoped>
/* Shared form field styles */
.input-field,
.textarea-field,
.select-field {
    @apply w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 dark:placeholder-gray-400 text-sm;
}

/* Button styles */
.btn-primary {
    @apply px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed;
}

.btn-secondary {
    @apply px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition dark:bg-gray-600 dark:text-gray-200 dark:hover:bg-gray-500 text-sm font-medium;
}
</style>