<template>
    <div class="min-h-screen w-full relative overflow-hidden bg-cover bg-center"
        style="background-image: url('/images/BG2.webp')">

        <StarrySky class="absolute inset-0 pointer-events-none" />

        <!-- 引入 Navbar 组件 -->
        <Navbar :navigationLinks="navigationLinks" />

        <main class="mt-8 transition-all duration-300">
            <slot />
        </main>

        <!-- 悬浮按钮 -->
        <FloatingMenu />
    </div>
</template>

<script setup>
import Navbar from '@/Components/Nav/NavBar.vue'; // 引入新创建的 Navbar 组件
import FloatingMenu from '@/Components/Other/FloatingMenu.vue';
import StarrySky from '@/Components/Other/StarrySky.vue';

const props = defineProps({
    navigationLinks: {
        type: Array,
        default: () => [],
    },
});
</script>
