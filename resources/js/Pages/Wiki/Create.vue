// FileName: /var/www/Solarmax3Wiki/resources/js/Pages/Wiki/Create.vue
<template>
    <MainLayout
        :navigationLinks="[{ href: '/wiki', label: '游戏维基' }, { href: '#', label: '游戏历史&名人墙' }, { href: '#', label: '自制专区' }, { href: '#', label: '攻略专区' }, { href: '#', label: '论坛' }]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white/80 backdrop-blur-sm shadow-lg rounded-lg overflow-hidden">
                <div class="p-6">
                    <h2 class="text-2xl font-semibold text-gray-900 mb-6">创建新文章</h2>
                    <WikiArticleForm :categories="categories" />
                </div>
            </div>
        </div>
    </MainLayout>
</template>

<script setup>
import MainLayout from '@/Layouts/MainLayouts/MainLayout.vue';
import WikiArticleForm from '@/Components/Wiki/WikiPageForm.vue';

defineProps({
    categories: {
        type: Array,
        required: true
    }
});
</script>