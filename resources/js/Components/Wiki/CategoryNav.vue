// FileName: /var/www/Solarmax3Wiki/resources/js/Components/Wiki/CategoryNav.vue
<template>
    <div class="bg-white/80 backdrop-blur-sm shadow-lg rounded-lg p-4 mb-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">分类导航</h3>
        <div class="flex flex-wrap gap-2">
            <Link :href="route('wiki.index')" :class="[
                'px-3 py-1 rounded-full text-sm font-medium transition-colors duration-150',
                !currentCategory ? 'bg-blue-500 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
            ]">
            全部
            </Link>

            <Link v-for="category in categories" :key="category.id"
                :href="route('wiki.index', { category: category.id })" :class="[
                    'px-3 py-1 rounded-full text-sm font-medium transition-colors duration-150',
                    currentCategory === category.id.toString()
                        ? 'bg-blue-500 text-white'
                        : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                ]">
            {{ category.name }}
            <span class="text-xs ml-1">({{ category.pages_count  }})</span>
            </Link>
        </div>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    categories: {
        type: Array,
        required: true
    },
    currentCategory: {
        type: String,
        default: null
    }
});
</script>
