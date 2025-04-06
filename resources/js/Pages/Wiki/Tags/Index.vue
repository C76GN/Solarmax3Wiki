<template>
    <MainLayout :navigationLinks="navigationLinks">
        <div class="container mx-auto py-6 px-4">
            <div class="bg-white/80 backdrop-blur-sm rounded-lg shadow-lg p-6">
                <div class="flex justify-between items-center mb-6">
                    <h1 class="text-2xl font-bold">Wiki 标签管理</h1>
                    <div>
                        <button @click="showAddTagForm = true"
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                            <font-awesome-icon :icon="['fas', 'plus']" class="mr-2" />
                            添加标签
                        </button>
                    </div>
                </div>

                <!-- 添加标签表单 -->
                <div v-if="showAddTagForm" class="mb-6 bg-gray-50 p-4 rounded-lg">
                    <form @submit.prevent="addTag" class="flex items-end space-x-4">
                        <div class="flex-grow">
                            <label for="tagName" class="block text-sm font-medium text-gray-700 mb-1">
                                标签名称
                            </label>
                            <input id="tagName" v-model="newTag.name" type="text"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="输入标签名称" required />
                            <div v-if="newTag.error" class="mt-1 text-sm text-red-600">
                                {{ newTag.error }}
                            </div>
                        </div>
                        <div class="flex space-x-2">
                            <button type="submit"
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
                                :disabled="newTag.processing">
                                添加
                            </button>
                            <button type="button" @click="cancelAddTag"
                                class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                                取消
                            </button>
                        </div>
                    </form>
                </div>

                <!-- 标签列表 -->
                <div class="bg-white rounded-lg overflow-hidden">
                    <table class="min-w-full">
                        <thead class="bg-gray-100">
                            <tr>
                                <th
                                    class="py-3 px-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    标签名称</th>
                                <th
                                    class="py-3 px-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Slug</th>
                                <th
                                    class="py-3 px-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    页面数量</th>
                                <th
                                    class="py-3 px-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    操作</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-for="tag in tags" :key="tag.id">
                                <td class="py-4 px-6 text-sm">
                                    <div v-if="editingTag && editingTag.id === tag.id" class="flex space-x-2">
                                        <input v-model="editingTag.name" type="text"
                                            class="px-2 py-1 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500"
                                            @keyup.enter="updateTag" />
                                        <button @click="updateTag" class="text-green-600 hover:text-green-800">
                                            <font-awesome-icon :icon="['fas', 'check']" />
                                        </button>
                                        <button @click="cancelEdit" class="text-red-600 hover:text-red-800">
                                            <font-awesome-icon :icon="['fas', 'times']" />
                                        </button>
                                    </div>
                                    <span v-else>{{ tag.name }}</span>
                                </td>
                                <td class="py-4 px-6 text-sm text-gray-500">{{ tag.slug }}</td>
                                <td class="py-4 px-6 text-sm">{{ tag.pages_count }}</td>
                                <td class="py-4 px-6 text-sm space-x-2">
                                    <button @click="editTag(tag)" class="text-blue-600 hover:text-blue-800"
                                        :disabled="editingTag !== null">
                                        <font-awesome-icon :icon="['fas', 'edit']" />
                                    </button>
                                    <button @click="confirmDelete(tag)" class="text-red-600 hover:text-red-800">
                                        <font-awesome-icon :icon="['fas', 'trash']" />
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 删除确认对话框 -->
        <ConfirmModal :show="showDeleteConfirm" title="确认删除标签"
            :message="'确定要删除标签 ' + tagToDelete?.name + ' 吗？此操作不可恢复，相关页面的标签关联也将被删除。'" confirm-text="删除" cancel-text="取消"
            :danger-action="true" @close="cancelDelete" @confirm="deleteTag" />
    </MainLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayouts/MainLayout.vue';
import ConfirmModal from '@/Components/Modal/ConfirmModal.vue';

const navigationLinks = [
    { href: '/wiki', label: 'Wiki' },
    { href: '/wiki/categories', label: '分类管理' },
    { href: '/wiki/tags', label: '标签管理' },
    { href: '#', label: '模板管理' },
];

const props = defineProps({
    tags: {
        type: Array,
        required: true
    }
});

// 新标签表单
const showAddTagForm = ref(false);
const newTag = ref({
    name: '',
    processing: false,
    error: null
});

// 编辑标签
const editingTag = ref(null);

// 删除确认
const showDeleteConfirm = ref(false);
const tagToDelete = ref(null);

// 添加标签
const addTag = () => {
    newTag.value.processing = true;
    newTag.value.error = null;

    router.post(route('wiki.tags.store'), {
        name: newTag.value.name
    }, {
        onSuccess: () => {
            newTag.value.name = '';
            showAddTagForm.value = false;
            newTag.value.processing = false;
        },
        onError: (errors) => {
            newTag.value.error = errors.name;
            newTag.value.processing = false;
        }
    });
};

// 取消添加标签
const cancelAddTag = () => {
    showAddTagForm.value = false;
    newTag.value.name = '';
    newTag.value.error = null;
};

// 开始编辑标签
const editTag = (tag) => {
    editingTag.value = {
        id: tag.id,
        name: tag.name
    };
};

// 更新标签
const updateTag = () => {
    if (!editingTag.value) return;

    router.put(route('wiki.tags.update', editingTag.value.id), {
        name: editingTag.value.name
    }, {
        onSuccess: () => {
            editingTag.value = null;
        }
    });
};

// 取消编辑
const cancelEdit = () => {
    editingTag.value = null;
};

// 确认删除
const confirmDelete = (tag) => {
    tagToDelete.value = tag;
    showDeleteConfirm.value = true;
};

// 取消删除
const cancelDelete = () => {
    showDeleteConfirm.value = false;
    tagToDelete.value = null;
};

// 删除标签
const deleteTag = () => {
    if (tagToDelete.value) {
        router.delete(route('wiki.tags.destroy', tagToDelete.value.id), {
            onSuccess: () => {
                showDeleteConfirm.value = false;
                tagToDelete.value = null;
            }
        });
    }
};
</script>