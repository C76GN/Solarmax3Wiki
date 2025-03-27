import { ref, onMounted, onBeforeUnmount } from 'vue';
import { router } from '@inertiajs/vue3';

export function useEditConflict(pageId, initialContent) {
    const showConflictWarning = ref(false);
    const currentEditors = ref([]);
    const lastCheckTimestamp = ref('');
    const editorCheckInterval = ref(null);
    const modifiedContent = ref(initialContent);

    // 通知服务器用户正在编辑
    const notifyEditing = async () => {
        try {
            const response = await fetch(`/api/wiki/${pageId}/editing`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
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

    // 通知服务器用户停止编辑
    const notifyStoppedEditing = async () => {
        try {
            const response = await fetch(`/api/wiki/${pageId}/stopped-editing`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
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

    // 检查页面是否已被他人修改
    const checkForUpdates = async () => {
        try {
            const response = await fetch(`/api/wiki/${pageId}/status?last_check=${encodeURIComponent(lastCheckTimestamp.value)}`, {
                method: 'GET',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                credentials: 'same-origin'
            });

            if (!response.ok) {
                throw new Error(`HTTP错误: ${response.status}`);
            }

            const data = await response.json();

            if (data.hasBeenModified) {
                // 显示冲突警告
                showConflictWarning.value = true;
            }

            // 更新最后检查时间
            if (data.lastModified) {
                lastCheckTimestamp.value = data.lastModified;
            }

            // 更新其他编辑者列表
            currentEditors.value = data.currentEditors || [];
        } catch (error) {
            console.error('检查页面更新状态时出错:', error);
        }
    };

    // 查看差异
    const viewDiff = (content) => {
        router.get(`/wiki/${pageId}/compare-live`, {
            content: content || modifiedContent.value
        });
    };

    // 强制提交
    const forceSubmit = (formData) => {
        formData.force_update = true;
        router.put(`/wiki/${pageId}`, formData);
    };

    // 初始化
    const initEditTracking = () => {
        // 初始化时保存当前时间戳
        lastCheckTimestamp.value = new Date().toISOString();

        // 通知服务器用户开始编辑
        notifyEditing();

        // 定期通知服务器用户仍在编辑，并检查其他编辑者
        editorCheckInterval.value = setInterval(() => {
            notifyEditing();
            checkForUpdates();
        }, 60000); // 每分钟检查一次

        // 页面可见性变化时更新编辑状态
        document.addEventListener('visibilitychange', handleVisibilityChange);
    };

    // 处理页面可见性变化
    const handleVisibilityChange = () => {
        if (document.visibilityState === 'visible') {
            notifyEditing();
            checkForUpdates();
        }
    };

    // 清理资源
    const cleanup = () => {
        // 清除定时器
        if (editorCheckInterval.value) {
            clearInterval(editorCheckInterval.value);
        }

        // 通知服务器用户停止编辑
        notifyStoppedEditing();

        // 移除事件监听器
        document.removeEventListener('visibilitychange', handleVisibilityChange);
    };

    // 更新内容
    const updateContent = (content) => {
        modifiedContent.value = content;
    };

    onMounted(() => {
        initEditTracking();
    });

    onBeforeUnmount(() => {
        cleanup();
    });

    return {
        showConflictWarning,
        currentEditors,
        modifiedContent,
        updateContent,
        viewDiff,
        forceSubmit,
        notifyEditing,
        notifyStoppedEditing,
        checkForUpdates,
    };
}