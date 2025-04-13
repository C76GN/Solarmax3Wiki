<template>
    <MainLayout :navigationLinks="navigationLinks">

        <Head :title="`比较版本: v${fromVersion.version_number} vs v${toVersion.version_number} - ${page.title}`" />
        <div class="container mx-auto py-6 px-4">
            <div class="bg-gray-800/90 text-gray-200 backdrop-blur-sm rounded-lg shadow-lg p-6">
                <div
                    class="flex flex-col md:flex-row justify-between md:items-center mb-6 border-b pb-4 border-gray-700">
                    <h1 class="text-2xl md:text-3xl font-bold text-gray-100 mb-2 md:mb-0">
                        {{ page.title }} - 版本比较
                    </h1>
                    <div class="flex items-center space-x-3 text-sm">
                        <Link :href="route('wiki.history', page.slug)"
                            class="text-blue-400 hover:underline flex items-center">
                        <font-awesome-icon :icon="['fas', 'arrow-left']" class="mr-1 h-3 w-3" /> 返回历史版本
                        </Link>
                        <Link :href="route('wiki.show', page.slug)"
                            class="text-blue-400 hover:underline flex items-center">
                        <font-awesome-icon :icon="['fas', 'eye']" class="mr-1 h-3 w-3" /> 返回页面
                        </Link>
                    </div>
                </div>

                <div class="mb-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="p-4 bg-red-900/40 rounded-lg border border-red-700/60 shadow-sm">
                        <h3 class="font-semibold mb-2 text-red-300 flex items-center">
                            <font-awesome-icon :icon="['fas', 'history']" class="mr-2 text-red-400" />
                            旧版本 (v{{ fromVersion.version_number }})
                        </h3>
                        <div class="text-sm text-gray-400 space-y-1">
                            <p><font-awesome-icon :icon="['fas', 'user']" class="mr-1.5 w-3" /> 编辑者: {{
                                fromCreator?.name || '未知用户' }}</p>
                            <p><font-awesome-icon :icon="['fas', 'clock']" class="mr-1.5 w-3" /> 时间: {{
                                formatDateTime(fromVersion.created_at) }}</p>
                            <p><font-awesome-icon :icon="['fas', 'comment']" class="mr-1.5 w-3" /> 说明: {{
                                fromVersion.comment || '无说明' }}</p>
                        </div>
                        <div class="mt-4 text-right" v-if="canRevert">
                            <button @click="confirmRevert(fromVersion)"
                                class="inline-flex items-center px-3 py-1 bg-yellow-600 text-white rounded-md hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2 focus:ring-offset-gray-800 transition text-xs font-medium">
                                <font-awesome-icon :icon="['fas', 'undo']" class="mr-1.5 h-3 w-3" />
                                恢复到此版本
                            </button>
                        </div>
                    </div>
                    <div class="p-4 bg-green-900/40 rounded-lg border border-green-700/60 shadow-sm">
                        <h3 class="font-semibold mb-2 text-green-300 flex items-center">
                            <font-awesome-icon :icon="['fas', 'history']" class="mr-2 text-green-400" />
                            新版本 (v{{ toVersion.version_number }})
                        </h3>
                        <div class="text-sm text-gray-400 space-y-1">
                            <p><font-awesome-icon :icon="['fas', 'user']" class="mr-1.5 w-3" /> 编辑者: {{ toCreator?.name
                                || '未知用户' }}</p>
                            <p><font-awesome-icon :icon="['fas', 'clock']" class="mr-1.5 w-3" /> 时间: {{
                                formatDateTime(toVersion.created_at) }}</p>
                            <p><font-awesome-icon :icon="['fas', 'comment']" class="mr-1.5 w-3" /> 说明: {{
                                toVersion.comment || '无说明' }}</p>
                        </div>
                        <div class="mt-4 text-right" v-if="canRevert">
                            <button @click="confirmRevert(toVersion)"
                                class="inline-flex items-center px-3 py-1 bg-yellow-600 text-white rounded-md hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2 focus:ring-offset-gray-800 transition text-xs font-medium">
                                <font-awesome-icon :icon="['fas', 'undo']" class="mr-1.5 h-3 w-3" />
                                恢复到此版本
                            </button>
                        </div>
                    </div>
                </div>

                <div class="mb-6">
                    <h2 class="text-xl font-bold mb-4 text-gray-200">内容差异</h2>
                    <div class="diff-container">
                        <div v-if="diffHtml" v-html="diffHtml" class="specific-diff-styling leading-relaxed"></div>
                        <div v-else class="p-4 text-gray-400 italic">无法加载差异视图或无差异。</div>
                    </div>
                </div>
            </div>
        </div>

        <Modal :show="showRevertModal" @close="closeRevertModal" maxWidth="md">
            <div class="p-6 bg-gray-800 rounded-lg">
                <h3 class="text-lg font-medium text-gray-100 mb-4 flex items-center">
                    <font-awesome-icon :icon="['fas', 'exclamation-triangle']" class="text-yellow-500 mr-2" />
                    确认恢复版本
                </h3>
                <p class="mb-6 text-sm text-gray-300">
                    您确定要将页面恢复到 <strong class="font-semibold text-gray-100">版本 v{{ versionToRevert?.version_number || ''
                        }}</strong> 吗？
                    <br>
                    此操作将在当前版本之后创建一个包含所选版本内容的新版本，并保留所有历史记录。当前版本将不再是最新版本。
                </p>
                <div class="flex justify-end space-x-3">
                    <button @click="closeRevertModal"
                        class="px-4 py-2 bg-gray-600 text-gray-200 rounded-md hover:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2 focus:ring-offset-gray-800 text-sm font-medium">
                        取消
                    </button>
                    <button @click="revertToVersion"
                        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:ring-offset-gray-800 text-sm font-medium disabled:opacity-50"
                        :disabled="isReverting">
                        <font-awesome-icon v-if="isReverting" :icon="['fas', 'spinner']" spin class="mr-1" />
                        {{ isReverting ? '恢复中...' : '确认恢复' }}
                    </button>
                </div>
            </div>
        </Modal>
        <FlashMessage ref="flashMessage" />
    </MainLayout>
