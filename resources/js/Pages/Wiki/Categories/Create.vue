<template>
    <MainLayout :navigationLinks="navigationLinks">
        <div class="container mx-auto py-6 px-4">
            <div class="bg-white/80 backdrop-blur-sm rounded-lg shadow-lg p-6">
                <div class="flex justify-between items-center mb-6">
                    <h1 class="text-2xl font-bold">创建新分类</h1>
                    <Link :href="route('wiki.categories.index')" class="text-blue-600 hover:text-blue-800">
                    返回分类列表
                    </Link>
                </div>

                <form @submit.prevent="createCategory">
                    <div class="space-y-6">
                        <!-- 分类名称 -->
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                                分类名称 <span class="text-red-500">*</span>
                            </label>
                            <input id="name" v-model="form.name" type="text"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                required />
                            <div v-if="form.errors.name" class="mt-1 text-sm text-red-600">
                                {{ form.errors.name }}
                            </div>
                        </div>

                        <!-- 分类描述 -->
                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700 mb-1">
                                分类描述
                            </label>
                            <textarea id="description" v-model="form.description" rows="3"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"></textarea>
                            <div v-if="form.errors.description" class="mt-1 text-sm text-red-600">
                                {{ form.errors.description }}
                            </div>
                        </div>

                        <!-- 父分类 -->
                        <div>
                            <label for="parent_id" class="block text-sm font-medium text-gray-700 mb-1">
                                父分类
                            </label>
                            <select id="parent_id" v-model="form.parent_id"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option :value="null">无（顶级分类）</option>
                                <option v-for="category in categories" :key="category.id" :value="category.id">
                                    {{ category.name }}
                                </option>
                            </select>
                            <div v-if="form.errors.parent_id" class="mt-1 text-sm text-red-600">
                                {{ form.errors.parent_id }}
                            </div>
                        </div>

                        <!-- 排序顺序 -->
                        <div>
                            <label for="order" class="block text-sm font-medium text-gray-700 mb-1">
                                排序顺序
                            </label>
                            <input id="order" v-model="form.order" type="number" min="0"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
                            <div v-if="form.errors.order" class="mt-1 text-sm text-red-600">
                                {{ form.errors.order }}
                            </div>
                            <p class="mt-1 text-sm text-gray-500">
                                数字越小，排序越靠前。默认为0。
                            </p>
                        </div>

                        <!-- 提交按钮 -->
                        <div class="flex justify-end space-x-3">
                            <Link :href="route('wiki.categories.index')"
                                class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                            取消
                            </Link>
                            <button type="submit"
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
                                :disabled="form.processing">
                                创建分类
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

const navigationLinks = [
    { href: '/wiki', label: 'Wiki' },
    { href: '/wiki/categories', label: '分类管理' },
    { href: '/wiki/tags', label: '标签管理' },
    { href: '#', label: '模板管理' },
];

const props = defineProps({
    categories: {
        type: Array,
        required: true
    }
});

const form = useForm({
    name: '',
    description: '',
    parent_id: null,
    order: 0
});

const createCategory = () => {
    form.post(route('wiki.categories.store'));
};
</script>