<template>
    <MainLayout :navigationLinks="navigationLinks">
        <div class="container mx-auto py-6 px-4">
            <div class="bg-white/80 backdrop-blur-sm rounded-lg shadow-lg p-6">
                <div class="flex justify-between items-center mb-6">
                    <h1 class="text-2xl font-bold">{{ page.title }} - 版本历史</h1>
                    <Link :href="route('wiki.show', page.slug)" class="text-blue-600 hover:text-blue-800">
                    返回页面
                    </Link>
                </div>

                <!-- 版本比较表单 -->
                <div v-if="versions.data.length > 1" class="mb-6 p-4 bg-gray-100 rounded-lg">
                    <form @submit.prevent="compareVersions">
                        <div class="flex flex-wrap items-center gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    对比版本
                                </label>
                                <div class="flex items-center gap-2">
                                    <select v-model="compareFrom"
                                        class="rounded border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                        <option v-for="version in versions.data" :key="version.id"
                                            :value="version.version_number">
                                            v{{ version.version_number }} ({{ formatDate(version.created_at) }})
                                        </option>
                                    </select>
                                    <span>与</span>
                                    <select v-model="compareTo"
                                        class="rounded border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                        <option v-for="version in versions.data" :key="version.id"
                                            :value="version.version_number">
                                            v{{ version.version_number }} ({{ formatDate(version.created_at) }})
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <button type="submit"
                                class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition"
                                :disabled="compareFrom === compareTo">
                                比较版本
                            </button>
                        </div>
                    </form>
                </div>

                <!-- 版本列表 -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="px-4 py-3 text-sm font-medium text-gray-600 uppercase tracking-wider">版本</th>
                                <th class="px-4 py-3 text-sm font-medium text-gray-600 uppercase tracking-wider">时间</th>
                                <th class="px-4 py-3 text-sm font-medium text-gray-600 uppercase tracking-wider">编辑者
                                </th>
                                <th class="px-4 py-3 text-sm font-medium text-gray-600 uppercase tracking-wider">编辑说明
                                </th>
                                <th class="px-4 py-3 text-sm font-medium text-gray-600 uppercase tracking-wider">操作</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-for="version in versions.data" :key="version.id"
                                :class="{ 'bg-blue-50': version.is_current }">
                                <td class="px-4 py-4">
                                    <div class="flex items-center">
                                        <span class="font-medium">v{{ version.version_number }}</span>
                                        <span v-if="version.is_current"
                                            class="ml-2 px-2 py-0.5 text-xs font-medium bg-blue-100 text-blue-800 rounded-full">
                                            当前版本
                                        </span>
                                    </div>
                                </td>
                                <td class="px-4 py-4 text-sm text-gray-600">
                                    {{ formatDateTime(version.created_at) }}
                                </td>
                                <td class="px-4 py-4">
                                    {{ version.creator.name }}
                                </td>
                                <td class="px-4 py-4 text-sm">
                                    {{ version.comment || '无说明' }}
                                </td>
                                <td class="px-4 py-4 text-sm">
                                    <div class="flex space-x-2">
                                        <Link
                                            :href="route('wiki.show-version', { page: page.slug, version: version.version_number })"
                                            class="text-blue-600 hover:text-blue-800">
                                        查看
                                        </Link>

                                        <button
                                            v-if="!version.is_current && $page.props.auth.user && $page.props.auth.user.permissions.includes('wiki.edit')"
                                            @click="confirmRevert(version)"
                                            class="text-yellow-600 hover:text-yellow-800">
                                            恢复
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- 分页 -->
                <Pagination :links="versions.links" class="mt-6" />
            </div>
        </div>

        <!-- 确认恢复版本对话框 -->
        <Modal :show="showRevertModal" @close="closeRevertModal" maxWidth="md">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">
                    确认恢复版本
                </h3>
                <p class="mb-4 text-gray-600">
                    您确定要将页面恢复到版本 v{{ revertVersion?.version_number || '' }} 吗？
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
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayouts/MainLayout.vue';
import Pagination from '@/Components/Other/Pagination.vue';
import Modal from '@/Components/Modal/Modal.vue';
import { formatDate, formatDateTime } from '@/utils/formatters';

const navigationLinks = [
    { href: '/wiki', label: 'Wiki' },
    { href: '#', label: '游戏历史&名人墙' },
    { href: '#', label: '自制专区' },
    { href: '#', label: '攻略专区' },
    { href: '#', label: '论坛' }
];

const props = defineProps({
    page: {
        type: Object,
        required: true
    },
    versions: {
        type: Object,
        required: true
    }
});

// 版本比较
const compareFrom = ref(props.versions.data.length > 0 ? props.versions.data[0].version_number : 1);
const compareTo = ref(props.versions.data.length > 1 ? props.versions.data[1].version_number : 1);

const compareVersions = () => {
    router.visit(route('wiki.compare-versions', {
        page: props.page.slug,
        fromVersion: compareFrom.value,
        toVersion: compareTo.value
    }));
};

// 版本恢复
const showRevertModal = ref(false);
const revertVersion = ref(null);

const confirmRevert = (version) => {
    revertVersion.value = version;
    showRevertModal.value = true;
};

const closeRevertModal = () => {
    showRevertModal.value = false;
    revertVersion.value = null;
};

const revertToVersion = () => {
    if (revertVersion.value) {
        router.post(route('wiki.revert-version', {
            page: props.page.slug,
            version: revertVersion.value.version_number
        }), {}, {
            onFinish: () => {
                closeRevertModal();
            }
        });
    }
};
</script>