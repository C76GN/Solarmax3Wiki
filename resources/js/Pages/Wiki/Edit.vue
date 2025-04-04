<template>
    <MainLayout
        :navigationLinks="[{ href: '/wiki', label: '游戏维基' }, { href: '#', label: '游戏历史&名人墙' }, { href: '#', label: '自制专区' }, { href: '#', label: '攻略专区' }, { href: '#', label: '论坛' }]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white/80 backdrop-blur-sm shadow-lg rounded-lg overflow-hidden">
                <div class="p-6">
                    <h2 class="text-2xl font-semibold text-gray-900 mb-6">编辑页面</h2>

                    <!-- 锁定状态显示 -->
                    <div v-if="pageLocked" class="mb-6 p-4 bg-yellow-50 border-l-4 border-yellow-500 rounded">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z"
                                        clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-yellow-800">页面已锁定</h3>
                                <div class="mt-1 text-sm text-yellow-700">
                                    <p>由 {{ lockInfo.locked_by }} 于 {{ formatDate(lockInfo.locked_at) }} 锁定</p>
                                    <p>原因: {{ lockInfo.reason }}</p>
                                    <p>锁定将于 {{ formatDate(lockInfo.expires_at) }} 过期</p>
                                </div>
                                <div v-if="lockInfo.can_unlock" class="mt-3">
                                    <button @click="unlockPage"
                                        class="px-3 py-1 text-sm font-medium text-yellow-700 border border-yellow-700 rounded hover:bg-yellow-50">
                                        解除锁定
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 当前编辑者提示 -->
                    <div v-if="currentEditors.length > 0 && !pageLocked"
                        class="mb-6 p-4 bg-blue-50 border-l-4 border-blue-500 rounded">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2h2a1 1 0 100-2H9z"
                                        clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-blue-800">协作编辑提示</h3>
                                <div class="mt-1 text-sm text-blue-700">
                                    <p>{{ currentEditors.join('、') }} 也在编辑此页面。请注意协调修改内容，避免冲突。</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 编辑冲突警告 -->
                    <div v-if="showConflictWarning" class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 rounded">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                    fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 001.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                        clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-red-800">编辑冲突警告</h3>
                                <div class="mt-1 text-sm text-red-700">
                                    <p>在您编辑期间，另一用户已更新了此页面内容。继续提交将覆盖他们的更改。</p>
                                </div>
                                <div class="mt-3 flex">
                                    <button @click="viewDiff"
                                        class="mr-3 px-3 py-1 text-sm font-medium text-red-700 border border-red-700 rounded hover:bg-red-50">
                                        查看差异
                                    </button>
                                    <button @click="lockPageForConflict"
                                        class="px-3 py-1 text-sm font-medium text-white bg-red-600 rounded hover:bg-red-700">
                                        锁定页面解决冲突
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 主要表单 -->
                    <form @submit.prevent="submit" v-if="!pageLocked">
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700">页面标题</label>
                            <input type="text" v-model="form.title"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                :class="{ 'border-red-500': form.errors.title }">
                            <p v-if="form.errors.title" class="mt-1 text-sm text-red-600">{{ form.errors.title }}</p>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">页面分类</label>
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                                <div v-for="category in categories" :key="category.id"
                                    class="relative flex items-start">
                                    <div class="flex h-5 items-center">
                                        <input type="checkbox" :value="category.id" v-model="form.categories"
                                            class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    </div>
                                    <div class="ml-3 text-sm">
                                        <label class="font-medium text-gray-700">{{ category.name }}</label>
                                        <p class="text-gray-500">{{ category.description }}</p>
                                    </div>
                                </div>
                            </div>
                            <p v-if="form.errors.categories" class="mt-1 text-sm text-red-600">{{ form.errors.categories
                                }}</p>
                        </div>

                        <div class="mb-4">
                            <div class="flex justify-between items-center mb-2">
                                <label class="block text-sm font-medium text-gray-700">页面内容</label>

                                <!-- 协作工具栏 -->
                                <div class="flex space-x-2">
                                    <button type="button" @click="toggleDiscussionPanel"
                                        class="px-3 py-1 text-sm text-gray-600 border border-gray-300 rounded hover:bg-gray-50">
                                        <span class="flex items-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1"
                                                viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd"
                                                    d="M18 10c0 3.866-3.582 7-8 7a8.841 8.841 0 01-4.083-.98L2 17l1.338-3.123C2.493 12.767 2 11.434 2 10c0-3.866 3.582-7 8-7s8 3.134 8 7zM7 9H5v2h2V9zm8 0h-2v2h2V9zM9 9h2v2H9V9z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                            讨论
                                        </span>
                                    </button>
                                    <button type="button" @click="lockPage"
                                        class="px-3 py-1 text-sm text-gray-600 border border-gray-300 rounded hover:bg-gray-50">
                                        <span class="flex items-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1"
                                                viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd"
                                                    d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                            锁定页面
                                        </span>
                                    </button>
                                </div>
                            </div>

                            <div ref="editorContainer">
                                <WikiEditor v-model="form.content" :autoSaveKey="autoSaveKey" :pageId="page.id"
                                    @auto-save="handleAutoSave" @content-changed="updateContent"
                                    @conflict-detected="onConflictDetected" @view-diff="viewDiff"
                                    @force-update="setForceUpdate" ref="wikiEditor" />
                            </div>
                            <p v-if="form.errors.content" class="mt-1 text-sm text-red-600">{{ form.errors.content }}
                            </p>

                            <div class="mt-4">
                                <label class="block text-sm font-medium text-gray-700">修改说明</label>
                                <input type="text" v-model="form.comment"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="简要描述你的修改（可选）">
                            </div>
                        </div>

                        <div class="flex justify-end gap-4">
                            <Link :href="route('wiki.index')"
                                class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 transition-all duration-300">
                            取消
                            </Link>
                            <button type="submit" :disabled="form.processing || submitting || pageLocked"
                                class="inline-flex items-center px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 transition-all duration-300 disabled:opacity-50">
                                更新页面
                            </button>
                        </div>
                    </form>

                    <!-- 锁定页面时显示只读内容 -->
                    <div v-else>
                        <h3 class="text-lg font-medium text-gray-900 mb-4">{{ page.title }}</h3>
                        <div class="prose prose-lg max-w-none border p-4 rounded bg-gray-50" v-html="page.content">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 讨论面板 -->
        <div v-if="showDiscussionPanel" class="fixed bottom-4 right-4 w-80 h-96 z-40 shadow-xl">
            <WikiDiscussionPanel :pageId="page.id" @close="showDiscussionPanel = false" />
        </div>

        <!-- 锁定页面对话框 -->
        <Modal :show="showLockModal" @close="showLockModal = false">
            <div class="p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">锁定页面</h2>
                <p class="mb-4 text-gray-700">
                    锁定页面将阻止其他用户编辑，直到您解除锁定或锁定过期。请提供锁定原因：
                </p>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">锁定原因</label>
                    <input type="text" v-model="lockReason"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500"
                        placeholder="例如：需要解决编辑冲突" />
                </div>
                <div class="flex justify-end">
                    <button @click="showLockModal = false"
                        class="mr-3 px-4 py-2 text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200">
                        取消
                    </button>
                    <button @click="confirmLockPage"
                        class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600">
                        确认锁定
                    </button>
                </div>
            </div>
        </Modal>
    </MainLayout>
