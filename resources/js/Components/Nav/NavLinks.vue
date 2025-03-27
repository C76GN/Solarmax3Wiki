<template>
    <div ref="navLinksContainer" class="hidden md:flex space-x-2 sm:-my-px sm:ms-10">
        <NavLink v-for="(link, index) in navigationLinks" :key="index" :href="link.href" :active="isActive(link.href)">
            <!-- 显示链接文本 -->
            {{ link.label }}
        </NavLink>
    </div>
</template>

<script setup>
import NavLink from '@/Components/Nav/NavLink.vue';     // 导入自定义的 NavLink 组件
import { usePage } from '@inertiajs/vue3';              // 导入 usePage 函数，用于获取当前页面信息

// 定义组件接收的 props 参数
const props = defineProps({
    navigationLinks: {
        type: Array,      // 导航链接数组，包含多个导航项
        required: true,   // 必须传入该参数
    },
});

// 使用 usePage 获取当前页面信息，用于判断哪个链接是活动状态
const page = usePage();

// 定义函数：判断链接是否为活动状态
// 当传入的链接等于当前页面 URL 时，返回 true；否则返回 false
const isActive = (link) => page.url === link;
</script>
