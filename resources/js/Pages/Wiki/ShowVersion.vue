<template>
    <!-- 使用 MainLayout 组件作为页面的主要布局，并传入导航链接 -->
    <MainLayout :navigationLinks="navigationLinks">

        <!-- 设置页面标题，显示当前版本号和页面标题 -->

        <Head :title="`版本 ${version.version_number} - ${page.title}`" />
        <div class="container mx-auto py-8 px-4 md:px-6 lg:px-8">
            <!-- 页面内容主体容器，应用统一的背景、模糊效果和阴影 -->
            <div class="bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm rounded-lg shadow-lg p-6 md:p-8">
                <!-- 页面头部信息区域，包含标题、版本信息和操作按钮 -->
                <div class="mb-8 pb-4 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex flex-col md:flex-row justify-between md:items-start mb-4 gap-4">
                        <div>
                            <!-- 页面标题和版本号 -->
                            <h1
                                class="text-3xl md:text-4xl font-bold mb-2 leading-tight text-gray-900 dark:text-gray-100 break-words">
                                {{ page.title }}
                                <span class="text-xl font-normal text-gray-500 dark:text-gray-400">(版本 v{{
                                    version.version_number }})</span>
                            </h1>
                            <!-- 版本编辑者信息和编辑说明 -->
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
                        <!-- 操作按钮组 -->
                        <div class="flex items-center space-x-2 flex-shrink-0 mt-2 md:mt-0">
                            <!-- 返回历史版本列表的链接 -->
                            <Link :href="route('wiki.history', page.slug)" class="btn-icon-secondary">
                            <font-awesome-icon :icon="['fas', 'history']" /> <span>返回历史</span>
                            </Link>
                            <!-- 查看当前页面内容的链接 -->
                            <Link :href="route('wiki.show', page.slug)" class="btn-icon-primary">
                            <font-awesome-icon :icon="['fas', 'eye']" /> <span>查看当前</span>
                            </Link>
                        </div>
                    </div>
                    <!-- 页面分类和标签显示区域 -->
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

                <!-- 版本内容显示区域，应用 Tailwind Prose 样式以美化 Markdown/HTML 内容 -->
                <div class="prose dark:prose-invert max-w-none prose-indigo lg:prose-lg xl:prose-xl">
                    <!-- 如果版本内容存在，则使用 v-html 渲染 -->
                    <div v-if="version && version.content" v-html="version.content"></div>
                    <!-- 如果版本内容为空，则显示占位符信息 -->
                    <div v-else
                        class="text-gray-500 dark:text-gray-400 italic py-8 text-center border border-gray-200 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800/50 mt-6">
                        <p>该版本没有内容。</p>
                    </div>
                </div>
            </div>
        </div>
        <!-- 页面顶部的闪烁消息组件 -->
        <FlashMessage ref="flashMessage" />
    </MainLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3'; // 引入 Head 和 Link 组件
import MainLayout from '@/Layouts/MainLayouts/MainLayout.vue'; // 引入主布局组件
import FlashMessage from '@/Components/Other/FlashMessage.vue'; // 引入闪烁消息组件
import { formatDateTime } from '@/utils/formatters'; // 引入日期时间格式化工具函数
import { mainNavigationLinks } from '@/config/navigationConfig'; // 引入主导航链接配置

// 导航链接配置
const navigationLinks = mainNavigationLinks;
// 为 FlashMessage 组件创建一个引用
const flashMessage = ref(null);

// 定义组件接收的属性
const props = defineProps({
    page: { // 当前Wiki页面对象
        type: Object,
        required: true
    },
    version: { // 当前显示的Wiki版本对象
        type: Object,
        required: true
    }
});

/**
 * 截断文本函数。
 * @param {string} text - 原始文本。
 * @param {number} length - 截断后的最大长度。
 * @returns {string} - 截断后的文本，如果超过长度则添加 '...'。
 */
const truncate = (text, length) => {
    if (!text) return '';
    return text.length > length ? text.substring(0, length) + '...' : text;
};
</script>

<style scoped>
/* Tailwind Prose 暗色模式下文本和背景颜色的变量覆盖，确保内容在暗色背景下可读 */
.dark .dark\:prose-invert {
    --tw-prose-body: theme(colors.gray.300);
    /* 正文颜色 */
    --tw-prose-headings: theme(colors.gray.100);
    /* 标题颜色 */
    --tw-prose-lead: theme(colors.gray.400);
    /* 引导文本颜色 */
    --tw-prose-links: theme(colors.cyan.400);
    /* 链接颜色 */
    --tw-prose-bold: theme(colors.white);
    /* 加粗文本颜色 */
    --tw-prose-counters: theme(colors.gray.400);
    /* 列表计数器颜色 */
    --tw-prose-bullets: theme(colors.gray.600);
    /* 列表项目符号颜色 */
    --tw-prose-hr: theme(colors.gray.700);
    /* 分隔线颜色 */
    --tw-prose-quotes: theme(colors.gray.200);
    /* 引用文本颜色 */
    --tw-prose-quote-borders: theme(colors.blue.700);
    /* 引用边框颜色 */
    --tw-prose-captions: theme(colors.gray.400);
    /* 图片标题颜色 */
    --tw-prose-code: theme(colors.red.300);
    /* 内联代码颜色 */
    --tw-prose-pre-code: theme(colors.gray.300);
    /* 代码块内文本颜色 */
    --tw-prose-pre-bg: theme(colors.gray.900);
    /* 代码块背景颜色 */
    --tw-prose-th-borders: theme(colors.gray.600);
    /* 表格表头边框颜色 */
    --tw-prose-td-borders: theme(colors.gray.700);
    /* 表格单元格边框颜色 */
}

/* 页面分类标签样式 */
.tag-category {
    @apply inline-block px-2.5 py-0.5 bg-gray-200 text-gray-700 text-xs rounded-full transition dark:bg-gray-700 dark:text-gray-300;
}

/* 页面标签样式 */
.tag-tag {
    @apply inline-block px-2.5 py-0.5 bg-blue-100 text-blue-700 text-xs rounded-full transition dark:bg-blue-900/40 dark:text-blue-300;
}

/* 主要操作按钮样式 */
.btn-icon-primary {
    @apply inline-flex items-center px-3 py-1 bg-blue-600 text-white text-xs rounded hover:bg-blue-700 transition;
}

/* 次要操作按钮样式 */
.btn-icon-secondary {
    @apply inline-flex items-center px-3 py-1 bg-gray-200 text-gray-700 text-xs rounded hover:bg-gray-300 transition dark:bg-gray-600 dark:text-gray-200 dark:hover:bg-gray-500;
}

/* 按钮中 SVG 图标的样式 */
.btn-icon-primary svg,
.btn-icon-secondary svg {
    @apply mr-1.5 h-3 w-3;
}
</style>