<template>
    <!-- 主要布局容器，引入导航链接 -->
    <MainLayout :navigationLinks="navigationLinks">

        <!-- 设置页面标题 -->

        <Head title="Wiki 标签管理" />
        <div class="container mx-auto py-6 px-4">
            <!-- 页面内容主体卡片 -->
            <div class="bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm rounded-lg shadow-lg p-6">
                <!-- 页面头部，包含标题和添加标签按钮 -->
                <div class="flex justify-between items-center mb-6 pb-4 border-b dark:border-gray-700">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Wiki 标签管理</h1>
                    <div>
                        <!-- 控制添加标签表单显示/隐藏的按钮 -->
                        <button @click="showAddTagForm = !showAddTagForm" class="btn-primary text-sm">
                            <font-awesome-icon :icon="['fas', showAddTagForm ? 'times' : 'plus']" class="mr-2" />
                            {{ showAddTagForm ? '取消添加' : '添加标签' }}
                        </button>
                    </div>
                </div>

                <!-- 添加标签表单 (带过渡效果，根据 showAddTagForm 状态显示) -->
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
                                <!-- 显示新标签名称的验证错误 -->
                                <InputError class="mt-1" :message="newTag.error" />
                            </div>
                            <div class="flex space-x-2 flex-shrink-0">
                                <!-- 添加标签提交按钮 -->
                                <button type="submit" class="btn-primary text-sm"
                                    :disabled="newTag.processing || !newTag.name.trim()">
                                    <font-awesome-icon v-if="newTag.processing" :icon="['fas', 'spinner']" spin
                                        class="mr-1" />
                                    添加
                                </button>
                                <!-- 取消添加标签按钮 -->
                                <button type="button" @click="cancelAddTag" class="btn-secondary text-sm">
                                    取消
                                </button>
                            </div>
                        </form>
                    </div>
                </Transition>

                <!-- 标签列表表格 -->
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
                            <!-- 如果没有标签，显示提示信息 -->
                            <tr v-if="tags.length === 0">
                                <td colspan="4" class="py-6 text-center text-gray-500 dark:text-gray-400 italic">
                                    没有找到任何标签。</td>
                            </tr>
                            <!-- 遍历并显示每个标签 -->
                            <tr v-for="tag in tags" :key="tag.id" class="hover:bg-gray-50 dark:hover:bg-gray-800/30">
                                <td class="td-cell">
                                    <!-- 编辑标签输入框 (当处于编辑模式时显示) -->
                                    <div v-if="editingTag && editingTag.id === tag.id"
                                        class="flex items-center space-x-2">
                                        <input v-model="editingTag.name" type="text"
                                            class="input-field py-1 text-sm flex-grow" @keyup.enter="updateTag"
                                            @keyup.esc="cancelEdit" ref="editInput" />
                                        <!-- 保存编辑按钮 -->
                                        <button @click="updateTag"
                                            class="btn-link text-green-600 dark:text-green-400 p-1" title="保存">
                                            <font-awesome-icon :icon="['fas', 'check']" />
                                        </button>
                                        <!-- 取消编辑按钮 -->
                                        <button @click="cancelEdit" class="btn-link text-red-600 dark:text-red-400 p-1"
                                            title="取消">
                                            <font-awesome-icon :icon="['fas', 'times']" />
                                        </button>
                                    </div>
                                    <!-- 标签名称 (非编辑模式时显示) -->
                                    <span v-else class="font-medium text-gray-900 dark:text-gray-100">{{ tag.name
                                        }}</span>
                                </td>
                                <td class="td-cell text-gray-600 dark:text-gray-400">{{ tag.slug }}</td>
                                <td class="td-cell text-center text-gray-800 dark:text-gray-200">{{ tag.pages_count }}
                                </td>
                                <td class="td-cell text-right pr-6">
                                    <div class="flex justify-end space-x-3">
                                        <!-- 编辑按钮 (需要权限且非编辑状态) -->
                                        <button v-if="$page.props.auth.user.permissions.includes('wiki.manage_tags')"
                                            @click="editTag(tag)" class="btn-link text-xs"
                                            :disabled="editingTag !== null">
                                            <font-awesome-icon :icon="['fas', 'edit']" />
                                        </button>
                                        <!-- 删除按钮 (需要权限) -->
                                        <button v-if="$page.props.auth.user.permissions.includes('wiki.manage_tags')"
                                            @click="confirmDelete(tag)"
                                            class="btn-link text-xs text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300">
                                            <font-awesome-icon :icon="['fas', 'trash']" />
                                        </button>
                                        <!-- 无操作权限提示 -->
                                        <span v-else class="text-xs text-gray-400 italic">无操作权限</span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 删除确认模态框 -->
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
        <!-- 闪现消息组件 -->
        <FlashMessage ref="flashMessage" />
    </MainLayout>
