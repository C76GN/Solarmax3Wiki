<template>
    <MainLayout
        :navigationLinks="[{ href: '/wiki', label: '游戏维基' }, { href: '#', label: '游戏历史&名人墙' }, { href: '#', label: '自制专区' }, { href: '#', label: '攻略专区' }, { href: '#', label: '论坛' }]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white/80 backdrop-blur-sm shadow-lg rounded-lg overflow-hidden">
                <div class="p-6">
                    <h2 class="text-2xl font-semibold text-gray-900 mb-6">版本比较</h2>

                    <div class="flex justify-between items-center mb-4">
                        <div>
                            <h3 class="text-lg font-medium">{{ page.title }}</h3>
                            <p class="text-sm text-gray-500">比较您正在编辑的版本与数据库中的最新版本</p>
                        </div>
                        <div class="flex gap-2">
                            <Link :href="route('wiki.edit', page.id)"
                                class="px-4 py-2 text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200">
                            返回编辑
                            </Link>
                            <button @click="forceSubmit"
                                class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
                                强制更新
                            </button>
                        </div>
                    </div>

                    <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- 数据库版本 -->
                        <div class="border rounded-lg overflow-hidden">
                            <div class="bg-gray-100 px-4 py-2 border-b">
                                <h4 class="font-medium">数据库中的版本</h4>
                                <p class="text-xs text-gray-500">最后更新: {{ formatDate(databaseVersion.updated_at) }}</p>
                            </div>
                            <div class="p-4 prose prose-sm max-w-none">
                                <div v-html="databaseVersion.content"></div>
                            </div>
                        </div>

                        <!-- 正在编辑的版本 -->
                        <div class="border rounded-lg overflow-hidden">
                            <div class="bg-gray-100 px-4 py-2 border-b">
                                <h4 class="font-medium">您正在编辑的版本</h4>
                                <p class="text-xs text-gray-500">未保存的更改</p>
                            </div>
                            <div class="p-4 prose prose-sm max-w-none">
                                <div v-html="editingVersion.content"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </MainLayout>
</template>

<script setup>
import { router, Link } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayouts/MainLayout.vue';

const props = defineProps({
    page: {
        type: Object,
        required: true
    },
    databaseVersion: {
        type: Object,
        required: true
    },
    editingVersion: {
        type: Object,
        required: true
    },
});

const formatDate = (date) => {
    if (!date) return '';
    return new Date(date).toLocaleString('zh-CN');
};

const forceSubmit = () => {
    router.put(`/wiki/${props.page.id}`, {
        title: props.page.title,
        content: props.editingVersion.content,
        categories: props.page.categories,
        force_update: true
    });
};
</script>