</template>

<script setup>
import MainLayout from '@/Layouts/MainLayouts/MainLayout.vue';
import WikiEditor from '@/Components/Editor/WikiEditor.vue';
import WikiDiscussionPanel from '@/Components/Wiki/WikiDiscussionPanel.vue';
import Modal from '@/Components/Modal/Modal.vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import { ref, watch, onMounted, onBeforeUnmount } from 'vue';
import axios from 'axios';

const props = defineProps({
    page: {
        type: Object,
        required: true
    },
    categories: {
        type: Array,
        required: true
    },
    canEdit: {
        type: Boolean,
        default: false
    }
});

// 表单数据
const form = useForm({
    title: props.page?.title || '',
    content: props.page?.content || '',
    categories: props.page?.categories || [],
    last_check: props.page?.updated_at || '',
    comment: '',
    force_update: false
});

// 组件引用
const wikiEditor = ref(null);
const editorContainer = ref(null);

// 状态变量
const submitting = ref(false);
const autoSaveKey = ref(`wiki_edit_${props.page.id}`);
const currentEditors = ref([]);
const showConflictWarning = ref(false);
const showDiscussionPanel = ref(false);
const showLockModal = ref(false);
const lockReason = ref('');
const pageLocked = ref(false);
const lockInfo = ref({});

// 更新内容
const updateContent = (content) => {
    form.content = content;
};

// 自动保存处理
const handleAutoSave = (data) => {
    // 可以在这里添加服务器端自动保存逻辑
};

// 冲突检测
const onConflictDetected = (detected) => {
    showConflictWarning.value = detected;
};

