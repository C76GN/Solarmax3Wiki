<template>
    <MainLayout :navigationLinks="navigationLinks">

        <Head :title="`${page.title} - 版本历史`" />
        <div class="container mx-auto py-6 px-4">
            <div class="bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm rounded-lg shadow-lg p-6">
                <div
                    class="flex flex-col md:flex-row justify-between md:items-center mb-6 pb-4 border-b border-gray-200 dark:border-gray-700">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-2 md:mb-0">{{ page.title }} - 版本历史
                    </h1>
                    <Link :href="route('wiki.show', page.slug)" class="btn-link text-sm">
                    <font-awesome-icon :icon="['fas', 'arrow-left']" class="mr-1" /> 返回页面
                    </Link>
                </div>

                <div v-if="versions.data.length > 1"
                    class="mb-6 p-4 bg-gray-50 dark:bg-gray-800/50 rounded-lg border border-gray-200 dark:border-gray-700">
                    <form @submit.prevent="compareVersions">
                        <div class="flex flex-wrap items-center gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    对比版本
                                </label>
                                <div class="flex items-center gap-2 text-sm">
                                    <select v-model="compareFrom" class="select-field">
                                        <option v-for="version in versions.data" :key="`from-${version.id}`"
                                            :value="version.version_number">
                                            v{{ version.version_number }} ({{ formatDate(version.created_at) }})
                                        </option>
                                    </select>
                                    <span class="text-gray-600 dark:text-gray-400">与</span>
                                    <select v-model="compareTo" class="select-field">
                                        <option v-for="version in versions.data" :key="`to-${version.id}`"
                                            :value="version.version_number">
                                            v{{ version.version_number }} ({{ formatDate(version.created_at) }})
                                        </option>
                                    </select>
                                </div>
                            </div>
                            <button type="submit" class="btn-primary text-sm" :disabled="compareFrom === compareTo">
                                比较版本
                            </button>
                        </div>
                    </form>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left table-fixed">
                        <thead class="bg-gray-100 dark:bg-gray-700/50">
                            <tr>
                                <th class="th-cell w-1/6">版本</th>
                                <th class="th-cell w-1/4">时间</th>
                                <th class="th-cell w-1/6">编辑者</th>
                                <th class="th-cell w-1/3">编辑说明</th>
                                <th class="th-cell w-1/6 text-right">操作</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="version in versions.data" :key="version.id"
                                class="hover:bg-gray-50 dark:hover:bg-gray-800/30"
                                :class="{ 'bg-blue-50 dark:bg-blue-900/30 font-medium': version.is_current }">
                                <td class="td-cell">
                                    <div class="flex items-center">
                                        <span class="font-medium text-gray-900 dark:text-gray-100">v{{
                                            version.version_number }}</span>
                                        <span v-if="version.is_current"
                                            class="ml-2 px-2 py-0.5 text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300 rounded-full">
                                            当前版本
                                        </span>
                                    </div>
                                </td>
                                <td class="td-cell text-gray-600 dark:text-gray-400">
                                    {{ formatDateTime(version.created_at) }}
                                </td>
                                <td class="td-cell text-gray-800 dark:text-gray-200">
                                    {{ version.creator?.name || '未知用户' }}
                                </td>
                                <td class="td-cell text-gray-700 dark:text-gray-300 truncate"
                                    :title="version.comment || '无说明'">
                                    {{ version.comment || '-' }}
                                </td>
                                <td class="td-cell text-right">
                                    <div class="flex justify-end space-x-3">
                                        <Link
                                            :href="route('wiki.show-version', { page: page.slug, version: version.version_number })"
                                            class="btn-link text-xs">查看</Link>
                                        <button v-if="!version.is_current && canRevert" @click="confirmRevert(version)"
                                            class="btn-link text-xs text-yellow-600 dark:text-yellow-400 hover:text-yellow-800 dark:hover:text-yellow-300">恢复</button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="versions.data.length === 0">
                                <td colspan="5" class="py-6 text-center text-gray-500 dark:text-gray-400 italic">
                                    没有历史版本记录。</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <Pagination :links="versions.links" class="mt-6" />
            </div>
        </div>

        <!-- Revert Confirmation Modal - Added :showFooter="true" -->
        <Modal :show="showRevertModal" @close="closeRevertModal" maxWidth="md" title="确认恢复版本" :showFooter="true">
            <div class="p-6">
                <p class="mb-6 text-sm text-gray-600 dark:text-gray-300">
                    您确定要将页面恢复到 <strong class="font-semibold text-gray-900 dark:text-gray-100">版本 v{{
                        revertVersion?.version_number || '' }}</strong> 吗？
                    <br>此操作将在当前版本之后创建一个包含所选版本内容的新版本，并保留所有历史记录。当前版本将不再是最新版本。
                </p>
            </div>
            <template #footer>
                <button @click="closeRevertModal" class="btn-secondary">取消</button>
                <button @click="revertToVersion" class="btn-primary ml-3" :disabled="isReverting">
                    <font-awesome-icon v-if="isReverting" :icon="['fas', 'spinner']" spin class="mr-1" />
                    {{ isReverting ? '恢复中...' : '确认恢复' }}
                </button>
            </template>
        </Modal>

        <FlashMessage ref="flashMessage" />
    </MainLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayouts/MainLayout.vue';
