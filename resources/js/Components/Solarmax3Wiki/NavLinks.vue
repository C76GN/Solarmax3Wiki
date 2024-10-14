<template>
    <div class="flex items-center space-x-4 whitespace-nowrap">
        <a v-for="link in navLinks" :key="link.name" :href="link.url" :class="linkClass">{{ link.name }}</a>
    </div>
</template>

<script setup>
const props = defineProps({
    navLinks: Array,
    linkClass: String
});
</script>
