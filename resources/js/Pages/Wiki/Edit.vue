<template>
    <MainLayout
        :navigationLinks="[{ href: '/wiki', label: '游戏维基' }, { href: '#', label: '游戏历史&名人墙' }, { href: '#', label: '自制专区' }, { href: '#', label: '攻略专区' }, { href: '#', label: '论坛' }]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white/80 backdrop-blur-sm shadow-lg rounded-lg overflow-hidden">
                <div class="p-6">
                    <h2 class="text-2xl font-semibold text-gray-900 mb-6">编辑页面</h2>

                    <!-- 添加当前编辑者信息显示 -->
                    <div v-if="currentEditors.length > 0"
                        class="mb-4 p-3 bg-yellow-50 border border-yellow-200 rounded-md">
                        <p class="text-yellow-700">
                            <span class="font-medium">注意：</span>
                            {{ currentEditors.join('、') }} 也在编辑此页面。请注意协调修改内容，避免冲突。
                        </p>
                    </div>

                    <WikiPageForm :page="page" :categories="categories" @content-changed="trackChanges" />

                    <!-- 冲突警告对话框 -->
                    <Modal :show="showConflictWarning" @close="acknowledgeWarning">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-red-600 mb-4">内容冲突警告</h3>
                            <p class="mb-4">
                                在您编辑期间，另一用户已更新了此页面内容。继续提交将覆盖他们的更改。
                            </p>
                            <div class="mt-5 flex justify-end gap-4">
                                <button @click="viewDiff"
                                    class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600">
                                    查看差异
                                </button>
                                <button @click="forceSubmit"
                                    class="px-4 py-2 bg-red-500 text-white rounded-md hover:bg-red-600">
                                    仍然提交
                                </button>
                                <button @click="acknowledgeWarning"
                                    class="px-4 py-2 text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200">
                                    返回编辑
                                </button>
                            </div>
                        </div>
                    </Modal>
                </div>
            </div>
        </div>
    </MainLayout>
</template>

<script setup>
import MainLayout from '@/Layouts/MainLayouts/MainLayout.vue';
import WikiPageForm from '@/Components/Wiki/WikiPageForm.vue';
import Modal from '@/Components/Modal/Modal.vue';  // 假设你有一个Modal组件
import { onMounted, onBeforeUnmount, ref, watch } from "vue";
import { router, usePage } from '@inertiajs/vue3';

const props = defineProps({
    page: {
        type: Object,
        required: true
    },
    categories: {
        type: Array,
        required: true
    }
});

// 状态变量
const originalContent = ref(props.page.content);
const modifiedContent = ref(props.page.content);
const lastCheckTimestamp = ref(props.page.updated_at || '');
const showConflictWarning = ref(false);
const currentEditors = ref([]);
const editorCheckInterval = ref(null);

// 跟踪内容变化
const trackChanges = (newContent) => {
    modifiedContent.value = newContent;
};

// 检查页面是否已被他人修改
const checkForUpdates = async () => {
    try {
        // 从页面 props 获取 CSRF 令牌 (注意：这里使用 'csrf' 而不是 'csrf_token')
        const { csrf } = usePage().props;

        const response = await fetch(`/api/wiki/${props.page.id}/status?last_check=${encodeURIComponent(lastCheckTimestamp.value)}`, {
            method: 'GET',
            headers: {
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
            credentials: 'same-origin'  // 发送 cookies
        });

        if (!response.ok) {
            throw new Error(`HTTP错误: ${response.status}`);
        }

        const data = await response.json();

        if (data.hasBeenModified) {
            // 显示冲突警告
            showConflictWarning.value = true;
        }

        // 更新其他编辑者列表
        currentEditors.value = data.currentEditors || [];
    } catch (error) {
        console.error('检查页面更新状态时出错:', error);
    }
};

// 添加页面更新前的检查钩子
const beforeUpdate = (event) => {
    // 在表单提交时检查冲突
    if (event.target.method === 'post' || event.target.method === 'put') {
        event.preventDefault();
        checkForUpdates().then(() => {
            if (!showConflictWarning.value) {
                // 如果没有冲突，提交表单
                event.target.submit();
            }
            // 如果有冲突，显示警告并阻止提交
        });
    }
};

// 告知服务器用户正在编辑
const notifyEditing = async () => {
    try {
        const { csrf } = usePage().props;

        const response = await fetch(`/api/wiki/${props.page.id}/editing`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
            credentials: 'same-origin'
        });

        if (!response.ok) {
            throw new Error(`HTTP错误: ${response.status}`);
        }
    } catch (error) {
        console.error('通知编辑状态时出错:', error);
    }
};

// 告知服务器用户停止编辑
const notifyStoppedEditing = async () => {
    try {
        const { csrf } = usePage().props;

        const response = await fetch(`/api/wiki/${props.page.id}/stopped-editing`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
            credentials: 'same-origin'
        });

        if (!response.ok) {
            throw new Error(`HTTP错误: ${response.status}`);
        }
    } catch (error) {
        console.error('通知停止编辑时出错:', error);
    }
};

// 查看差异
const viewDiff = () => {
    // 实现查看差异的逻辑，可能是打开一个新页面展示两个版本之间的差异
    router.get(`/wiki/${props.page.id}/compare-live`);
};

// 强制提交
const forceSubmit = () => {
    showConflictWarning.value = false;
    // 添加一个标志表示用户已确认覆盖
    router.put(`/wiki/${props.page.id}`, {
        title: props.page.title,
        content: modifiedContent.value,
        categories: props.page.categories,
        force_update: true
    });
};

// 确认警告
const acknowledgeWarning = () => {
    showConflictWarning.value = false;
};

onMounted(() => {
    // 初始化时通知服务器用户开始编辑
    notifyEditing();

    // 定期通知服务器用户仍在编辑，并检查其他编辑者
    editorCheckInterval.value = setInterval(() => {
        notifyEditing();
        checkForUpdates();
    }, 60000); // 每分钟检查一次

    // 添加表单提交前的检查
    document.addEventListener('submit', beforeUpdate);

    // 页面可见性变化时更新编辑状态
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') {
            notifyEditing();
            checkForUpdates();
        }
    });
});

onBeforeUnmount(() => {
    // 组件卸载时清理
    if (editorCheckInterval.value) {
        clearInterval(editorCheckInterval.value);
    }

    // 通知服务器用户停止编辑
    notifyStoppedEditing();

    // 移除事件监听器
    document.removeEventListener('submit', beforeUpdate);
});
</script>