</template>

<script setup>
import { ref, reactive, nextTick } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayouts/MainLayout.vue';
import Modal from '@/Components/Modal/Modal.vue';
import FlashMessage from '@/Components/Other/FlashMessage.vue';
import InputError from '@/Components/Other/InputError.vue';
import { adminNavigationLinks } from '@/config/navigationConfig';

// 管理员导航链接配置
const navigationLinks = adminNavigationLinks;
// 闪现消息组件的引用
const flashMessage = ref(null);

// 定义组件接收的属性
const props = defineProps({
    tags: {
        type: Array,
        required: true
    },
    errors: Object // 后端验证错误
});

// 添加新标签表单的显示状态
const showAddTagForm = ref(false);
// 新标签的数据和处理状态
const newTag = reactive({
    name: '',
    processing: false,
    error: null
});

// 当前正在编辑的标签
const editingTag = ref(null);
// 编辑标签输入框的引用
const editInput = ref(null);

// 删除确认模态框的显示状态
const showDeleteConfirm = ref(false);
// 待删除的标签对象
const tagToDelete = ref(null);

// --- 方法 ---

/**
 * 添加新标签
 */
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
            // 如果错误不是关于name字段的，则显示通用错误消息
            if (flashMessage.value && newTag.error !== errors.name) {
                flashMessage.value.addMessage('error', newTag.error);
            }
        },
        onFinish: () => {
            newTag.processing = false;
        }
    });
};

/**
 * 取消添加标签
 */
const cancelAddTag = () => {
    showAddTagForm.value = false;
    newTag.name = '';
    newTag.error = null;
};

/**
 * 进入编辑标签模式
 * @param {Object} tag - 要编辑的标签对象
 */
const editTag = (tag) => {
    // 如果添加标签表单已打开，则关闭
    if (showAddTagForm.value) cancelAddTag();
    // 如果有其他标签正在编辑，则取消其编辑状态
    if (editingTag.value) cancelEdit();

    // 设置当前编辑的标签
    editingTag.value = { id: tag.id, name: tag.name };
    // 等待DOM更新后聚焦输入框
    nextTick(() => {
        if (editInput.value) {
            editInput.value.focus();
        }
    });
};

/**
 * 更新标签
 */
const updateTag = () => {
    if (!editingTag.value) return; // 如果没有标签在编辑，则退出

    router.put(route('wiki.tags.update', editingTag.value.id), { name: editingTag.value.name }, {
        preserveScroll: true,
        onSuccess: () => {
            if (flashMessage.value) flashMessage.value.addMessage('success', '标签更新成功！');
            cancelEdit(); // 更新成功后取消编辑状态
        },
        onError: (errors) => {
            const errorMsg = errors.name || '更新标签失败。';
            if (flashMessage.value) {
                flashMessage.value.addMessage('error', errorMsg);
            } else {
                alert(errorMsg); // 备用错误提示
            }
        }
    });
};

/**
 * 取消编辑标签
 */
const cancelEdit = () => {
    editingTag.value = null;
};

/**
 * 显示删除确认模态框
 * @param {Object} tag - 要删除的标签对象
 */
const confirmDelete = (tag) => {
    tagToDelete.value = tag;
    showDeleteConfirm.value = true;
};

/**
 * 取消删除操作
 */
const cancelDelete = () => {
    showDeleteConfirm.value = false;
    tagToDelete.value = null;
};

/**
 * 执行删除标签操作
 */
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
                    alert(errorMsg); // 备用错误提示
                }
            },
            onFinish: () => {
                cancelDelete(); // 完成操作后关闭模态框
            }
        });
    }
};
</script>

<style scoped>
/* 表格头部单元格样式 */
.th-cell {
    @apply px-4 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wider;
}

/* 表格数据单元格样式 */
.td-cell {
    @apply px-4 py-4 text-sm;
}

/* 输入框通用样式 */
.input-field {
    @apply w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 dark:placeholder-gray-400 text-sm;
}

/* 主要按钮样式 */
.btn-primary {
    @apply px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed;
}

/* 次要按钮样式 */
.btn-secondary {
    @apply px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition dark:bg-gray-600 dark:text-gray-200 dark:hover:bg-gray-500 text-sm font-medium;
}

/* 链接按钮样式 */
.btn-link {
    @apply text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 hover:underline transition disabled:opacity-50 disabled:cursor-not-allowed;
}

/* 添加表单的淡入淡出过渡效果 */
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s ease, max-height 0.3s ease, padding 0.3s ease, margin 0.3s ease;
    max-height: 200px;
    /* 过渡时的最大高度，确保内容展开 */
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