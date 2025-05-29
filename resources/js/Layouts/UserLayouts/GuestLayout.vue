<template>
    <div class="flex min-h-screen flex-col items-center pt-6 sm:justify-center sm:pt-0 p-6 text-white relative bg-center bg-cover bg-fixed"
        style="background-image: url('/images/BG2.webp');">
        <!-- StarrySky 背景组件，绝对定位覆盖整个屏幕，不响应鼠标事件，创建动态星空效果 -->
        <StarrySky class="absolute inset-0 pointer-events-none" />

        <!-- Logo 区域，确保Logo在星空背景之上 -->
        <div class="w-full max-w-xs sm:max-w-md h-auto z-10">
            <!-- 导航链接，点击Logo返回首页 -->
            <Link href="/">
            <!-- AppLogo 组件，填充当前文本颜色，保持内容比例，并添加交互动画效果 -->
            <AppLogo
                class="fill-current object-contain text-cyan-500 hover:scale-105 transition duration-300 ease-in-out" />
            </Link>
        </div>

        <!-- 表单区域，确保表单在星空背景之上且具有半透明背景效果 -->
        <div
            class="mt-6 overflow-hidden px-6 py-4 sm:max-w-md sm:rounded-lg bg-black bg-opacity-40 rounded-lg shadow-md w-full sm:w-96 transition-all duration-300 ease-in-out z-10">
            <!-- 渲染父组件传入的内容，通常是认证相关的表单（如登录、注册） -->
            <slot />
        </div>
    </div>
</template>

<script setup>
// 导入StarrySky组件，用于背景的星空效果
import StarrySky from '@/Components/Other/StarrySky.vue';
// 导入AppLogo组件，用于显示应用程序的Logo
import AppLogo from '@/Components/Svg/AppLogo.vue';
// 导入Inertia.js的Link组件，用于客户端路由导航
import { Link } from '@inertiajs/vue3';
</script>