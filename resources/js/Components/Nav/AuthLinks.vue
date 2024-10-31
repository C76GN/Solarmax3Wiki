<template>
    <!-- 登录和注册链接容器：用于放置两个链接按钮（登录和注册） -->
    <!-- 'flex' 设置为水平排列，'space-x-4' 设置两个链接之间的水平间距 -->
    <div class="flex space-x-4">
        <!-- 登录链接 -->
        <Link href="/login" class="linkClass ">
        登录
        </Link>
        <!-- 注册链接 -->
        <Link href="/register" class="linkClass ">
        注册
        </Link>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';  // 从 Inertia.js 导入 Link 组件，用于页面导航

// 公共样式类
// 'transition' 添加过渡效果，'hover:text-black' 鼠标悬停时切换为黑色文本
// 指向登录页面的链接，文本大小为 lg，字体为 medium，默认颜色为 text-slate-800
const linkClass = 'text-lg font-medium text-slate-800 transition hover:text-black';
</script>
