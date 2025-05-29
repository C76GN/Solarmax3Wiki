<template>
    <!-- 主要布局容器，引入导航链接 -->
    <MainLayout :navigationLinks="navigationLinks">

        <!-- 设置页面标题，动态显示当前编辑的分类名称 -->

        <Head :title="`编辑分类: ${category.name}`" />
        <div class="container mx-auto py-6 px-4">
            <!-- 页面内容主体卡片 -->
            <div class="max-w-2xl mx-auto bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm rounded-lg shadow-lg p-6">
                <!-- 页面头部，包含标题和返回列表的链接 -->
                <div class="flex justify-between items-center mb-6 pb-4 border-b dark:border-gray-700">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">编辑分类: {{ form.name }}</h1>
                    <!-- 返回 Wiki 分类列表的链接 -->
                    <Link :href="route('wiki.categories.index')"
                        class="text-blue-600 dark:text-blue-400 hover:underline text-sm">
                    <font-awesome-icon :icon="['fas', 'arrow-left']" class="mr-1" /> 返回列表
                    </Link>
                </div>

                <!-- 分类编辑表单，阻止默认提交行为，调用 updateCategory 方法 -->
                <form @submit.prevent="updateCategory">
                    <div class="space-y-6">
                        <!-- 分类名称输入框 -->
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                分类名称 <span class="text-red-500">*</span>
                            </label>
                            <input id="name" v-model="form.name" type="text" class="input-field" required />
                            <!-- 显示分类名称的错误信息 -->
                            <InputError class="mt-1" :message="form.errors.name" />
                        </div>
                        <!-- 分类描述文本域 -->
                        <div>
                            <label for="description"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                分类描述
                            </label>
                            <textarea id="description" v-model="form.description" rows="3"
                                class="textarea-field"></textarea>
                            <!-- 显示分类描述的错误信息 -->
                            <InputError class="mt-1" :message="form.errors.description" />
                        </div>
                        <!-- 父分类选择下拉框 -->
                        <div>
                            <label for="parent_id"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                父分类
                            </label>
                            <select id="parent_id" v-model="form.parent_id" class="select-field">
                                <option :value="null">-- 无（设为顶级分类） --</option>
                                <!-- 遍历可用的父分类选项，排除当前分类自身 -->
                                <option v-for="cat in availableParents" :key="cat.id" :value="cat.id">
                                    {{ cat.name }}
                                </option>
                            </select>
                            <!-- 显示父分类选择的错误信息 -->
                            <InputError class="mt-1" :message="form.errors.parent_id" />
                            <!-- 如果选择自己作为父分类，显示警告信息 -->
                            <p v-if="isSelfParentSelected" class="mt-1 text-xs text-red-500">不能选择自己作为父分类。</p>
                        </div>
                        <!-- 排序顺序输入框 -->
                        <div>
                            <label for="order" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                排序顺序
                            </label>
                            <input id="order" v-model="form.order" type="number" min="0" class="input-field" />
                            <!-- 显示排序顺序的错误信息 -->
                            <InputError class="mt-1" :message="form.errors.order" />
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                数字越小，排序越靠前。默认为0。
                            </p>
                        </div>

                        <!-- 表单底部操作按钮区域 -->
                        <div class="flex justify-end gap-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                            <!-- 取消按钮，点击返回列表页 -->
                            <Link :href="route('wiki.categories.index')" class="btn-secondary">
                            取消
                            </Link>
                            <!-- 提交按钮，根据处理状态和父分类选择禁用 -->
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
import InputError from '@/Components/Other/InputError.vue';
import { adminNavigationLinks } from '@/config/navigationConfig';

// 导入管理员导航链接配置
const navigationLinks = adminNavigationLinks;

// 定义组件接收的 props
const props = defineProps({
    category: { // 当前正在编辑的分类对象
        type: Object,
        required: true
    },
    categories: { // 所有分类的数组，用于父分类的选择列表
        type: Array,
        required: true
    },
    errors: Object // Inertia 传递的错误对象
});

// 使用 useForm 创建表单状态，并用当前分类数据初始化
const form = useForm({
    name: props.category.name,
    description: props.category.description || '', // 如果描述为空则默认为空字符串
    parent_id: props.category.parent_id || null, // 如果没有父分类则默认为 null
    order: props.category.order || 0 // 如果排序顺序为空则默认为 0
});

// 计算属性：过滤掉当前分类自身，用于父分类下拉列表
const availableParents = computed(() => {
    return props.categories.filter(cat => cat.id !== props.category.id);
});

// 计算属性：检查当前分类是否被选为自己的父分类
const isSelfParentSelected = computed(() => {
    return form.parent_id === props.category.id;
});

// 更新分类的方法
const updateCategory = () => {
    // 客户端校验：如果选择自己作为父分类，则设置错误并阻止提交
    if (isSelfParentSelected.value) {
        form.setError('parent_id', '分类不能选择自己作为父分类。');
        return;
    }
    // 发送 PUT 请求更新分类数据
    form.put(route('wiki.categories.update', props.category.id), {
        preserveScroll: true, // 保持页面滚动位置
        onError: (pageErrors) => {
            console.error("更新分类失败:", pageErrors);
            // 如果没有特定的名称或父分类错误，则显示通用错误
            if (!pageErrors.name && !pageErrors.parent_id) {
                form.setError('general', '更新分类时发生未知错误。');
            }
        }
    });
};
</script>

<style scoped>
/* 所有输入框、文本域和选择框的通用样式 */
.input-field,
.textarea-field,
.select-field {
    @apply w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 dark:placeholder-gray-400 text-sm;
}

/* 主要操作按钮样式 */
.btn-primary {
    @apply px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed;
}

/* 次要操作按钮样式 */
.btn-secondary {
    @apply px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition dark:bg-gray-600 dark:text-gray-200 dark:hover:bg-gray-500 text-sm font-medium;
}
</style>