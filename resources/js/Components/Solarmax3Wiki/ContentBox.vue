<template>
    <div class="bg-gray-800 bg-opacity-75 p-6 rounded-lg shadow-lg">
        <h2 class="text-2xl font-bold text-camp-orange mb-4">{{ title }}</h2>
        <p class="text-gray-300">{{ content }}</p>
        <slot></slot>
    </div>
</template>

<script setup>
defineProps({
    title: String,
    content: String
});
</script>

<style scoped>
/* 组件内部样式 */
</style>
