<template>
    <!-- Link 组件 -->
    <Link :href="href" :class="[ // 动态绑定 class，根据 active 状态应用不同的样式
        commonClass, // 应用通用样式
        active
            // 如果 active 为 true，应用激活状态样式
            ? 'bg-sky-600 text-xl font-extrabold text-black hover:bg-sky-500'
            // 如果 active 为 false，应用非激活状态样式
            : 'bg-sky-400 text-xl font-medium text-slate-700 hover:bg-sky-500 hover:font-extrabold'
    ]">
    <!-- 插槽，用于插入链接文本或内容 -->
    <slot />
    </Link>
</template>

<script setup>
// 导入 Link 组件用于导航链接
import { Link } from '@inertiajs/vue3';
// 从 Vue 导入 defineProps 用于定义组件的属性
import { defineProps } from 'vue';

// 定义组件的 props 属性
const props = defineProps({
    // 链接的 href 属性，字符串类型，必填
    href: {
        type: String,
        required: true,
    },
    // 判断是否激活状态的 active 属性，布尔类型，默认值为 false
    active: {
        type: Boolean,
        default: false,
    },
});

// 定义一个通用的类名字符串 commonClass，用于为所有链接添加基本样式
const commonClass = 'inline-flex items-center px-1 pt-1 transition duration-300 ease-in-out whitespace-nowrap focus:outline-none block w-full ps-3 pe-4 py-2';
</script>



<style scoped>
/* 可选样式区域，可以在此添加其他必要的样式 */
</style>
