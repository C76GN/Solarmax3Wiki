<template>
    <MainLayout :navigationLinks="navigationLinks">

        <Head :title="`版本 ${version.version_number} - ${page.title}`" />

        <div class="container mx-auto py-8 px-4 md:px-6 lg:px-8">
            <div class="bg-white/80 backdrop-blur-sm rounded-lg shadow-lg p-6 md:p-8">

                <!-- 页面和版本信息 -->
                <div class="mb-8 pb-4 border-b border-gray-200">
                    <div class="flex flex-col md:flex-row justify-between md:items-start mb-4 gap-4">
                        <div>
                            <h1 class="text-3xl md:text-4xl font-bold mb-2 leading-tight">
                                {{ page.title }}
                                <span class="text-xl font-normal text-gray-500">(版本 v{{ version.version_number
                                    }})</span>
                            </h1>
                            <div class="text-sm text-gray-500 flex flex-wrap items-center gap-x-4 gap-y-1">
                                <span v-if="version.creator" class="whitespace-nowrap">
                                    <font-awesome-icon :icon="['fas', 'user-edit']" class="mr-1" />
                                    由 {{ version.creator.name }} 编辑于 {{ formatDateTime(version.created_at) }}
                                </span>
                                <span v-if="version.comment" class="whitespace-nowrap" :title="version.comment">
                                    <font-awesome-icon :icon="['fas', 'comment-dots']" class="mr-1" />
                                    编辑说明: {{ truncate(version.comment, 50) }}
                                </span>
                                <span v-else class="whitespace-nowrap text-gray-400 italic">
                                    <font-awesome-icon :icon="['fas', 'comment-slash']" class="mr-1" />
                                    无编辑说明
                                </span>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2 flex-shrink-0">
                            <Link :href="route('wiki.history', page.slug)" class="btn-icon-secondary">
                            <font-awesome-icon :icon="['fas', 'history']" /> <span>返回历史</span>
                            </Link>
                            <Link :href="route('wiki.show', page.slug)" class="btn-icon-primary">
                            <font-awesome-icon :icon="['fas', 'eye']" /> <span>查看当前</span>
                            </Link>
                        </div>
                    </div>
                    <!-- 分类和标签 (如果需要显示) -->
                    <div class="flex flex-wrap gap-2 mt-3">
                        <span class="text-sm text-gray-500 mr-2">分类:</span>
                        <span v-for="category in page.categories" :key="category.id" class="tag-category">
                            {{ category.name }}
                        </span>
                        <span class="text-sm text-gray-500 mr-2 ml-4" v-if="page.tags && page.tags.length">标签:</span>
                        <span v-for="tag in page.tags" :key="tag.id" class="tag-tag">
                            {{ tag.name }}
                        </span>
                    </div>
                </div>

                <!-- 版本内容 -->
                <div class="prose max-w-none prose-indigo lg:prose-lg xl:prose-xl">
                    <div v-if="version && version.content" v-html="version.content"></div>
                    <div v-else class="text-gray-500 italic py-8 text-center border rounded-lg bg-gray-50 mt-6">
                        <p>该版本没有内容。</p>
                    </div>
                </div>

            </div>
        </div>
        <FlashMessage ref="flashMessage" />
    </MainLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link, Head } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayouts/MainLayout.vue';
import FlashMessage from '@/Components/Other/FlashMessage.vue'; // 如果需要闪存消息
import { formatDateTime } from '@/utils/formatters'; // 引入格式化函数
import { mainNavigationLinks } from '@/config/navigationConfig'; // 引入导航链接

const navigationLinks = mainNavigationLinks;
const flashMessage = ref(null); // 如果使用了 FlashMessage

const props = defineProps({
    page: {
        type: Object,
        required: true,
        // 确保传递了需要的信息
        // page.title
        // page.slug
        // page.categories (可选, 如果要在历史版本页显示)
        // page.tags (可选, 如果要在历史版本页显示)
    },
    version: {
        type: Object,
        required: true,
        // 确保传递了需要的信息
        // version.version_number
        // version.content
        // version.comment
        // version.created_at
        // version.creator.name
    }
});

// 辅助函数：截断长文本
const truncate = (text, length) => {
    if (!text) return '';
    return text.length > length ? text.substring(0, length) + '...' : text;
};

</script>

<style>
/* 可以复用 Show.vue 中的大部分样式 */
.prose {
    @apply text-gray-700;
}

.prose h1,
.prose h2,
.prose h3,
.prose h4,
.prose h5,
.prose h6 {
    @apply text-gray-900 font-semibold mb-4 mt-6 first:mt-0;
}

.prose p {
    @apply leading-relaxed mb-4;
}

.prose ul,
.prose ol {
    @apply pl-6 mb-4;
}

.prose li>p {
    @apply mb-1;
}

.prose blockquote {
    @apply border-l-4 border-gray-300 pl-4 italic text-gray-600 my-6;
}

.prose pre {
    @apply bg-gray-100 p-4 rounded-md overflow-x-auto my-6 text-sm;
}

.prose code:not(pre code) {
    @apply bg-gray-100 text-red-600 px-1 py-0.5 rounded text-sm;
}

.prose a {
    @apply text-blue-600 hover:underline;
}

.prose img {
    @apply max-w-full h-auto my-6 rounded;
}

.prose table {
    @apply w-full my-6 border-collapse border border-gray-300;
}

.prose th,
.prose td {
    @apply border border-gray-300 px-4 py-2;
}

.prose th {
    @apply bg-gray-100 font-semibold;
}

.tag-category {
    @apply inline-block px-3 py-1 bg-gray-200 text-gray-700 text-xs rounded-full transition;
}

.tag-tag {
    @apply inline-block px-3 py-1 bg-blue-100 text-blue-700 text-xs rounded-full transition;
}

.btn-icon-primary {
    @apply inline-flex items-center px-3 py-1 bg-blue-600 text-white text-xs rounded hover:bg-blue-700 transition;
}

.btn-icon-secondary {
    @apply inline-flex items-center px-3 py-1 bg-gray-200 text-gray-700 text-xs rounded hover:bg-gray-300 transition;
}

.btn-icon-warning {
    @apply inline-flex items-center px-3 py-1 bg-yellow-600 text-white text-xs rounded hover:bg-yellow-700 transition;
}

.btn-icon-primary svg,
.btn-icon-secondary svg,
.btn-icon-warning svg {
    @apply mr-1 h-3 w-3;
}
</style>