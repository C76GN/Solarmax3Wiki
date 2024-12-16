// FileName: /var/www/Solarmax3Wiki/resources/js/Components/Other/Pagination.vue
<template>
    <div v-if="links.length > 3" class="flex justify-center">
        <div v-for="(link, index) in links" :key="index" class="mx-1">
            <Link v-if="link.url" :href="link.url" :class="[
                'px-4 py-2 rounded-md text-sm transition duration-150 ease-in-out',
                link.active
                    ? 'bg-blue-500 text-white'
                    : 'bg-white/80 backdrop-blur-sm text-gray-700 hover:bg-gray-50'
            ]" v-html="link.label" />
            <span v-else class="px-4 py-2 text-gray-400 text-sm" v-html="link.label" />
        </div>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'

defineProps({
    links: {
        type: Array,
        required: true
    }
})
</script>