import Pagination from '@/Components/Other/Pagination.vue';
import Modal from '@/Components/Modal/Modal.vue'; // Modal component
import FlashMessage from '@/Components/Other/FlashMessage.vue'; // Flash message component
import { formatDate, formatDateTime } from '@/utils/formatters';
import { mainNavigationLinks } from '@/config/navigationConfig'; // Navigation links

const navigationLinks = mainNavigationLinks;
const pageProps = usePage().props;

// Props definition
const props = defineProps({
    page: { type: Object, required: true },
    versions: { type: Object, required: true },
});

// Refs for component state
const compareFrom = ref(props.versions.data.length > 0 ? props.versions.data[0].version_number : 1);
const compareTo = ref(props.versions.data.length > 1 ? props.versions.data[1].version_number : 1);
const showRevertModal = ref(false);
const isReverting = ref(false);
const revertVersion = ref(null);
const flashMessage = ref(null);

// Computed property to check revert permission
const canRevert = computed(() => {
    return pageProps.auth?.user?.permissions?.includes('wiki.edit') || false;
});

// Method to initiate version comparison
const compareVersions = () => {
    if (compareFrom.value === compareTo.value) {
        flashMessage.value?.addMessage('warning', '请选择两个不同的版本进行比较。');
        return;
    }
    router.get(route('wiki.compare-versions', {
        page: props.page.slug,
        fromVersion: compareFrom.value,
        toVersion: compareTo.value
    }));
};

// Methods for handling revert modal
const confirmRevert = (version) => {
    revertVersion.value = version;
    showRevertModal.value = true;
};

const closeRevertModal = () => {
    showRevertModal.value = false;
    revertVersion.value = null;
};

// Method to perform revert action
const revertToVersion = () => {
    if (!revertVersion.value) return;
    isReverting.value = true;
    router.post(route('wiki.revert-version', {
        page: props.page.slug,
        version: revertVersion.value.version_number
    }), {}, {
        preserveScroll: true,
        onSuccess: () => {
            flashMessage.value?.addMessage('success', `页面已成功恢复到 v${revertVersion.value.version_number}`);
        },
        onError: (errors) => {
            console.error('Revert failed:', errors);
            const errorMsg = Object.values(errors).flat()[0] || '恢复版本失败，请重试。';
            flashMessage.value?.addMessage('error', errorMsg);
        },
        onFinish: () => {
            closeRevertModal();
            isReverting.value = false;
        }
    });
};
</script>

<style scoped>
/* Styles remain the same as before, ensuring consistency */
.th-cell {
    @apply px-4 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wider;
}

.td-cell {
    @apply px-4 py-4 text-sm;
}

.select-field {
    @apply rounded border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200;
}

.btn-primary {
    @apply px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed;
    /* Added font-medium */
}

.btn-secondary {
    @apply px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition dark:bg-gray-600 dark:text-gray-200 dark:hover:bg-gray-500 text-sm font-medium;
    /* Added font-medium */
}

.btn-link {
    @apply text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 hover:underline transition;
}

/* Modal content styles */
.modal-content p {
    @apply text-gray-600 dark:text-gray-300;
}

.modal-content strong {
    @apply font-semibold text-gray-900 dark:text-gray-100;
}
</style>