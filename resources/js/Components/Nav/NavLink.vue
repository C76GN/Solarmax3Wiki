<!-- 导航链接组件，支持不同的样式显示，基于当前链接的状态（活动或非活动）显示不同的样式 -->
<template>
    <!-- 使用 Inertia.js 的 Link 组件，绑定动态样式和链接地址 -->
    <Link :href="href" :class="classes">
    <!-- 插槽，用于在链接中插入动态内容 -->
    <slot />
    </Link>
</template>

<script setup>
import { computed } from 'vue';                // 导入 Vue 的 computed 计算属性
import { Link } from '@inertiajs/vue3';        // 导入 Inertia.js 的 Link 组件

// 定义接收的 props 参数
const props = defineProps({
    href: {
        type: String,                          // 链接地址
        required: true,                        // 必填项
    },
    active: {
        type: Boolean,                         // 活动状态，决定样式变化
        default: false,                        // 默认值为 false（非活动状态）
    },
});

// 定义基础样式类（用于所有导航链接）
const baseClass = 'inline-flex items-center px-1 pt-1 border-b-2 text-xl transition duration-300 ease-in-out whitespace-nowrap';
// 定义鼠标悬浮时的样式
const hoverClass = 'hover:text-gray-700 hover:border-gray-300';
// 活动状态下的样式：背景渐变色和加粗字体
const activeClass = 'bg-clip-text text-transparent bg-gradient-to-r from-blue-950 to-sky-900 border-blue-950 font-extrabold';
// 非活动状态下的样式：边框透明和字体颜色灰色
const inactiveClass = 'border-transparent font-medium text-slate-800';

// 计算属性 classes：根据 active 状态动态设置样式
const classes = computed(() =>
    `${baseClass} ${props.active ? activeClass : inactiveClass} ${hoverClass}`
);
</script>