// 提交表单
const submit = () => {
    if (pageLocked.value) {
        alert('页面已被锁定，无法提交更改');
        return;
    }

    submitting.value = true;
    form.last_check = props.page?.updated_at || '';

    form.put(route('wiki.update', props.page.id), {
        onSuccess: () => {
            localStorage.removeItem(autoSaveKey.value);
            submitting.value = false;
            notifyStoppedEditing();
        },
        onError: (errors) => {
            submitting.value = false;
            if (errors.conflict) {
                showConflictWarning.value = true;
            }
        }
    });
};

// 强制更新
const setForceUpdate = () => {
    form.force_update = true;
    submit();
};

// 查看差异
const viewDiff = (content) => {
    // 跳转到差异对比页面
    router.visit(route('wiki.compare-live', props.page.id), {
        method: 'get',
        data: { content: form.content },
        preserveState: true
    });
};

// 切换讨论面板
const toggleDiscussionPanel = () => {
    showDiscussionPanel.value = !showDiscussionPanel.value;
};

// 锁定页面
const lockPage = () => {
    showLockModal.value = true;
};

// 解锁页面
const unlockPage = async () => {
    try {
        const response = await axios.post(route('wiki.unlock'), {
            page_id: props.page.id
        });

        if (response.data.success) {
            pageLocked.value = false;
            checkPageLock(); // 刷新锁定状态
        }
    } catch (error) {
        console.error('解锁页面失败:', error);
        alert('解锁页面失败，请重试');
    }
};

// 确认锁定页面
const confirmLockPage = async () => {
    if (!lockReason.value.trim()) {
        alert('请提供锁定原因');
        return;
    }

    try {
        const response = await axios.post(route('wiki.lock'), {
            page_id: props.page.id,
            reason: lockReason.value
        });

        if (response.data.success) {
            showLockModal.value = false;
            checkPageLock(); // 刷新锁定状态
        }
    } catch (error) {
        console.error('锁定页面失败:', error);
        alert('锁定页面失败，请重试');
    }
};

// 由于编辑冲突锁定页面
const lockPageForConflict = () => {
    lockReason.value = '需要解决编辑冲突';
    confirmLockPage();
};

// 检查页面锁定状态
const checkPageLock = async () => {
    try {
        const response = await axios.get(`/wiki/${props.page.id}/lock-status`);
        pageLocked.value = response.data.locked;

        if (pageLocked.value) {
            lockInfo.value = response.data;
        }
    } catch (error) {
        console.error('检查页面锁定状态失败:', error);
    }
};

// 检查编辑状态
const checkEditingStatus = async () => {
    try {
        const response = await axios.get(`/api/wiki/${props.page.id}/status?last_check=${encodeURIComponent(props.page.updated_at)}`);

        if (response.data.currentEditors) {
            currentEditors.value = response.data.currentEditors;
        }

        if (response.data.hasBeenModified) {
            showConflictWarning.value = true;
        }
    } catch (error) {
        console.error('检查编辑状态失败:', error);
    }
};

// 通知正在编辑
const notifyEditing = async () => {
    if (pageLocked.value) return; // 如果页面已锁定，不发送编辑通知

    try {
        await axios.post(`/api/wiki/${props.page.id}/editing`);
    } catch (error) {
        console.error('通知编辑状态失败:', error);
    }
};

// 通知停止编辑
const notifyStoppedEditing = async () => {
    try {
        await axios.post(`/api/wiki/${props.page.id}/stopped-editing`);
    } catch (error) {
        console.error('通知停止编辑失败:', error);
    }
};

// 格式化日期
const formatDate = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleString('zh-CN');
};

onMounted(() => {
    // 检查页面锁定状态
    checkPageLock();
    // 检查编辑状态
    checkEditingStatus();

    // 设置定期检查
    const checkInterval = setInterval(() => {
        checkPageLock();
        if (!pageLocked.value) {
            notifyEditing();
            checkEditingStatus();
        }
    }, 30000); // 每30秒检查一次

    // 监听键盘快捷键
    const handleKeyDown = (e) => {
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            e.preventDefault();
            if (!pageLocked.value) {
                submit();
            }
        }
    };
    window.addEventListener('keydown', handleKeyDown);

    // 组件卸载时清理
    onBeforeUnmount(() => {
        clearInterval(checkInterval);
        notifyStoppedEditing();
        window.removeEventListener('keydown', handleKeyDown);
    });

    // 页面关闭前通知停止编辑
    window.addEventListener('beforeunload', notifyStoppedEditing);
    onBeforeUnmount(() => {
        window.removeEventListener('beforeunload', notifyStoppedEditing);
    });
});

// 监听锁定状态变化
watch(pageLocked, (locked) => {
    if (locked) {
        // 如果页面被锁定，通知停止编辑
        notifyStoppedEditing();
    }
});
</script>