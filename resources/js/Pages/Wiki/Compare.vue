<template>
    <MainLayout :navigationLinks="navigationLinks">

        <Head :title="`比较版本: v${fromVersion.version_number} vs v${toVersion.version_number} - ${page.title}`" />
        <div class="container mx-auto py-6 px-4">
            <div class="bg-white/80 backdrop-blur-sm rounded-lg shadow-lg p-6">
                <!-- 标题和导航 -->
                <div class="flex justify-between items-center mb-6 border-b pb-4">
                    <h1 class="text-2xl font-bold">{{ page.title }} - 版本比较</h1>
                    <div class="flex items-center space-x-3">
                        <Link :href="route('wiki.history', page.slug)" class="text-blue-600 hover:text-blue-800">
                        返回历史版本
                        </Link>
                        <Link :href="route('wiki.show', page.slug)" class="text-blue-600 hover:text-blue-800">
                        返回页面
                        </Link>
                    </div>
                </div>

                <!-- 版本信息 -->
                <div class="mb-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="p-4 bg-red-50 rounded-lg border border-red-200">
                        <h3 class="font-medium mb-2 text-red-700">旧版本 (v{{ fromVersion.version_number }})</h3>
                        <div class="text-sm text-gray-600">
                            <p>编辑者: {{ fromCreator.name }}</p>
                            <p>时间: {{ formatDateTime(fromVersion.created_at) }}</p>
                            <p>说明: {{ fromVersion.comment || '无说明' }}</p>
                        </div>
                    </div>
                    <div class="p-4 bg-green-50 rounded-lg border border-green-200">
                        <h3 class="font-medium mb-2 text-green-700">新版本 (v{{ toVersion.version_number }})</h3>
                        <div class="text-sm text-gray-600">
                            <p>编辑者: {{ toCreator.name }}</p>
                            <p>时间: {{ formatDateTime(toVersion.created_at) }}</p>
                            <p>说明: {{ toVersion.comment || '无说明' }}</p>
                        </div>
                    </div>
                </div>

                <!-- 视图切换 (这部分原代码没有，但 Diff 逻辑依赖它，保留或移除根据你的需求) -->
                <!--
                <div class="mb-4">
                    <div class="flex space-x-4">
                        <button @click="viewMode = 'unified'" :class="[...]">
                            统一视图
                        </button>
                        <button @click="viewMode = 'side-by-side'" :class="[...]">
                            并排视图
                        </button>
                    </div>
                </div>
                 -->

                <!-- 差异内容显示 -->
                <div class="mb-6">
                    <h2 class="text-xl font-bold mb-4">内容差异</h2>
                    <!-- 始终使用统一视图（根据你的代码判断） -->
                    <div class="border rounded-lg overflow-hidden bg-white">
                        <!-- 直接渲染后端生成的 HTML diff -->
                        <div v-if="diffHtml" v-html="diffHtml" class="diff-container p-4 text-sm leading-relaxed"></div>
                        <div v-else class="p-4 text-gray-500 italic">无法加载差异视图或无差异。</div>
                    </div>
                </div>

                <!-- 恢复版本按钮 -->
                <div v-if="canRevert" class="mt-4 text-right">
                    <button @click="confirmRevert"
                        class="px-4 py-2 bg-yellow-600 text-white rounded-md hover:bg-yellow-700 transition">
                        恢复到版本 (v{{ toVersion.version_number }})
                    </button>
                </div>

            </div>
        </div>

        <!-- 恢复版本确认 Modal -->
        <Modal :show="showRevertModal" @close="closeRevertModal" maxWidth="md">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">
                    确认恢复版本
                </h3>
                <p class="mb-4 text-gray-600">
                    您确定要将页面恢复到版本 v{{ toVersion?.version_number || '' }} 吗？
                    <br>此操作将创建一个新版本，保留历史记录。
                </p>
                <div class="flex justify-end">
                    <button @click="closeRevertModal" class="px-4 py-2 bg-gray-200 text-gray-700 rounded mr-2">
                        取消
                    </button>
                    <button @click="revertToVersion" class="px-4 py-2 bg-blue-600 text-white rounded">
                        确认恢复
                    </button>
                </div>
            </div>
        </Modal>

    </MainLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
// 确保导入 usePage 和 Link
import { Link, Head, router, usePage } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayouts/MainLayout.vue';
import Modal from '@/Components/Modal/Modal.vue';
import { formatDateTime } from '@/utils/formatters';
import { mainNavigationLinks } from '@/config/navigationConfig';

const navigationLinks = mainNavigationLinks;
// 使用 usePage 获取页面属性
const pageProps = usePage().props; // <--- 修改点: 使用 usePage().props 访问共享数据

const props = defineProps({
    page: {
        type: Object,
        required: true
    },
    fromVersion: {
        type: Object,
        required: true
    },
    toVersion: {
        type: Object,
        required: true
    },
    fromCreator: {
        type: Object,
        required: true
    },
    toCreator: {
        type: Object,
        required: true
    },
    diffHtml: { // 确保 diffHtml prop 被定义
        type: String,
        default: '<p>无差异信息</p>' // 提供默认值或确保它总被传递
    }
});

const showRevertModal = ref(false);

// 在计算属性中安全地访问 $page.props.auth.user
const canRevert = computed(() => {
    // 使用 pageProps 替代 $page.props
    return pageProps.auth?.user?.permissions?.includes('wiki.edit') || false;
});

const confirmRevert = () => {
    showRevertModal.value = true;
};

const closeRevertModal = () => {
    showRevertModal.value = false;
};

const revertToVersion = () => {
    router.post(route('wiki.revert-version', {
        page: props.page.slug,
        version: props.toVersion.version_number // 恢复到较新的版本
    }), {}, {
        onSuccess: () => {
            closeRevertModal();
            // 可以选择在这里添加一个 flash message 或其他成功提示
        }
    });
};

</script>

<style>
/* 引入 php-diff 库生成的 HTML 所需的基本样式 */
.diff-container table {
    width: 100%;
    border-collapse: collapse;
    font-family: monospace;
    margin-top: 1rem;
    table-layout: fixed;
    /* 防止内容撑开 */
}

.diff-container tbody tr:hover {
    background-color: #f8f9fa;
    /* 行悬停效果 */
}

.diff-container td,
.diff-container th {
    padding: 0.25rem 0.5rem;
    border: 1px solid #dee2e6;
    vertical-align: top;
    line-height: 1.4;
    white-space: pre-wrap;
    /* 保持换行 */
    word-break: break-all;
    /* 长单词换行 */
}

.diff-container .ChangeDelete td.Left,
.diff-container .ChangeReplace td.Left {
    background-color: #fdd;
    /* 删除的行背景（左侧） */
}

.diff-container .ChangeInsert td.Right,
.diff-container .ChangeReplace td.Right {
    background-color: #dfd;
    /* 添加的行背景（右侧） */
}

.diff-container .Left,
.diff-container .Right {
    width: 50%;
    /* 左右两栏宽度 */
}

.diff-container del {
    background-color: #fbb;
    /* 删除的文字 */
    text-decoration: none;
}

.diff-container ins {
    background-color: #bfb;
    /* 添加的文字 */
    text-decoration: none;
}

/* 确保 prose 样式不覆盖 diff 样式 */
.diff-container .prose {
    all: revert;
    /* 撤销 prose 影响 */
}

/* 可以添加更多自定义样式 */
</style>