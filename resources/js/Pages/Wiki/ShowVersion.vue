<template>
    <MainLayout :navigationLinks="navigationLinks">

        <Head :title="`版本 ${version.version_number} - ${page.title}`" />
        <div class="container mx-auto py-8 px-4 md:px-6 lg:px-8">
            <!-- 使用统一的背景和模糊效果 -->
            <div class="bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm rounded-lg shadow-lg p-6 md:p-8">
                <!-- 页面头部信息 -->
                <div class="mb-8 pb-4 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex flex-col md:flex-row justify-between md:items-start mb-4 gap-4">
                        <div>
                            <h1
                                class="text-3xl md:text-4xl font-bold mb-2 leading-tight text-gray-900 dark:text-gray-100 break-words">
                                {{ page.title }}
                                <span class="text-xl font-normal text-gray-500 dark:text-gray-400">(版本 v{{
                                    version.version_number }})</span>
                            </h1>
                            <div
                                class="text-sm text-gray-500 dark:text-gray-400 flex flex-wrap items-center gap-x-4 gap-y-1">
                                <span v-if="version.creator" class="whitespace-nowrap flex items-center">
                                    <font-awesome-icon :icon="['fas', 'user-edit']" class="mr-1.5 w-3 h-3" />
                                    由 {{ version.creator.name }} 编辑于 {{ formatDateTime(version.created_at) }}
                                </span>
                                <span v-if="version.comment" class="whitespace-nowrap flex items-center"
                                    :title="version.comment">
                                    <font-awesome-icon :icon="['fas', 'comment-dots']" class="mr-1.5 w-3 h-3" />
                                    编辑说明: {{ truncate(version.comment, 50) }}
                                </span>
                                <span v-else class="whitespace-nowrap text-gray-400 italic flex items-center">
                                    <font-awesome-icon :icon="['fas', 'comment-slash']" class="mr-1.5 w-3 h-3" />
                                    无编辑说明
                                </span>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2 flex-shrink-0 mt-2 md:mt-0">
                            <!-- 使用统一按钮/链接样式 -->
                            <Link :href="route('wiki.history', page.slug)" class="btn-icon-secondary">
                            <font-awesome-icon :icon="['fas', 'history']" /> <span>返回历史</span>
                            </Link>
                            <Link :href="route('wiki.show', page.slug)" class="btn-icon-primary">
                            <font-awesome-icon :icon="['fas', 'eye']" /> <span>查看当前</span>
                            </Link>
                        </div>
                    </div>
                    <!-- 分类和标签 -->
                    <div class="flex flex-wrap gap-2 mt-3 items-center">
                        <span class="text-sm text-gray-500 dark:text-gray-400 mr-2">分类:</span>
                        <span v-for="category in page.categories" :key="category.id" class="tag-category">
                            {{ category.name }}
                        </span>
                        <span class="text-sm text-gray-500 dark:text-gray-400 mr-2 ml-4"
                            v-if="page.tags && page.tags.length">标签:</span>
                        <span v-for="tag in page.tags" :key="tag.id" class="tag-tag">
                            {{ tag.name }}
                        </span>
                    </div>
                </div>

                <!-- 版本内容 -->
                <!-- 应用暗色模式下的Prose样式 -->
                <div class="prose dark:prose-invert max-w-none prose-indigo lg:prose-lg xl:prose-xl">
                    <div v-if="version && version.content" v-html="version.content"></div>
                    <div v-else
                        class="text-gray-500 dark:text-gray-400 italic py-8 text-center border border-gray-200 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800/50 mt-6">
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
import { Head, Link } from '@inertiajs/vue3'; // 引入 Head
import MainLayout from '@/Layouts/MainLayouts/MainLayout.vue';
import FlashMessage from '@/Components/Other/FlashMessage.vue'; // 引入 FlashMessage
import { formatDateTime } from '@/utils/formatters';
import { mainNavigationLinks } from '@/config/navigationConfig';

const navigationLinks = mainNavigationLinks;
const flashMessage = ref(null); // 为 FlashMessage 创建 ref

const props = defineProps({
    page: { type: Object, required: true },
    version: { type: Object, required: true }
});

const truncate = (text, length) => {
    if (!text) return '';
    return text.length > length ? text.substring(0, length) + '...' : text;
};
</script>

<style scoped>
/* 确保 Tailwind Prose 暗色模式正确应用 */
.dark .dark\:prose-invert {
    --tw-prose-body: theme(colors.gray.300);
    --tw-prose-headings: theme(colors.gray.100);
    --tw-prose-lead: theme(colors.gray.400);
    --tw-prose-links: theme(colors.cyan.400);
    --tw-prose-bold: theme(colors.white);
    --tw-prose-counters: theme(colors.gray.400);
    --tw-prose-bullets: theme(colors.gray.600);
    --tw-prose-hr: theme(colors.gray.700);
    --tw-prose-quotes: theme(colors.gray.200);
    --tw-prose-quote-borders: theme(colors.blue.700);
    --tw-prose-captions: theme(colors.gray.400);
    --tw-prose-code: theme(colors.red.300);
    /* 调整代码颜色 */
    --tw-prose-pre-code: theme(colors.gray.300);
    --tw-prose-pre-bg: theme(colors.gray.900);
    /* 深色代码块背景 */
    --tw-prose-th-borders: theme(colors.gray.600);
    --tw-prose-td-borders: theme(colors.gray.700);
    /* 你可以根据需要覆盖更多 Prose 变量 */
}

/* 统一的标签样式 */
.tag-category {
    @apply inline-block px-2.5 py-0.5 bg-gray-200 text-gray-700 text-xs rounded-full transition dark:bg-gray-700 dark:text-gray-300;
}

.tag-tag {
    @apply inline-block px-2.5 py-0.5 bg-blue-100 text-blue-700 text-xs rounded-full transition dark:bg-blue-900/40 dark:text-blue-300;
}

/* 统一的按钮/链接样式 */
.btn-icon-primary {
    @apply inline-flex items-center px-3 py-1 bg-blue-600 text-white text-xs rounded hover:bg-blue-700 transition;
}

.btn-icon-secondary {
    @apply inline-flex items-center px-3 py-1 bg-gray-200 text-gray-700 text-xs rounded hover:bg-gray-300 transition dark:bg-gray-600 dark:text-gray-200 dark:hover:bg-gray-500;
}

.btn-icon-primary svg,
.btn-icon-secondary svg {
    @apply mr-1.5 h-3 w-3;
    /* 稍微调整图标间距 */
}
</style>