<template>
    <MainLayout :navigationLinks="navigationLinks">

        <Head :title="`编辑分类: ${category.name}`" />
        <div class="container mx-auto py-6 px-4">
            <div class="max-w-2xl mx-auto bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm rounded-lg shadow-lg p-6">
                <div class="flex justify-between items-center mb-6 pb-4 border-b dark:border-gray-700">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">编辑分类: {{ form.name }}</h1>
                    <Link :href="route('wiki.categories.index')"
                        class="text-blue-600 dark:text-blue-400 hover:underline text-sm">
                    <font-awesome-icon :icon="['fas', 'arrow-left']" class="mr-1" /> 返回列表
                    </Link>
                </div>

                <form @submit.prevent="updateCategory">
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
                                <option v-for="cat in availableParents" :key="cat.id" :value="cat.id">
                                    {{ cat.name }}
                                </option>
                            </select>
                            <InputError class="mt-1" :message="form.errors.parent_id" />
                            <p v-if="isSelfParentSelected" class="mt-1 text-xs text-red-500">不能选择自己作为父分类。</p>
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
                            <button type="submit" class="btn-primary"
                                :disabled="form.processing || isSelfParentSelected">
                                <font-awesome-icon v-if="form.processing" :icon="['fas', 'spinner']" spin
                                    class="mr-1" />
                                {{ form.processing ? '更新中...' : '更新分类' }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </MainLayout>
</template>

<script setup>
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayouts/MainLayout.vue';
import InputError from '@/Components/Other/InputError.vue'; // Make sure path is correct
import { adminNavigationLinks } from '@/config/navigationConfig';

const navigationLinks = adminNavigationLinks;

const props = defineProps({
    category: { // The category being edited
        type: Object,
        required: true
    },
    categories: { // All other categories (for parent selection)
        type: Array,
        required: true
    },
    errors: Object // Passed by Inertia
});

const form = useForm({
    name: props.category.name,
    description: props.category.description || '',
    parent_id: props.category.parent_id || null, // Ensure null if no parent
    order: props.category.order || 0
});

// Filter out the current category from the parent selection list
const availableParents = computed(() => {
    return props.categories.filter(cat => cat.id !== props.category.id);
});

// Computed property to check if the current category is selected as its own parent
const isSelfParentSelected = computed(() => {
    return form.parent_id === props.category.id;
});

const updateCategory = () => {
    if (isSelfParentSelected.value) {
        // This check should ideally be done on the backend too, but good for UX
        form.setError('parent_id', '分类不能选择自己作为父分类。');
        return;
    }
    form.put(route('wiki.categories.update', props.category.id), {
        preserveScroll: true,
        onError: (pageErrors) => {
            console.error("更新分类失败:", pageErrors);
            if (!pageErrors.name && !pageErrors.parent_id) {
                form.setError('general', '更新分类时发生未知错误。');
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