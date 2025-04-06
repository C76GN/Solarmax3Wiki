<template>
    <div ref="navLinksContainer" class="hidden md:flex space-x-2 sm:-my-px sm:ms-10">
        <NavLink v-for="(link, index) in navigationLinks" :key="index" :href="link.href" :active="isActive(link.href)">
            {{ link.label }}
        </NavLink>
    </div>
</template>

<script setup>
import NavLink from '@/Components/Nav/NavLink.vue';
import { usePage, router } from '@inertiajs/vue3';

const props = defineProps({
    navigationLinks: {
        type: Array,
        required: true,
    },
});

const page = usePage();
const isActive = (link) => page.url === link;
const isActiveRoute = (routeName) => {
    return route().current(routeName) ||
        (routeName.startsWith('wiki.') && route().current().startsWith('wiki.'));
};
</script>