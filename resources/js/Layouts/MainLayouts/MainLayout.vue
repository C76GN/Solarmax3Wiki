// FileName: /var/www/Solarmax3Wiki/resources/js/Layouts/MainLayouts/MainLayout.vue
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
import Navbar from '@/Components/Nav/NavBar.vue'; 
import FloatingMenu from '@/Components/Other/FloatingMenu.vue';
import StarrySky from '@/Components/Other/StarrySky.vue';

const props = defineProps({
    navigationLinks: {
        type: Array,
        default: () => [],
    },
});
</script>
