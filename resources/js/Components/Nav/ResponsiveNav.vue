<template>
    <!-- 整个导航容器 -->
    <div class="relative w-full bg-sky-400 shadow-lg z-40 overflow-hidden">
        <!-- 导航链接部分 -->
        <div class="space-y-1 pb-3 pt-2">
            <!-- 遍历 navigationLinks 数组，渲染每个导航链接 -->
            <ResponsiveNavLink v-for="(link, index) in navigationLinks" :key="index" :href="link.href"
                :active="isActive(link.href)">
                {{ link.label }}
            </ResponsiveNavLink>
        </div>

        <!-- 用户信息区域 -->
        <div class="border-t-4 border-black pb-1 pt-4">
            <!-- 判断用户是否登录，显示不同的内容 -->
            <div v-if="user" class="px-4">
                <!-- 已登录用户显示用户名和邮箱 -->
                <div class="text-base font-medium text-gray-800">{{ user.name }}
                </div>
                <div class="text-sm font-medium text-gray-500">{{ user.email }}
                </div>
                <div class="mt-3 space-y-1">
                    <!-- 已登录用户显示个人资料和登出链接 -->
                    <ResponsiveNavLink href="/profile.edit">Profile</ResponsiveNavLink>
                    <ResponsiveNavLink href="/logout" method="post" as="button">Log Out</ResponsiveNavLink>
                </div>
            </div>
            <div v-else class="mt-3 space-y-1">
                <!-- 未登录用户显示登录和注册链接 -->
                <ResponsiveNavLink href="/login">登录</ResponsiveNavLink>
                <ResponsiveNavLink href="/register">注册</ResponsiveNavLink>
            </div>
        </div>
    </div>
</template>

<script setup>
import ResponsiveNavLink from '@/Components/Nav/ResponsiveNavLink.vue';
import { usePage } from '@inertiajs/vue3';

// 定义组件的 props
const props = defineProps({
    navigationLinks: {
        type: Array,
        default: () => [], // navigationLinks 是一个数组，默认值为空数组
    },
    user: Object, // user 是一个对象，用于存储用户信息
});

// 使用 Inertia 提供的 usePage 获取当前页面信息
const page = usePage();

// 函数 isActive 用于判断当前导航链接是否激活
function isActive(link) {
    return page.url === link; // 如果当前页面 URL 等于链接的 href，则返回 true
}
</script>
