<template>
    <!-- 主要布局容器，引入导航链接 -->
    <MainLayout :navigationLinks="navigationLinks">

        <!-- 设置页面标题为“创建 Wiki 分类” -->

        <Head title="创建 Wiki 分类" />
        <div class="container mx-auto py-6 px-4">
            <!-- 页面内容主体卡片 -->
            <div class="max-w-2xl mx-auto bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm rounded-lg shadow-lg p-6">
                <!-- 页面头部，包含标题和返回按钮 -->
                <div class="flex justify-between items-center mb-6 pb-4 border-b dark:border-gray-700">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">创建新分类</h1>
                    <!-- 返回分类列表的链接 -->
                    <Link :href="route('wiki.categories.index')"
                        class="text-blue-600 dark:text-blue-400 hover:underline text-sm">
                    <font-awesome-icon :icon="['fas', 'arrow-left']" class="mr-1" /> 返回列表
                    </Link>
                </div>

                <!-- 创建分类表单 -->
                <form @submit.prevent="createCategory">
                    <div class="space-y-6">
                        <!-- 分类名称输入字段 -->
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                分类名称 <span class="text-red-500">*</span>
                            </label>
                            <input id="name" v-model="form.name" type="text" class="input-field" required />
                            <!-- 显示分类名称相关的错误信息 -->
                            <InputError class="mt-1" :message="form.errors.name" />
                        </div>
                        <!-- 分类描述文本区域 -->
                        <div>
                            <label for="description"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                分类描述
                            </label>
                            <textarea id="description" v-model="form.description" rows="3"
                                class="textarea-field"></textarea>
                            <!-- 显示分类描述相关的错误信息 -->
                            <InputError class="mt-1" :message="form.errors.description" />
                        </div>
                        <!-- 父分类选择下拉框 -->
                        <div>
                            <label for="parent_id"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                父分类
                            </label>
                            <select id="parent_id" v-model="form.parent_id" class="select-field">
                                <!-- 默认选项：无父分类（顶级分类） -->
                                <option :value="null">-- 无（设为顶级分类） --</option>
                                <!-- 遍历并显示所有可选的父分类 -->
                                <option v-for="category in categories" :key="category.id" :value="category.id">
                                    {{ category.name }}
                                </option>
                            </select>
                            <!-- 显示父分类相关的错误信息 -->
                            <InputError class="mt-1" :message="form.errors.parent_id" />
                        </div>
                        <!-- 排序顺序输入字段 -->
                        <div>
                            <label for="order" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                排序顺序
                            </label>
                            <input id="order" v-model="form.order" type="number" min="0" class="input-field" />
                            <!-- 显示排序顺序相关的错误信息 -->
                            <InputError class="mt-1" :message="form.errors.order" />
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                数字越小，排序越靠前。默认为0。
                            </p>
                        </div>

                        <!-- 表单提交按钮区域 -->
                        <div class="flex justify-end gap-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                            <!-- 取消按钮 -->
                            <Link :href="route('wiki.categories.index')" class="btn-secondary">
                            取消
                            </Link>
                            <!-- 提交按钮，显示处理状态和文本 -->
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
import InputError from '@/Components/Other/InputError.vue';
import { adminNavigationLinks } from '@/config/navigationConfig';

// 管理员导航链接配置
const navigationLinks = adminNavigationLinks;

// 定义组件接收的属性
const props = defineProps({
    categories: { // 父分类列表
        type: Array,
        required: true
    },
    errors: Object // Inertia 传递的验证错误对象
});

// 使用 useForm 创建响应式表单数据
const form = useForm({
    name: '',
    description: '',
    parent_id: null, // 默认设为顶级分类
    order: 0
});

// 提交创建分类的函数
const createCategory = () => {
    // 发送 POST 请求到分类存储路由
    form.post(route('wiki.categories.store'), {
        preserveScroll: true, // 保持滚动位置
        onError: (pageErrors) => {
            console.error("创建分类失败:", pageErrors);
            // 如果没有特定的字段错误，则设置一个通用错误信息
            if (!pageErrors.name && !pageErrors.parent_id) {
                form.setError('general', '创建分类时发生未知错误。');
            }
        }
    });
};
</script>

<style scoped>
/* 表单输入、文本区域和选择框的通用样式 */
.input-field,
.textarea-field,
.select-field {
    @apply w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 dark:placeholder-gray-400 text-sm;
}

/* 主要按钮样式 */
.btn-primary {
    @apply px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed;
}

/* 次要按钮样式 */
.btn-secondary {
    @apply px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition dark:bg-gray-600 dark:text-gray-200 dark:hover:bg-gray-500 text-sm font-medium;
}
</style>