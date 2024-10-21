<template>
    <div class="flex space-x-4 ">
        <NavLink v-for="link in links" :key="link.href" :href="link.href" :active="link.active"
            @click.prevent="handleNavClick(link.href)">
            {{ link.text }}
        </NavLink>
    </div>
</template>

<script setup>
import NavLink from './NavLink.vue';

const props = defineProps({
    links: Array,
});

const emit = defineEmits(['nav-click']);

const handleNavClick = (href) => {
    emit('nav-click', href);
};
</script>
