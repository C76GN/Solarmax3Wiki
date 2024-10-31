<template>
    <!-- 使用 NavText 组件，传入导航链接和用户信息 -->
    <NavText :navigation-links="[
        { href: '/text', label: 'text1' },
        { href: '/text2', label: 'text2' }
    ]" :show-dropdown="true">
        <!-- 页面内容示例 -->
        <div class="p-10 text-white">
            <h1 class="text-4xl font-bold">Welcome to Text Page</h1>
            <p class="mt-4">This is an example page using the NavText component.</p>
        </div>
    </NavText>
</template>

<script setup>
import NavText from '@/Layouts/MainLayouts/MainLayout.vue';
</script>

<style scoped>
/* 可根据需要调整样式 */
</style>
