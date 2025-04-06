<template>
    <div v-if="editors.length > 0" class="editors-list mb-4">
        <div class="p-3 bg-yellow-100 border-l-4 border-yellow-500 rounded-lg">
            <div class="flex items-center">
                <font-awesome-icon :icon="['fas', 'users']" class="mr-2 text-yellow-600" />
                <div>
                    <p class="font-medium">
                        当前编辑者 ({{ editors.length }}):
                        <span v-for="(editor, index) in editors" :key="editor.id" class="inline-flex items-center">
                            {{ editor.name }}{{ index < editors.length - 1 ? ', ' : '' }} </span>
                    </p>
                    <p v-if="editors.length > 1" class="text-sm text-yellow-700 mt-1">
                        <font-awesome-icon :icon="['fas', 'exclamation-triangle']" class="mr-1" />
                        多人编辑可能导致内容冲突，请保持沟通
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';
import axios from 'axios';

const props = defineProps({
    pageId: {
        type: Number,
        required: true
    }
});

const editors = ref([]);

// 初始化Echo监听器
let editorsChannel = null;
let heartbeatInterval = null;

onMounted(async () => {
    // 获取初始编辑者列表
    await loadEditors();

    // 注册自己为编辑者
    await registerAsEditor();

    // 设置定期发送心跳
    heartbeatInterval = setInterval(registerAsEditor, 30000); // 每30秒发送一次心跳

    // 设置实时编辑者更新监听
    setupRealTimeListener();
});

onBeforeUnmount(() => {
    // 注销编辑者状态
    unregisterAsEditor();

    // 清理定时器
    if (heartbeatInterval) {
        clearInterval(heartbeatInterval);
    }

    // 清理监听器
    if (editorsChannel) {
        editorsChannel.stopListening('editors.updated');
    }
});

// 加载编辑者列表
const loadEditors = async () => {
    try {
        const response = await axios.get(route('wiki.editors', props.pageId));
        editors.value = response.data.editors;
    } catch (error) {
        console.error('加载编辑者列表失败:', error);
    }
};

// 注册为编辑者
const registerAsEditor = async () => {
    try {
        await axios.post(route('wiki.editors.register', props.pageId));
    } catch (error) {
        console.error('注册编辑者失败:', error);
    }
};

// 注销编辑者状态
const unregisterAsEditor = async () => {
    try {
        await axios.post(route('wiki.editors.unregister', props.pageId));
    } catch (error) {
        console.error('注销编辑者状态失败:', error);
    }
};

// 设置实时编辑者更新监听
const setupRealTimeListener = () => {
    editorsChannel = window.Echo.channel(`wiki.page.${props.pageId}`);

    editorsChannel.listen('editors.updated', (data) => {
        // 更新编辑者列表
        editors.value = data.editors;
    });
};
</script>