// FileName: /var/www/Solarmax3Wiki/resources/js/Pages/Wiki/Revisions/CompareRevisions.vue
<template>
    <MainLayout
        :navigationLinks="[{ href: '/wiki', label: '游戏维基' }, { href: '#', label: '游戏历史&名人墙' }, { href: '#', label: '自制专区' }, { href: '#', label: '攻略专区' }, { href: '#', label: '论坛' }]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- 页面头部 -->
            <div class="bg-white/80 backdrop-blur-sm shadow-lg rounded-lg mb-6">
                <div class="px-6 py-4">
                    <div class="flex justify-between items-center">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">
                                {{ page.title }} - 版本对比
                            </h1>
                            <p class="mt-1 text-sm text-gray-500">
                                对比版本 {{ fromVersion }} 和版本 {{ toVersion }}
                            </p>
                        </div>
                        <div class="flex gap-4">
                            <Link :href="route('wiki.revisions', page.id)"
                                class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md text-sm text-gray-700 bg-white hover:bg-gray-50">
                            返回版本列表
                            </Link>
                            <Link :href="route('wiki.show', page.id)"
                                class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md text-sm text-gray-700 bg-white hover:bg-gray-50">
                            返回页面
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 版本对比内容 -->
            <div class="bg-white/80 backdrop-blur-sm shadow-lg rounded-lg">
                <RevisionCompare :old-revision="oldRevision" :new-revision="newRevision" :from-version="fromVersion"
                    :to-version="toVersion" />
            </div>
        </div>
    </MainLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayouts/MainLayout.vue';
import RevisionCompare from '@/Components/Wiki/Revision/RevisionCompare.vue';

defineProps({
    page: {
        type: Object,
        required: true
    },
    oldRevision: {
        type: Object,
        required: true
    },
    newRevision: {
        type: Object,
        required: true
    },
    fromVersion: {
        type: Number,
        required: true
    },
    toVersion: {
        type: Number,
        required: true
    }
});
</script>