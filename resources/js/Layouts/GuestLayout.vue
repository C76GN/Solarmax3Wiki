<!-- 登陆页面布局 -->
<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="flex min-h-screen flex-col items-center pt-6 sm:justify-center sm:pt-0 p-6 text-white relative bg-cover bg-center"
        style="background-image: url('/images/BG2.webp'); background-attachment: fixed">
        <div>
            <Link href="/">
            <ApplicationLogo class="h-20 w-20 fill-current text-gray-500" />
            </Link>
        </div>

        <div
            class="mt-6 overflow-hidden px-6 py-4 sm:max-w-md sm:rounded-lg bg-black bg-opacity-40 rounded-lg hover: shadow-md w-full sm:w-96 transition-all duration-300 ease-in-out">
            <slot />
        </div>
    </div>
</template>
