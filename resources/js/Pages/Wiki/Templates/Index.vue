// 新文件: resources/js/Pages/Wiki/Templates/Index.vue

<template>
    <MainLayout :navigationLinks="navigationLinks">
        <div class="container mx-auto py-6 px-4">
            <div class="bg-white/80 backdrop-blur-sm rounded-lg shadow-lg p-6">
                <div class="flex justify-between items-center mb-6">
                    <h1 class="text-2xl font-bold">Wiki 页面模板</h1>
                    <Link
                        v-if="$page.props.auth.user && $page.props.auth.user.permissions.includes('wiki.manage_templates')"
                        :href="route('wiki.templates.create')"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                    <font-awesome-icon :icon="['fas', 'plus']" class="mr-2" />
                    创建模板
                    </Link>
                </div>

                <div v-if="templates.length === 0" class="text-center py-8 text-gray-500">
                    暂无页面模板，点击右上角创建第一个模板。
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="min-w-full bg-white">
                        <thead class="bg-gray-100">
                            <tr>
                                <th
                                    class="py-3 px-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    模板名称
                                </th>
                                <th
                                    class="py-3 px-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    描述
                                </th>
                                <th
                                    class="py-3 px-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    字段数量
                                </th>
                                <th
                                    class="py-3 px-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    使用次数
                                </th>
                                <th
                                    class="py-3 px-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    创建者
                                </th>
                                <th
                                    class="py-3 px-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    操作
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-for="template in templates" :key="template.id">
                                <td class="py-4 px-6 text-sm">{{ template.name }}</td>
                                <td class="py-4 px-6 text-sm">{{ template.description || '-' }}</td>
                                <td class="py-4 px-6 text-sm">{{ template.structure ? template.structure.length : 0 }}
                                </td>
                                <td class="py-4 px-6 text-sm">{{ template.pages_count }}</td>
                                <td class="py-4 px-6 text-sm">{{ template.creator ? template.creator.name : '-' }}</td>
                                <td class="py-4 px-6 text-sm space-x-2">
                                    <Link :href="route('wiki.templates.edit', template.id)"
                                        class="text-blue-600 hover:text-blue-800">
                                    <font-awesome-icon :icon="['fas', 'edit']" />
                                    </Link>
                                    <button @click="confirmDelete(template)" class="text-red-600 hover:text-red-800"
                                        :disabled="template.pages_count > 0">
                                        <font-awesome-icon :icon="['fas', 'trash']" />
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <Modal :show="showDeleteConfirm" @close="cancelDelete" @confirm="deleteTemplate" title="确认删除模板"
            :showFooter="true" :dangerAction="true" confirmText="删除" cancelText="取消">
            <div class="p-6">
                <p class="mb-4 text-gray-600">确定要删除模板 {{ templateToDelete?.name }} 吗？此操作不可恢复。</p>
            </div>
        </Modal>
    </MainLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayouts/MainLayout.vue';
import Modal from '@/Components/Modal/Modal.vue'
import { adminNavigationLinks } from '@/config/navigationConfig';

const navigationLinks = adminNavigationLinks;
const props = defineProps({
    templates: {
        type: Array,
        required: true
    }
});


const showDeleteConfirm = ref(false);
const templateToDelete = ref(null);

const confirmDelete = (template) => {
    if (template.pages_count > 0) {
        alert('该模板已被使用，无法删除');
        return;
    }

    templateToDelete.value = template;
    showDeleteConfirm.value = true;
};

const cancelDelete = () => {
    showDeleteConfirm.value = false;
    templateToDelete.value = null;
};

const deleteTemplate = () => {
    if (templateToDelete.value) {
        router.delete(route('wiki.templates.destroy', templateToDelete.value.id), {
            onSuccess: () => {
                showDeleteConfirm.value = false;
                templateToDelete.value = null;
            }
        });
    }
};
</script>