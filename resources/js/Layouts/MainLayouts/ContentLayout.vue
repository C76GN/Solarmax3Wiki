<template>
    <div class="flex flex-col md:flex-row space-y-6 md:space-y-0 md:space-x-6">
        <!-- 左侧窄栏内容 -->
        <aside class="flex-1 space-y-6">
            <slot name="left" />
        </aside>

        <!-- 右侧宽栏内容 -->
        <section class="flex-2 space-y-6">
            <slot name="right" />
        </section>
    </div>
</template>

<script setup>
// 本组件仅用于左右栏布局
</script>

<style scoped>
.flex-1 {
    min-width: 600px; /* 根据需求设置最小宽度 */
}

.flex-2 {
    flex-grow: 1;
    max-width: 900px;
}
</style>
