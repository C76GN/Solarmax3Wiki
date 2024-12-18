// FileName: /var/www/Solarmax3Wiki/resources/js/Pages/Wiki/Revisions/Revisions.vue
<template>
    <MainLayout
        :navigationLinks="[{ href: '/wiki', label: '游戏维基' }, { href: '#', label: '游戏历史&名人墙' }, { href: '#', label: '自制专区' }, { href: '#', label: '攻略专区' }, { href: '#', label: '论坛' }]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- 页面头部 -->
            <div class="bg-white/80 backdrop-blur-sm shadow-lg rounded-lg mb-6">
                <div class="px-6 py-4">
                    <div class="flex justify-between items-center">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">
                                {{ page.title }} - 版本历史
                            </h1>
                            <p class="mt-1 text-sm text-gray-500">
                                共 {{ revisions.total }} 个版本
                            </p>
                        </div>
                        <div class="flex gap-4">
                            <Link :href="route('wiki.show', page.id)"
                                class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md text-sm text-gray-700 bg-white hover:bg-gray-50">
                            返回页面
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 版本比较工具 -->
            <div v-if="selectedVersions.length > 0" class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                <div class="flex justify-between items-center">
                    <div class="text-sm text-blue-700">
                        已选择 {{ selectedVersions.length }} 个版本
                        <span v-if="selectedVersions.length === 2">
                            （版本 {{ Math.min(...selectedVersions) }} 与 {{ Math.max(...selectedVersions) }}）
                        </span>
                    </div>
                    <div class="flex gap-2">
                        <button @click="selectedVersions = []"
                            class="px-3 py-1 text-sm text-gray-600 hover:text-gray-900">
                            清除选择
                        </button>
                        <button v-if="selectedVersions.length === 2" @click="compareSelectedVersions"
                            class="px-3 py-1 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                            比较这些版本
                        </button>
                    </div>
                </div>
            </div>

            <!-- 版本列表 -->
            <div class="bg-white/80 backdrop-blur-sm shadow-lg rounded-lg">
                <div class="divide-y divide-gray-200">
                    <div v-for="revision in revisions.data" :key="revision.version" class="p-6">
                        <div class="flex items-start justify-between">
                            <div class="flex items-start space-x-4">
                                <!-- 选择框 -->
                                <div v-if="!isCurrentVersion(revision.version)" class="pt-1">
                                    <input type="checkbox" :value="revision.version" v-model="selectedVersions"
                                        :disabled="selectedVersions.length >= 2 && !selectedVersions.includes(revision.version)"
                                        class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                </div>
                                <!-- 版本信息 -->
                                <div>
                                    <div class="flex items-center">
                                        <span class="text-lg font-medium text-gray-900">
                                            版本 {{ revision.version }}
                                        </span>
                                        <span v-if="isCurrentVersion(revision.version)"
                                            class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            当前版本
                                        </span>
                                    </div>
                                    <div class="mt-1 text-sm text-gray-500">
                                        由 {{ revision.creator.name }} 于 {{ formatDate(revision.created_at) }} 创建
                                        <span v-if="revision.comment" class="ml-2 text-gray-400">
                                            - {{ revision.comment }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <!-- 操作按钮 -->
                            <div class="flex gap-2">
                                <Link :href="route('wiki.show-revision', [page.id, revision.version])"
                                    class="text-blue-600 hover:text-blue-900">
                                查看
                                </Link>
                                <button v-if="can.edit && !isCurrentVersion(revision.version)"
                                    @click="revertToVersion(revision.version)"
                                    class="text-green-600 hover:text-green-900">
                                    恢复此版本
                                </button>
                            </div>
                        </div>
                        <!-- 变更统计 -->
                        <div v-if="revision.changes" class="mt-2 space-y-1">
                            <div v-if="revision.changes.added?.length" class="text-sm text-green-600">
                                + 添加了 {{ revision.changes.added.length }} 行
                            </div>
                            <div v-if="revision.changes.removed?.length" class="text-sm text-red-600">
                                - 删除了 {{ revision.changes.removed.length }} 行
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 分页 -->
            <div class="mt-6">
                <Pagination :links="revisions.links" />
            </div>
        </div>

        <!-- 确认对话框 -->
        <Modal :show="showConfirmRevert" @close="showConfirmRevert = false">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900">
                    确认恢复版本
                </h3>
                <p class="mt-2 text-sm text-gray-500">
                    确定要将页面恢复到版本 {{ versionToRevert }} 吗？此操作将创建一个新的版本。
                </p>
                <div class="mt-4 flex justify-end gap-2">
                    <button @click="showConfirmRevert = false"
                        class="px-4 py-2 text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200">
                        取消
                    </button>
                    <button @click="confirmRevert"
                        class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                        确认恢复
                    </button>
                </div>
            </div>
        </Modal>
    </MainLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayouts/MainLayout.vue';
import Modal from '@/Components/Modal/Modal.vue';
import Pagination from '@/Components/Other/Pagination.vue';

const props = defineProps({
    page: {
        type: Object,
        required: true
    },
    revisions: {
        type: Object,
        required: true
    },
    can: {
        type: Object,
        required: true
    }
});

const selectedVersions = ref([]);
const showConfirmRevert = ref(false);
const versionToRevert = ref(null);

const formatDate = (date) => {
    return new Date(date).toLocaleString('zh-CN', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const isCurrentVersion = (version) => {
    return props.page.current_version === version;
};

const compareSelectedVersions = () => {
    const [fromVersion, toVersion] = selectedVersions.value.sort((a, b) => a - b);
    router.visit(route('wiki.compare-revisions', [props.page.id, fromVersion, toVersion]));
};

const revertToVersion = (version) => {
    versionToRevert.value = version;
    showConfirmRevert.value = true;
};

const confirmRevert = () => {
    router.post(route('wiki.revert-version', [props.page.id, versionToRevert.value]), {}, {
        onSuccess: () => {
            showConfirmRevert.value = false;
            versionToRevert.value = null;
        }
    });
};
</script>