<template>
    <div class="flex min-h-screen flex-col items-center pt-6 sm:justify-center sm:pt-0 p-6 text-white relative bg-center bg-cover bg-fixed"
        style="background-image: url('/images/BG2.webp');">
        <!-- StarrySky 背景 -->
        <StarrySky class="absolute inset-0 pointer-events-none" />

        <!-- Logo 区域 -->
        <div class="w-full max-w-xs sm:max-w-md h-auto z-10">
            <Link href="/">
            <Logo
                class="fill-current object-contain text-cyan-500 hover:scale-105 transition duration-300 ease-in-out" />
            </Link>
        </div>

        <!-- 表单区域 -->
        <div
            class="mt-6 overflow-hidden px-6 py-4 sm:max-w-md sm:rounded-lg bg-black bg-opacity-40 rounded-lg shadow-md w-full sm:w-96 transition-all duration-300 ease-in-out z-10">
            <slot />
        </div>
    </div>
</template>

<script setup>
import StarrySky from '@/Components/Solarmax3Wiki/StarrySky.vue';
import Logo from '@/Components/Solarmax3Wiki/Svg/Logo.vue';
import { Link } from '@inertiajs/vue3';
</script>
