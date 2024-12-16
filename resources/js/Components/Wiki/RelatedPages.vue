// FileName: /var/www/Solarmax3Wiki/resources/js/Components/Wiki/RelatedPages.vue
<template>
    <div class="border-t border-gray-200 pt-6 mt-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">相关页面</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div v-for="page in pages" :key="page.id"
                class="border border-gray-200 rounded-md p-4 hover:bg-gray-50 transition-colors duration-200">
                <Link :href="route('wiki.show', page.id)" class="block">
                <h4 class="text-base font-medium text-gray-900 mb-1">
                    {{ page.title }}
                </h4>
                <div class="text-sm text-gray-500 space-y-1">
                    <div class="flex items-center gap-2">
                        <DocumentIcon class="w-4 h-4" />
                        {{ page.categories_count }} 个共同分类
                    </div>
                    <div class="flex items-center gap-2">
                        <LinkIcon class="w-4 h-4" />
                        {{ page.incoming_references_count }} 次引用
                    </div>
                </div>
                </Link>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import { DocumentIcon, LinkIcon } from '@heroicons/vue/24/outline';

defineProps({
    pages: {
        type: Array,
        required: true
    }
});
</script>