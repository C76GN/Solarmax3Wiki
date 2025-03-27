<template>
    <Link :href="href" :class="classes">
    <slot />
    </Link>
</template>

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
        default: false,
    },
});
const baseClass = 'inline-flex items-center px-1 pt-1 border-b-2 text-xl transition duration-400 ease-in-out whitespace-nowrap';
const hoverClass = 'hover:text-gray-700 hover:border-gray-300';
const activeClass = 'bg-clip-text text-transparent bg-gradient-to-r from-blue-950 to-sky-900 border-blue-950 font-extrabold';
const inactiveClass = 'border-transparent font-medium text-slate-800';

const classes = computed(() =>
    `${baseClass} ${props.active ? activeClass : inactiveClass} ${hoverClass}`
);
</script>
