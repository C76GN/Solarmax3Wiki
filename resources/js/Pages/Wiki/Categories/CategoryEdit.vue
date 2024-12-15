<template>
    <MainLayout
        :navigationLinks="[{ href: '/wiki', label: '游戏维基' }, { href: '#', label: '游戏历史&名人墙' }, { href: '#', label: '自制专区' }, { href: '#', label: '攻略专区' }, { href: '#', label: '论坛' }]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white/80 backdrop-blur-sm shadow-lg rounded-lg overflow-hidden">
                <div class="p-6">
                    <h2 class="text-2xl font-semibold text-gray-900 mb-6">编辑分类</h2>
                    <form @submit.prevent="submit" class="space-y-6">
                        <!-- 分类名称 -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700">
                                分类名称
                            </label>
                            <input type="text" v-model="form.name"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                :class="{ 'border-red-500': form.errors.name }">
                            <div v-if="form.errors.name" class="mt-1 text-sm text-red-600">
                                {{ form.errors.name }}
                            </div>
                        </div>

                        <!-- 父分类 -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700">
                                父分类
                            </label>
                            <select v-model="form.parent_id"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                <option value="">无</option>
                                <option v-for="category in categories" :key="category.id" :value="category.id">
                                    {{ category.name }}
                                </option>
                            </select>
                            <div v-if="form.errors.parent_id" class="mt-1 text-sm text-red-600">
                                {{ form.errors.parent_id }}
                            </div>
                        </div>

                        <!-- 分类描述 -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700">
                                分类描述
                            </label>
                            <textarea v-model="form.description" rows="3"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"></textarea>
                            <div v-if="form.errors.description" class="mt-1 text-sm text-red-600">
                                {{ form.errors.description }}
                            </div>
                        </div>

                        <!-- 排序 -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700">
                                排序
                            </label>
                            <input type="number" v-model="form.order"
                                class="mt-1 block w-32 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <div v-if="form.errors.order" class="mt-1 text-sm text-red-600">
                                {{ form.errors.order }}
                            </div>
                        </div>

                        <!-- 提交按钮 -->
                        <div class="flex justify-end gap-4">
                            <Link :href="route('wiki.categories.index')"
                                class="px-4 py-2 text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200 transition duration-150 ease-in-out">
                            取消
                            </Link>
                            <button type="submit" :disabled="form.processing"
                                class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 transition duration-150 ease-in-out disabled:opacity-50">
                                更新分类
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

const props = defineProps({
    category: {
        type: Object,
        required: true
    },
    categories: {
        type: Array,
        required: true
    }
});

// 初始化表单时使用传入的 category 数据
const form = useForm({
    name: props.category.name,
    description: props.category.description,
    parent_id: props.category.parent_id,
    order: props.category.order
});

// 使用 put 方法更新分类
const submit = () => {
    form.put(route('wiki.categories.update', props.category.id));
};
</script>