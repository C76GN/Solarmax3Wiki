<template>
    <MainLayout :navigationLinks="navigationLinks">

        <Head title="Wiki 标签管理" />
        <div class="container mx-auto py-6 px-4">
            <div class="bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm rounded-lg shadow-lg p-6">
                <div class="flex justify-between items-center mb-6 pb-4 border-b dark:border-gray-700">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Wiki 标签管理</h1>
                    <div>
                        <button @click="showAddTagForm = !showAddTagForm" class="btn-primary text-sm">
                            <font-awesome-icon :icon="['fas', showAddTagForm ? 'times' : 'plus']" class="mr-2" />
                            {{ showAddTagForm ? '取消添加' : '添加标签' }}
                        </button>
                    </div>
                </div>

                <!-- Add Tag Form (Conditional) -->
                <Transition name="fade">
                    <div v-if="showAddTagForm"
                        class="mb-6 bg-gray-50 dark:bg-gray-800/50 p-4 rounded-lg border dark:border-gray-700">
                        <form @submit.prevent="addTag" class="flex flex-col sm:flex-row items-end gap-4">
                            <div class="flex-grow w-full sm:w-auto">
                                <label for="tagName"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    新标签名称 <span class="text-red-500">*</span>
                                </label>
                                <input id="tagName" v-model="newTag.name" type="text" class="input-field"
                                    placeholder="输入标签名称" required />
                                <InputError class="mt-1" :message="newTag.error" />
                            </div>
                            <div class="flex space-x-2 flex-shrink-0">
                                <button type="submit" class="btn-primary text-sm"
                                    :disabled="newTag.processing || !newTag.name.trim()">
                                    <font-awesome-icon v-if="newTag.processing" :icon="['fas', 'spinner']" spin
                                        class="mr-1" />
                                    添加
                                </button>
                                <button type="button" @click="cancelAddTag" class="btn-secondary text-sm">
                                    取消
                                </button>
                            </div>
                        </form>
                    </div>
                </Transition>

                <!-- Tags Table -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left table-fixed">
                        <thead class="bg-gray-100 dark:bg-gray-700/50">
                            <tr>
                                <th class="th-cell w-2/5">标签名称</th>
                                <th class="th-cell w-2/5">Slug</th>
                                <th class="th-cell w-1/12">页面</th>
                                <th class="th-cell w-auto text-right pr-6">操作</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-if="tags.length === 0">
                                <td colspan="4" class="py-6 text-center text-gray-500 dark:text-gray-400 italic">
                                    没有找到任何标签。</td>
                            </tr>
                            <tr v-for="tag in tags" :key="tag.id" class="hover:bg-gray-50 dark:hover:bg-gray-800/30">
                                <td class="td-cell">
                                    <div v-if="editingTag && editingTag.id === tag.id"
                                        class="flex items-center space-x-2">
                                        <input v-model="editingTag.name" type="text"
                                            class="input-field py-1 text-sm flex-grow" @keyup.enter="updateTag"
                                            @keyup.esc="cancelEdit" ref="editInput" />
                                        <button @click="updateTag"
                                            class="btn-link text-green-600 dark:text-green-400 p-1" title="保存">
                                            <font-awesome-icon :icon="['fas', 'check']" />
                                        </button>
                                        <button @click="cancelEdit" class="btn-link text-red-600 dark:text-red-400 p-1"
                                            title="取消">
                                            <font-awesome-icon :icon="['fas', 'times']" />
                                        </button>
                                    </div>
                                    <span v-else class="font-medium text-gray-900 dark:text-gray-100">{{ tag.name
                                        }}</span>
                                </td>
                                <td class="td-cell text-gray-600 dark:text-gray-400">{{ tag.slug }}</td>
                                <td class="td-cell text-center text-gray-800 dark:text-gray-200">{{ tag.pages_count }}
                                </td>
                                <td class="td-cell text-right pr-6">
                                    <div class="flex justify-end space-x-3">
                                        <button v-if="$page.props.auth.user.permissions.includes('wiki.manage_tags')"
                                            @click="editTag(tag)" class="btn-link text-xs"
                                            :disabled="editingTag !== null">
                                            <font-awesome-icon :icon="['fas', 'edit']" />
                                        </button>
                                        <button v-if="$page.props.auth.user.permissions.includes('wiki.manage_tags')"
                                            @click="confirmDelete(tag)"
                                            class="btn-link text-xs text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300">
                                            <font-awesome-icon :icon="['fas', 'trash']" />
                                        </button>
                                        <span v-else class="text-xs text-gray-400 italic">无操作权限</span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <Modal :show="showDeleteConfirm" @close="cancelDelete" @confirm="deleteTag" :showFooter="true" dangerAction
            confirmText="确认删除" cancelText="取消" maxWidth="md">
            <template #default>
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4 flex items-center">
                        <font-awesome-icon :icon="['fas', 'exclamation-triangle']" class="text-red-500 mr-2" />
                        确认删除标签
                    </h3>
                    <p class="mb-4 text-gray-600 dark:text-gray-300">
                        确定要删除标签 “<strong class="font-semibold text-gray-800 dark:text-gray-200">{{ tagToDelete?.name
                            }}</strong>” 吗？
                    </p>
                    <p class="text-sm text-red-600 dark:text-red-400">
                        <font-awesome-icon :icon="['fas', 'exclamation-circle']" class="mr-1" /> 此操作不可恢复，相关页面的标签关联也将被删除。
                    </p>
                </div>
            </template>
        </Modal>
        <FlashMessage ref="flashMessage" />
    </MainLayout>
