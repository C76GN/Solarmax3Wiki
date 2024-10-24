<template>

    <Head :title="title" />
    <div>
        <!-- 导航栏 -->
        <NavBar />

        <!-- 背景图片和半透明容器 -->
        <div class="bg-sky-200 min-h-screen flex flex-col items-center p-6 text-white relative bg-cover bg-center"
            style="background-image: url('/images/BG2.webp'); background-attachment: fixed;">
            <!-- 半透明容器，包含所有内容，并居中显示 -->
            <div class="w-full max-w-screen-xl bg-headcolor bg-opacity-40 p-8 rounded-lg shadow-lg mt-20 mb-16">
                <!-- 用来插入页面内容 -->
                <slot></slot>
            </div>
        </div>
    </div>
</template>

<script setup>
import NavBar from '@/Components/Solarmax3Wiki/NavBar.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    title: {
        type: String,
        required: true,
    },
});
</script>

<style scoped>
/* 在这里你可以添加额外的自定义样式 */
</style>
