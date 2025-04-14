<template>
    <MainLayout :navigationLinks="navigationLinks">

        <Head title="角色管理" />
        <div class="container mx-auto py-6 px-4">
            <div class="bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm rounded-lg shadow-lg p-6">
                <div class="flex justify-between items-center mb-6 pb-4 border-b dark:border-gray-700">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">角色管理</h1>
                    <Link v-if="$page.props.auth.user.permissions.includes('role.create')" :href="route('roles.create')"
                        class="btn-primary text-sm">
                    <font-awesome-icon :icon="['fas', 'plus']" class="mr-1" /> 创建角色
                    </Link>
                </div>

                <div class="overflow-x-auto">
                    <!-- Removed table-fixed -->
                    <table class="w-full text-left">
                        <thead class="bg-gray-100 dark:bg-gray-700/50">
                            <tr>
                                <!-- Adjusted column widths -->
                                <th class="th-cell w-1/4">角色名称</th>
                                <th class="th-cell w-2/5">描述</th>
                                <th class="th-cell w-auto">权限数量</th> <!-- Use w-auto -->
                                <th class="th-cell w-auto">创建时间</th> <!-- Use w-auto -->
                                <th class="th-cell w-auto text-right pr-6">操作</th> <!-- Use w-auto -->
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-if="roles.data.length === 0">
                                <td colspan="5" class="py-6 text-center text-gray-500 dark:text-gray-400 italic">
                                    没有找到任何角色。</td>
                            </tr>
                            <tr v-for="role in roles.data" :key="role.id"
                                class="hover:bg-gray-50 dark:hover:bg-gray-800/30">
                                <td class="td-cell align-top"> <!-- Added align-top -->
                                    <div class="flex items-center">
                                        <span class="font-medium text-gray-900 dark:text-gray-100">{{ role.display_name
                                            }}</span>
                                        <span v-if="role.is_system"
                                            class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300">
                                            系统
                                        </span>
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ role.name }}</div>
                                </td>
                                <td class="td-cell align-top text-gray-700 dark:text-gray-300">{{ role.description ||
                                    '-' }}</td>
                                <td class="td-cell align-top text-center text-gray-900 dark:text-gray-100">{{
                                    role.permissions_count }}</td> <!-- Added text-center -->
                                <td class="td-cell align-top text-gray-600 dark:text-gray-400 whitespace-nowrap">
                                    <!-- Added align-top and nowrap -->
                                    {{ formatDate(role.created_at) }}
                                </td>
                                <td class="td-cell align-top text-right pr-6 whitespace-nowrap">
                                    <!-- Added align-top and nowrap -->
                                    <div class="flex justify-end space-x-3">
                                        <Link v-if="!role.is_system && $page.props.canEdit"
                                            :href="route('roles.edit', role.id)" class="btn-link text-xs">
                                        <font-awesome-icon :icon="['fas', 'edit']" class="mr-1" /> 编辑
                                        </Link>
                                        <span v-else-if="role.is_system && $page.props.canEdit"
                                            class="text-xs text-gray-400 italic" title="系统角色不可编辑">不可编辑</span>

                                        <button v-if="!role.is_system && $page.props.canDelete"
                                            @click="confirmDelete(role)"
                                            class="btn-link text-xs text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300">
                                            <font-awesome-icon :icon="['fas', 'trash']" class="mr-1" /> 删除
                                        </button>
                                        <span v-else-if="role.is_system && $page.props.canDelete"
                                            class="text-xs text-gray-400 italic" title="系统角色不可删除">不可删除</span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <Pagination :links="roles.links" class="mt-6" />
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <Modal :show="confirmingDeletion" @close="cancelDelete" @confirm="deleteRole" :showFooter="true" dangerAction
            confirmText="确认删除" cancelText="取消" maxWidth="md">
            <template #default>
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4 flex items-center">
                        <font-awesome-icon :icon="['fas', 'exclamation-triangle']" class="text-red-500 mr-2" />
                        确认删除角色
                    </h3>
                    <p class="mb-4 text-gray-600 dark:text-gray-300">
                        确定要删除角色 “<strong class="font-semibold text-gray-800 dark:text-gray-200">{{
                            roleToDelete?.display_name }}</strong>” ({{ roleToDelete?.name }}) 吗？
                    </p>
                    <p class="text-sm text-red-600 dark:text-red-400"><font-awesome-icon
                            :icon="['fas', 'exclamation-circle']" class="mr-1" /> 此操作不可恢复，关联用户的此角色也将被移除。</p>
                </div>
            </template>
        </Modal>
        <FlashMessage ref="flashMessage" />
    </MainLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import MainLayout from "@/Layouts/MainLayouts/MainLayout.vue";
import Pagination from '@/Components/Other/Pagination.vue';
import Modal from '@/Components/Modal/Modal.vue';
import FlashMessage from '@/Components/Other/FlashMessage.vue';
import { adminNavigationLinks } from '@/config/navigationConfig';
import { formatDate } from '@/utils/formatters';

const navigationLinks = adminNavigationLinks;
const pageProps = usePage().props;
const flashMessage = ref(null);

const props = defineProps({
    roles: {
        type: Object,
        required: true
    },
    canEdit: {
        type: Boolean,
        default: false,
    },
    canDelete: {
        type: Boolean,
        default: false,
    }
});

const confirmingDeletion = ref(false);
const roleToDelete = ref(null);

const confirmDeleteAction = (role) => {
    if (role.is_system) return;
    roleToDelete.value = role;
    confirmingDeletion.value = true;
};

const cancelDelete = () => {
    confirmingDeletion.value = false;
    roleToDelete.value = null;
};

const deleteRole = () => {
    if (roleToDelete.value && !roleToDelete.value.is_system) {
        router.delete(route('roles.destroy', roleToDelete.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                // Flash message handled globally
            },
            onError: (errors) => {
                const errorMsg = Object.values(errors).flat()[0] || '删除角色失败，请重试。';
                if (flashMessage.value) {
                    flashMessage.value.addMessage('error', errorMsg);
                } else {
                    alert(errorMsg); // Fallback alert
                }
            },
            onFinish: () => {
                cancelDelete(); // Close modal regardless of outcome
            }
        });
    }
};

// Make confirmDelete available in the template
const confirmDelete = (role) => {
    confirmDeleteAction(role);
};
</script>

<style scoped>
/* Shared styles for table cells */
.th-cell {
    @apply px-4 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap;
    /* Added nowrap to headers */
}

.td-cell {
    @apply px-4 py-4 text-sm;
}

/* Button styles */
.btn-primary {
    @apply px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm disabled:opacity-50;
}

.btn-link {
    @apply text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 hover:underline transition whitespace-nowrap;
    /* Added nowrap to buttons */
}

/* Modal content text styling */
.modal-content p {
    @apply text-gray-600 dark:text-gray-300;
}

.modal-content strong {
    @apply font-semibold text-gray-800 dark:text-gray-200;
}
</style>