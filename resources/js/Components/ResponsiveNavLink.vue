<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    href: {
        type: String,
        required: true,
    },
    active: {
        type: Boolean,
    },
});

// 公共类
const commonClass = 'inline-flex items-center px-1 pt-1 transition duration-300 ease-in-out whitespace-nowrap focus:outline-none';
// Active时的类
const IsActiveClass = `${commonClass} bg-sky-600 text-xl font-extrabold text-black hover:bg-sky-500`;
// 非Active时的类
const NotActiveClass = `${commonClass} bg-sky-400 text-xl font-medium text-slate-700 hover:bg-sky-500 hover:font-extrabold`;

const classes = computed(() => {
    return props.active ?
        `${IsActiveClass} block w-full ps-3 pe-4 py-2` :
        `${NotActiveClass} block w-full ps-3 pe-4 py-2`;
});
</script>

<template>
    <Link :href="href" :class="classes">
    <slot />
    </Link>
</template>
