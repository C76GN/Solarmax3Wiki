<template>
    <!-- 悬浮在顶部的导航栏容器 -->
    <div class="h-16 fixed top-0 p-4 left-0 w-full bg-camp-blue shadow-lg z-50">
        <div class="flex justify-between items-center w-full">
            <!-- 插槽，用于插入自定义内容 -->
            <slot />
        </div>
    </div>
</template>

<script setup>
// 该组件的内容主要是通过插槽传入的，因此无需太多逻辑。
</script>

<style scoped>
/* 导航栏的样式，固定在页面顶部并保持阴影效果 */

</style>
