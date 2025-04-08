<template>
    <MainLayout :navigationLinks="navigationLinks">
        <div class="container mx-auto py-6 px-4">
            <div class="bg-white/80 backdrop-blur-sm rounded-lg shadow-lg p-6">
                <h1 class="text-2xl font-bold mb-4">页面冲突</h1>

                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6">
                    <div class="flex items-center">
                        <font-awesome-icon :icon="['fas', 'exclamation-circle']" class="mr-2" />
                        <p>{{ message }}</p>
                    </div>
                </div>

                <div v-if="canResolveConflict" class="mb-6">
                    <Link :href="route('wiki.show-conflicts', page.slug)"
                        class="px-4 py-2 bg-yellow-600 text-white rounded-md hover:bg-yellow-700 inline-flex items-center">
                    <font-awesome-icon :icon="['fas', 'exclamation-triangle']" class="mr-2" />
                    解决冲突
                    </Link>
                </div>

                <div class="mb-6">
                    <Link :href="route('wiki.history', page.slug)"
                        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 inline-flex items-center">
                    <font-awesome-icon :icon="['fas', 'history']" class="mr-2" />
                    查看历史版本
                    </Link>
                </div>

                <!-- 当前版本内容 -->
                <div>
                    <h2 class="text-xl font-medium mb-4">当前版本内容:</h2>
                    <div class="p-4 border rounded-lg bg-gray-50">
                        <div v-if="page.currentVersion" class="prose max-w-none">
                            <div v-html="page.currentVersion.content"></div>
                        </div>
                        <div v-else class="text-gray-500 italic">
                            该页面没有内容
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </MainLayout>
</template>

<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayouts/MainLayout.vue';
import { mainNavigationLinks } from '@/config/navigationConfig';

const navigationLinks = mainNavigationLinks;

const props = defineProps({
    page: {
        type: Object,
        required: true
    },
    message: {
        type: String,
        default: '此页面当前存在编辑冲突，需要管理员解决。'
    }
});

const canResolveConflict = computed(() => {
    return props.$page?.props?.auth?.user?.permissions?.includes('wiki.resolve_conflict') || false;
});
</script>