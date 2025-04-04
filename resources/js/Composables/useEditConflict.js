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

    // 检查页面是否有更新
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
                showConflictWarning.value = true;
            }

            if (data.lastModified) {
                lastCheckTimestamp.value = data.lastModified;
            }

            currentEditors.value = data.currentEditors || [];
        } catch (error) {
            console.error('检查页面更新状态时出错:', error);
        }
    };

    // 查看冲突内容差异
    const viewDiff = (content) => {
        router.get(`/wiki/${pageId}/compare-live`, {
            content: content || modifiedContent.value
        });
    };

    // 强制提交（覆盖其他人的修改）
    const forceSubmit = (formData) => {
        formData.force_update = true;
        router.put(`/wiki/${pageId}`, formData);
    };

    // 初始化编辑跟踪
    const initEditTracking = () => {
        lastCheckTimestamp.value = new Date().toISOString();
        notifyEditing();

        // 设置定时器，定期检查更新和通知编辑状态
        editorCheckInterval.value = setInterval(() => {
            notifyEditing();
            checkForUpdates();
        }, 60000); // 每分钟检查一次

        // 监听页面可见性变化
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
        if (editorCheckInterval.value) {
            clearInterval(editorCheckInterval.value);
        }
        notifyStoppedEditing();
        document.removeEventListener('visibilitychange', handleVisibilityChange);
    };

    // 更新当前内容
    const updateContent = (content) => {
        modifiedContent.value = content;
    };

    // 生命周期钩子
    onMounted(() => {
        if (pageId) {
            initEditTracking();
        }
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