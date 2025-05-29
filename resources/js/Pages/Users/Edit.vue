<template>
    <!-- 主要布局容器，引入导航链接 -->
    <MainLayout :navigationLinks="navigationLinks">

        <!-- 设置页面标题 -->

        <Head :title="`编辑用户: ${user.name}`" />
        <div class="container mx-auto py-6 px-4">
            <!-- 页面内容主体卡片 -->
            <div class="max-w-3xl mx-auto bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm rounded-lg shadow-lg p-6">
                <!-- 页面头部，包含标题和返回按钮 -->
                <div class="flex justify-between items-center mb-6 pb-4 border-b dark:border-gray-700">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">编辑用户: {{ user.name }}</h1>
                    <!-- 返回用户列表的链接 -->
                    <Link :href="route('users.index')" class="text-blue-600 dark:text-blue-400 hover:underline text-sm">
                    <font-awesome-icon :icon="['fas', 'arrow-left']" class="mr-1" /> 返回列表
                    </Link>
                </div>

                <!-- 用户角色编辑表单 -->
                <form @submit.prevent="submit">
                    <div class="space-y-6">
                        <!-- 用户邮箱显示区域 -->
                        <div>
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">邮箱</label>
                            <div class="mt-1 text-sm font-medium text-gray-900 dark:text-gray-200">
                                {{ user.email }}
                            </div>
                        </div>
                        <!-- 角色分配区域 -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-4">
                                分配角色 <span class="text-red-500">*</span>
                            </label>
                            <!-- 角色列表，包含复选框 -->
                            <div
                                class="space-y-4 border border-gray-200 dark:border-gray-700 rounded-lg p-4 bg-gray-50 dark:bg-gray-800/50">
                                <div v-for="role in roles" :key="role.id" class="flex items-start">
                                    <div class="flex items-center h-5">
                                        <input type="checkbox" :id="`role-${role.id}`" :value="role.id"
                                            v-model="form.roles"
                                            class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded dark:bg-gray-600 dark:border-gray-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 disabled:cursor-not-allowed"
                                            :disabled="isSelfAdminAndTryingToRemoveAdmin(role)">
                                    </div>
                                    <div class="ml-3 text-sm">
                                        <label :for="`role-${role.id}`"
                                            class="font-medium text-gray-700 dark:text-gray-300"
                                            :class="{ 'opacity-50': isSelfAdminAndTryingToRemoveAdmin(role) }">
                                            {{ role.display_name }}
                                            <!-- 系统角色标签 -->
                                            <span v-if="role.is_system"
                                                class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300">
                                                系统
                                            </span>
                                        </label>
                                        <p class="text-gray-500 dark:text-gray-400">{{ role.description || '无描述' }}</p>
                                        <!-- 如果用户尝试移除自己的管理员角色，显示提示 -->
                                        <p v-if="isSelfAdminAndTryingToRemoveAdmin(role)"
                                            class="text-xs text-yellow-600 dark:text-yellow-400 mt-1 italic">
                                            不能移除自己的管理员角色。</p>
                                    </div>
                                </div>
                            </div>
                            <!-- 角色选择错误信息 -->
                            <div v-if="form.errors.roles" class="mt-1 text-sm text-red-600 dark:text-red-400">
                                {{ form.errors.roles }}
                            </div>
                        </div>
                    </div>
                    <!-- 表单提交按钮区域 -->
                    <div class="mt-8 flex justify-end gap-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <!-- 取消按钮 -->
                        <Link :href="route('users.index')" class="btn-secondary">
                        取消
                        </Link>
                        <!-- 提交按钮，显示处理状态 -->
                        <button type="submit" :disabled="form.processing" class="btn-primary">
                            <font-awesome-icon v-if="form.processing" :icon="['fas', 'spinner']" spin class="mr-1" />
                            {{ form.processing ? '正在保存...' : '保存更改' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </MainLayout>
</template>

<script setup>
import { useForm, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import MainLayout from "@/Layouts/MainLayouts/MainLayout.vue";
import { adminNavigationLinks } from '@/config/navigationConfig';

// 管理员导航链接配置
const navigationLinks = adminNavigationLinks;
// 获取 Inertia 页面属性
const pageProps = usePage().props;

// 定义组件接收的属性
const props = defineProps({
    user: {
        type: Object,
        required: true
    },
    roles: {
        type: Array,
        required: true
    }
});

// 表单数据，初始化为当前用户的角色ID数组
const form = useForm({
    roles: Array.isArray(props.user.roles) ? props.user.roles : []
});

/**
 * 检查当前用户是否正在尝试移除自己的管理员角色。
 * 如果是，则禁用对应的管理员角色复选框。
 * @param {Object} roleToCheck - 要检查的角色对象
 * @returns {boolean} - 如果是当前用户且是管理员角色，返回 true
 */
const isSelfAdminAndTryingToRemoveAdmin = (roleToCheck) => {
    const currentUser = pageProps.auth.user;
    if (!currentUser) return false;

    // 判断是否正在编辑当前登录用户
    // 并且当前角色是'admin'
    // 并且用户当前确实拥有'admin'角色
    // 并且尝试在新的角色列表中移除'admin'角色
    return props.user.id === currentUser.id &&
        roleToCheck.name === 'admin' &&
        props.user.roles.some(r => r.name === 'admin') &&
        !form.roles.includes(roleToCheck.id);
};

// 提交表单
const submit = () => {
    // 确保角色ID都是数字类型，并过滤掉无效值
    form.roles = form.roles.map(id => typeof id === 'string' ? parseInt(id, 10) : id).filter(id => !isNaN(id));

    // 发送 PUT 请求更新用户角色
    form.put(route('users.update', props.user.id), {
        preserveScroll: true, // 保持滚动位置
    });
};
</script>

<style scoped>
/* 主要按钮样式 */
.btn-primary {
    @apply px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed;
}

/* 次要按钮样式 */
.btn-secondary {
    @apply px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition dark:bg-gray-600 dark:text-gray-200 dark:hover:bg-gray-500 text-sm font-medium;
}
</style>