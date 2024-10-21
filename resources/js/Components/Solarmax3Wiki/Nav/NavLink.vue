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

const commonClass = 'inline-flex items-center px-1 pt-1 border-b-2 transition duration-300 ease-in-out whitespace-nowrap focus:border-sky-600';
const IsActiveClass = `${commonClass} bg-clip-text text-transparent bg-gradient-to-r from-blue-950 to-sky-900 border-blue-950 text-lg font-bold hover:text-black hover:border-gray-300`;
const NotActiveClass = `${commonClass} border-transparent text-lg font-medium text-slate-800 hover:text-gray-700 hover:border-gray-300 hover:font-bold focus:text-gray-700 focus:border-gray-300`;

const classes = computed(() => props.active ? IsActiveClass : NotActiveClass);
</script>
