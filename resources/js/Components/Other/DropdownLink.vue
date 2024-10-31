<!-- 下拉链接 -->
<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    href: {
        type: String,
        required: true,
    },
});
</script>

<template>
    <Link :href="href"
        class="block w-full px-4 py-2 text-start bg-sky-400 text-sm leading-5 text-black transition duration-150 ease-in-out hover:bg-cyan-300 focus:bg-cyan-500 focus:outline-none">
    <slot />
    </Link>
</template>