</template>

<script setup>
import { ref, reactive, nextTick } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayouts/MainLayout.vue';
import Modal from '@/Components/Modal/Modal.vue';
import FlashMessage from '@/Components/Other/FlashMessage.vue';
import InputError from '@/Components/Other/InputError.vue'; // Assuming you have this component
import { adminNavigationLinks } from '@/config/navigationConfig';

const navigationLinks = adminNavigationLinks;
const flashMessage = ref(null); // Ref for flash messages

const props = defineProps({
    tags: {
        type: Array,
        required: true
    },
    errors: Object // For validation errors from backend
});

// State for adding a new tag
const showAddTagForm = ref(false);
const newTag = reactive({ // Use reactive for object
    name: '',
    processing: false,
    error: null
});

// State for editing a tag
const editingTag = ref(null);
const editInput = ref(null); // Ref for the edit input element

// State for delete confirmation
const showDeleteConfirm = ref(false);
const tagToDelete = ref(null);

// --- Methods ---

// Add Tag
const addTag = () => {
    newTag.processing = true;
    newTag.error = null;
    router.post(route('wiki.tags.store'), { name: newTag.name }, {
        preserveScroll: true,
        onSuccess: () => {
            newTag.name = '';
            showAddTagForm.value = false;
            if (flashMessage.value) flashMessage.value.addMessage('success', '标签创建成功！');
        },
        onError: (errors) => {
            newTag.error = errors.name || '添加标签失败。';
            if (flashMessage.value && newTag.error !== errors.name) {
                flashMessage.value.addMessage('error', newTag.error);
            }
        },
        onFinish: () => {
            newTag.processing = false;
        }
    });
};

const cancelAddTag = () => {
    showAddTagForm.value = false;
    newTag.name = '';
    newTag.error = null;
};

// Edit Tag
const editTag = (tag) => {
    // Close add form if open
    if (showAddTagForm.value) cancelAddTag();
    // Cancel current edit if any
    if (editingTag.value) cancelEdit();

    editingTag.value = { id: tag.id, name: tag.name };
    // Focus the input after DOM update
    nextTick(() => {
        if (editInput.value) {
            editInput.value.focus();
        }
    });
};

const updateTag = () => {
    if (!editingTag.value) return;
    router.put(route('wiki.tags.update', editingTag.value.id), { name: editingTag.value.name }, {
        preserveScroll: true,
        onSuccess: () => {
            if (flashMessage.value) flashMessage.value.addMessage('success', '标签更新成功！');
            cancelEdit();
        },
        onError: (errors) => {
            const errorMsg = errors.name || '更新标签失败。';
            if (flashMessage.value) {
                flashMessage.value.addMessage('error', errorMsg);
            } else {
                alert(errorMsg); // Fallback
            }
            // Optionally keep the edit state if error?
            // cancelEdit();
        }
    });
};

const cancelEdit = () => {
    editingTag.value = null;
};

// Delete Tag
const confirmDelete = (tag) => {
    tagToDelete.value = tag;
    showDeleteConfirm.value = true;
};

const cancelDelete = () => {
    showDeleteConfirm.value = false;
    tagToDelete.value = null;
};

const deleteTag = () => {
    if (tagToDelete.value) {
        router.delete(route('wiki.tags.destroy', tagToDelete.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                if (flashMessage.value) flashMessage.value.addMessage('success', '标签删除成功！');
            },
            onError: (errors) => {
                const errorMsg = Object.values(errors).flat()[0] || '删除标签失败。';
                if (flashMessage.value) {
                    flashMessage.value.addMessage('error', errorMsg);
                } else {
                    alert(errorMsg); // Fallback
                }
            },
            onFinish: () => {
                cancelDelete();
            }
        });
    }
};
</script>

<style scoped>
/* Shared styles */
.th-cell {
    @apply px-4 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wider;
}

.td-cell {
    @apply px-4 py-4 text-sm;
}

.input-field {
    @apply w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 dark:placeholder-gray-400 text-sm;
}

.btn-primary {
    @apply px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed;
}

.btn-secondary {
    @apply px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition dark:bg-gray-600 dark:text-gray-200 dark:hover:bg-gray-500 text-sm font-medium;
}

.btn-link {
    @apply text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 hover:underline transition disabled:opacity-50 disabled:cursor-not-allowed;
}

/* REMOVED redundant rules */
/*
.btn-link.text-red-600 {
    @apply dark:text-red-400 hover:text-red-800 dark:hover:text-red-300;
}
.btn-link.text-green-600 {
     @apply dark:text-green-400 hover:text-green-800 dark:hover:text-green-300;
}
*/

/* Fade transition for the add form */
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s ease, max-height 0.3s ease, padding 0.3s ease, margin 0.3s ease;
    max-height: 200px;
    /* Adjust as needed */
    overflow: hidden;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
    max-height: 0;
    padding-top: 0;
    padding-bottom: 0;
    margin-bottom: 0;
    overflow: hidden;
}
</style>