</template>

<script setup>
// Script 部分保持不变
import { ref, computed, onMounted } from 'vue';
import { Link, Head, router, usePage } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayouts/MainLayout.vue';
import Modal from '@/Components/Modal/Modal.vue';
import FlashMessage from '@/Components/Other/FlashMessage.vue';
import { formatDateTime } from '@/utils/formatters';
import { mainNavigationLinks } from '@/config/navigationConfig';
const navigationLinks = mainNavigationLinks;
const pageProps = usePage().props;
const flashMessage = ref(null); // Ref for flash message component
const props = defineProps({
    page: { type: Object, required: true },
    fromVersion: { type: Object, required: true },
    toVersion: { type: Object, required: true },
    fromCreator: { type: Object, required: true },
    toCreator: { type: Object, required: true },
    diffHtml: { type: String, default: '<p>无差异信息</p>' }
});
const showRevertModal = ref(false);
const isReverting = ref(false);
const versionToRevert = ref(null);
const canRevert = computed(() => {
    // Ensure auth.user and permissions exist before accessing
    return pageProps.auth?.user?.permissions?.includes('wiki.edit') || false;
});
const confirmRevert = (version) => {
    versionToRevert.value = version;
    showRevertModal.value = true;
};
const closeRevertModal = () => {
    showRevertModal.value = false;
    versionToRevert.value = null;
};
const revertToVersion = () => {
    if (!versionToRevert.value) return;
    isReverting.value = true;
    router.post(route('wiki.revert-version', {
        page: props.page.slug,
        version: versionToRevert.value.version_number
    }), {}, {
        preserveScroll: true,
        onSuccess: () => {
            closeRevertModal();
            // Use the flash prop directly if available and correctly handled by MainLayout/FlashMessage
            router.page.props.flash = { message: { type: 'success', text: `页面已成功恢复到 v${versionToRevert.value.version_number}` } };
            // Or trigger the FlashMessage component instance if needed
            // flashMessage.value?.addMessage('success', `页面已成功恢复到 v${versionToRevert.value.version_number}`);
        },
        onError: (errors) => {
            console.error('Revert failed:', errors);
            const errorMsg = Object.values(errors).flat()[0] || '恢复版本失败，请重试。';
            router.page.props.flash = { message: { type: 'error', text: errorMsg } };
            // flashMessage.value?.addMessage('error', errorMsg);
        },
        onFinish: () => {
            isReverting.value = false;
        }
    });
};
</script>

<style scoped>
/* Scoped styles specific to Compare.vue */
.diff-container {
    max-height: 70vh;
    overflow-y: auto;
    background-color: #111827;
    /* Ensure diff container background is dark */
    border: 1px solid #4b5563;
    /* Dark border */
    border-radius: 0.5rem;
    /* rounded-lg */
}

/* Use :deep to style v-html content */
:deep(.diff-container table.diff) {
    font-size: 0.8rem;
    color: #d1d5db;
    /* Ensure default text color is light */
    background-color: #111827;
    /* Match container background */
}

:deep(.diff-container td) {
    border-color: #4b5563 !important;
    /* Darker border for cells */
    color: #d1d5db;
    /* Light text in cells */
}

:deep(.diff-container th) {
    background-color: #374151 !important;
    /* Tailwind gray-700 for headers */
    border-color: #4b5563 !important;
    /* Darker border for headers */
    color: #f3f4f6 !important;
    /* Tailwind gray-100 for header text */
}

:deep(.diff-container td.lines-no) {
    width: 35px !important;
    min-width: 35px !important;
    padding-right: 0.5rem;
    background-color: #1f2937 !important;
    /* Tailwind gray-800 for line numbers */
    color: #6b7280 !important;
    /* Tailwind gray-500 for line number text */
    border-right-color: #4b5563 !important;
}

/* Ensure deletion/insertion styles from app.css apply correctly */
:deep(.diff-container .ChangeDelete .Left) {
    background-color: rgba(127, 29, 29, 0.4) !important;
    /* Tailwind red-900/40 */
}

:deep(.diff-container .ChangeDelete .Left del) {
    background-color: rgba(185, 28, 28, 0.5) !important;
    color: #fecaca !important;
    /* Tailwind red-200 */
}

:deep(.diff-container .ChangeInsert .Right) {
    background-color: rgba(6, 78, 59, 0.4) !important;
    /* Tailwind green-900/40 */
}

:deep(.diff-container .ChangeInsert .Right ins) {
    background-color: rgba(4, 120, 87, 0.5) !important;
    color: #a7f3d0 !important;
    /* Tailwind green-200 */
}

/* Version Content Preview */
.version-content {
    @apply border p-4 rounded bg-gray-700/50 border-gray-600 max-h-96 overflow-y-auto text-sm prose max-w-none prose-invert;
}

.version-content.border-red-700\/60 {
    /* More specific class if needed */
    border-color: rgba(185, 28, 28, 0.6);
}

.version-content.bg-red-900\/40 {
    /* More specific class if needed */
    background-color: rgba(127, 29, 29, 0.4);
}
</style>