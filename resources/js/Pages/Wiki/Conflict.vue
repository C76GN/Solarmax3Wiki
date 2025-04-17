<template>
     <MainLayout :navigationLinks="navigationLinks">
         <Head :title="'页面冲突 - ' + page.title" />
         <div class="container mx-auto py-8 px-4 md:px-6 lg:px-8">
            <!-- 修改: 添加标准的内容包裹 -->
             <div class="bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm rounded-lg shadow-lg p-6 md:p-8 max-w-4xl mx-auto">
                 <!-- 页面标题 -->
                 <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-4 text-center">
                     页面《{{ page.title }}》访问受限
                </h1>
                 <!-- 错误/警告信息 -->
                 <div class="bg-red-100 border-l-4 border-red-500 text-red-800 p-4 rounded-md mb-6 dark:bg-red-900/40 dark:border-red-600 dark:text-red-300">
                    <div class="flex items-center">
                        <font-awesome-icon :icon="['fas', 'exclamation-triangle']" class="text-xl mr-3 flex-shrink-0" />
                         <p class="text-sm">{{ message }}</p>
                    </div>
                </div>
                 <!-- 操作按钮 -->
                 <div class="flex flex-col sm:flex-row justify-center items-center gap-4">
                     <!-- 修改: 根据权限显示不同的按钮 -->
                     <Link v-if="canResolveConflict" :href="route('wiki.edit', page.slug)" class="btn-primary bg-yellow-600 hover:bg-yellow-700">
                        <font-awesome-icon :icon="['fas', 'exclamation-triangle']" class="mr-2" />
                        解决冲突
                     </Link>
                     <!-- 查看历史总是允许（如果策略允许） -->
                      <Link :href="route('wiki.history', page.slug)" class="btn-secondary">
                        <font-awesome-icon :icon="['fas', 'history']" class="mr-2" />
                        查看历史版本
                     </Link>
                     <!-- 返回 Wiki 首页或列表 -->
                      <Link :href="route('wiki.index')" class="btn-secondary">
                        <font-awesome-icon :icon="['fas', 'arrow-left']" class="mr-2" />
                        返回 Wiki 列表
                     </Link>
                </div>
                 <!-- 可能显示最后已知内容（可选） -->
                  <!--
                  <div class="mt-8 pt-6 border-t dark:border-gray-700" v-if="page.currentVersion">
                    <h2 class="text-lg font-medium mb-3 text-gray-700 dark:text-gray-300">最后已知稳定版本内容 (v{{page.currentVersion.version_number}}):</h2>
                    <div class="p-4 border rounded-lg bg-gray-50 dark:bg-gray-800/70 dark:border-gray-600 prose max-w-none dark:prose-invert text-sm overflow-y-auto max-h-96">
                      <div v-html="page.currentVersion.content"></div>
                    </div>
                  </div>
                  -->
            </div>
        </div>
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

const navigationLinks = mainNavigationLinks;
const pageProps = usePage().props;
const flashMessage = ref(null);

const props = defineProps({
    page: {
        type: Object,
        required: true
    },
    message: {
        type: String,
        default: '此页面当前状态异常或存在编辑冲突，需要管理员处理。'
    },
     canResolveConflict: { // 这个 prop 需要从 Controller 传递
        type: Boolean,
        default: false,
    },
});

</script>

<style scoped>
.btn-primary {
     @apply inline-flex items-center justify-center px-5 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 transition;
}
.btn-secondary {
    @apply inline-flex items-center justify-center px-5 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 transition;
}
/* 添加黄色按钮样式 (如果需要) */
 .btn-primary.bg-yellow-600 {
    background-color: #D97706; /* amber-600 */
}
.btn-primary.bg-yellow-600:hover {
     background-color: #B45309; /* amber-700 */
}
</style>