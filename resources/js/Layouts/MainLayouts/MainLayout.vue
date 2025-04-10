<template>
    <div class="min-h-screen w-full relative overflow-hidden bg-cover bg-center"
        style="background-image: url('/images/BG2.webp')">
        <StarrySky class="absolute inset-0 pointer-events-none" />
        <Navbar :navigationLinks="navigationLinks" />
        <main class="mt-8 transition-all duration-300 pb-16"> <!-- Added pb-16 for footer space -->
            <slot />
        </main>
        <FloatingMenu />
        <!-- Add FlashMessage here -->
        <FlashMessage ref="flashMessage" />
    </div>
</template>

<script setup>
import Navbar from '@/Components/Nav/NavBar.vue';
import FloatingMenu from '@/Components/Other/FloatingMenu.vue';
import StarrySky from '@/Components/Other/StarrySky.vue';
// Import FlashMessage component
import FlashMessage from '@/Components/Other/FlashMessage.vue'; // Adjust path if needed
import { ref } from 'vue'; // Import ref

const props = defineProps({
    navigationLinks: {
        type: Array,
        default: () => [],
    },
});

const flashMessage = ref(null); // Define ref for FlashMessage
</script>

<style scoped>
/* Add any specific styles for MainLayout */
</style>