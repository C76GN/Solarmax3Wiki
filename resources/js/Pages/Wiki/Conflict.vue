<template>
    <!-- 主要布局容器，引入导航链接 -->
    <MainLayout :navigationLinks="navigationLinks">
        <!-- 设置页面标题，显示页面名称 -->

        <Head :title="'页面冲突 - ' + page.title" />
        <div class="container mx-auto py-8 px-4 md:px-6 lg:px-8">
            <!-- 页面内容主体卡片，背景半透明，圆角，阴影 -->
            <div
                class="bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm rounded-lg shadow-lg p-6 md:p-8 max-w-4xl mx-auto">
                <!-- 页面标题，居中显示 -->
                <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-4 text-center">
                    页面《{{ page.title }}》访问受限
                </h1>
                <!-- 错误/警告信息块，红色背景，左侧边框，显示传入的消息 -->
                <div
                    class="bg-red-100 border-l-4 border-red-500 text-red-800 p-4 rounded-md mb-6 dark:bg-red-900/40 dark:border-red-600 dark:text-red-300">
                    <div class="flex items-center">
                        <!-- 警告图标 -->
                        <font-awesome-icon :icon="['fas', 'exclamation-triangle']" class="text-xl mr-3 flex-shrink-0" />
                        <p class="text-sm">{{ message }}</p>
                    </div>
                </div>
                <!-- 操作按钮组，适应不同屏幕尺寸的布局 -->
                <div class="flex flex-col sm:flex-row justify-center items-center gap-4">
                    <!-- 解决冲突按钮，仅在用户有权限时显示，黄色样式 -->
                    <Link v-if="canResolveConflict" :href="route('wiki.edit', page.slug)"
                        class="btn-primary bg-yellow-600 hover:bg-yellow-700">
                    <font-awesome-icon :icon="['fas', 'exclamation-triangle']" class="mr-2" />
                    解决冲突
                    </Link>
                    <!-- 查看历史版本按钮，次要样式 -->
                    <Link :href="route('wiki.history', page.slug)" class="btn-secondary">
                    <font-awesome-icon :icon="['fas', 'history']" class="mr-2" />
                    查看历史版本
                    </Link>
                    <!-- 返回 Wiki 列表按钮，次要样式 -->
                    <Link :href="route('wiki.index')" class="btn-secondary">
                    <font-awesome-icon :icon="['fas', 'arrow-left']" class="mr-2" />
                    返回 Wiki 列表
                    </Link>
                </div>
            </div>
        </div>
        <!-- 闪存消息组件，用于显示操作成功或失败的提示 -->
        <FlashMessage ref="flashMessage" />
    </MainLayout>
</template>

<script setup>
import { computed, ref } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayouts/MainLayout.vue';
import FlashMessage from '@/Components/Other/FlashMessage.vue';
import { mainNavigationLinks } from '@/config/navigationConfig';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

// 从配置文件导入主导航链接
const navigationLinks = mainNavigationLinks;
// 获取当前页面属性，如认证用户和全局闪存消息
const pageProps = usePage().props;
// 引用 FlashMessage 组件实例
const flashMessage = ref(null);

// 定义组件接收的属性
const props = defineProps({
    page: {
        type: Object, // 页面数据对象
        required: true
    },
    message: {
        type: String, // 显示给用户的提示消息
        default: '此页面当前状态异常或存在编辑冲突，需要管理员处理。'
    },
    canResolveConflict: { // 指示当前用户是否具有解决冲突的权限
        type: Boolean,
        default: false,
    },
});

</script>

<style scoped>
/* 主要按钮样式 */
.btn-primary {
    /* 统一按钮样式，包括内边距、边框、字体、圆角、背景色、悬停效果和禁用状态 */
    @apply inline-flex items-center justify-center px-5 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 transition;
}

/* 次要按钮样式 */
.btn-secondary {
    /* 统一按钮样式，包括内边距、边框、字体、圆角、背景色、悬停效果和禁用状态 */
    @apply inline-flex items-center justify-center px-5 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 transition;
}

/* 黄色主要按钮的特定背景色 */
.btn-primary.bg-yellow-600 {
    background-color: #D97706;
    /* amber-600 */
}

/* 黄色主要按钮的悬停背景色 */
.btn-primary.bg-yellow-600:hover {
    background-color: #B45309;
    /* amber-700 */
}
</style>