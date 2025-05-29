<script setup>
import { Link } from '@inertiajs/vue3';

// 定义属性，接收链接的目标地址
defineProps({
    href: {
        type: String,         // 链接地址的字符串类型
        required: true,       // 必填项
    },
});
</script>

<template>
    <!-- 下拉链接项，点击后导航到指定 href -->
    <Link :href="href"
        class="block w-full px-4 py-2 text-start bg-sky-400 text-sm leading-5 text-black transition duration-150 ease-in-out hover:bg-cyan-300 focus:bg-cyan-500 focus:outline-none">
    <!-- 插槽内容，用于显示链接的文本 -->
    <slot />
    </Link>
</template>
