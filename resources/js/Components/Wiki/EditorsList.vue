// 修改 resources/js/Components/Wiki/EditorsList.vue

<template>
    <div v-if="editors.length > 0" class="editors-list mb-4">
        <div class="p-3" :class="alertClass">
            <div class="flex items-center">
                <font-awesome-icon :icon="['fas', 'users']" class="mr-2" :class="iconClass" />
                <div class="w-full">
                    <div class="flex justify-between items-center">
                        <p class="font-medium">当前编辑者 ({{ editors.length }}):</p>
                        <button v-if="showChatButton" @click="toggleChat"
                            class="text-xs px-2 py-1 rounded bg-blue-600 text-white hover:bg-blue-700">
                            实时讨论 {{ unreadMessages > 0 ? `(${unreadMessages})` : '' }}
                        </button>
                    </div>
                    <div class="flex flex-wrap items-center mt-1">
                        <div v-for="(editor, index) in editors" :key="editor.id"
                            class="inline-flex items-center mr-2 mb-1 px-2 py-1 rounded bg-white/50">
                            <span v-if="editor.avatar" class="mr-1">
                                <img :src="editor.avatar" class="w-5 h-5 rounded-full" :alt="editor.name" />
                            </span>
                            <span class="text-sm">{{ editor.name }}</span>
                            <span v-if="isEditorActive(editor)" class="ml-1 w-2 h-2 bg-green-500 rounded-full"></span>
                        </div>
                    </div>
                    <p v-if="editors.length > 1" class="text-sm mt-1" :class="warningTextClass">
                        <font-awesome-icon :icon="['fas', 'exclamation-triangle']" class="mr-1" />
                        <strong>注意:</strong> 多人同时编辑可能导致内容冲突！请与其他编辑者协调以避免修改同一区域。
                    </p>
                </div>
            </div>
        </div>

        <!-- 实时聊天窗口 -->
        <div v-if="showChat" class="bg-white border rounded-lg shadow-lg p-3 mb-4">
            <div class="flex justify-between items-center mb-2">
                <h3 class="font-medium">实时讨论</h3>
                <button @click="toggleChat" class="text-gray-500 hover:text-gray-700">
                    <font-awesome-icon :icon="['fas', 'times']" />
                </button>
            </div>
            <div class="chat-messages h-48 overflow-y-auto border p-2 rounded mb-2">
                <div v-if="messages.length === 0" class="flex items-center justify-center h-full text-gray-500">
                    暂无消息，开始实时讨论吧！
                </div>
                <div v-else>
                    <div v-for="msg in messages" :key="msg.id" class="mb-2">
                        <div :class="[
                            'p-2 rounded-lg max-w-xs inline-block',
                            isCurrentUser(msg.user_id) ? 'bg-blue-100 ml-auto' : 'bg-gray-100'
                        ]">
                            <div class="text-xs font-medium">{{ msg.user_name }}</div>
                            <div class="text-sm mt-1">{{ msg.message }}</div>
                            <div class="text-xs text-gray-500 text-right">{{ formatTime(msg.timestamp) }}</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="flex">
                <input v-model="newMessage" @keyup.enter="sendMessage" type="text" placeholder="发送消息..."
                    class="flex-grow px-2 py-1 border rounded-l focus:outline-none focus:ring-1 focus:ring-blue-500" />
                <button @click="sendMessage" class="px-3 py-1 bg-blue-500 text-white rounded-r hover:bg-blue-600"
                    :disabled="!newMessage.trim()">
                    <font-awesome-icon :icon="['fas', 'paper-plane']" />
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, computed, watch } from 'vue';
import axios from 'axios';
import { formatDistanceToNow } from 'date-fns';
import { zhCN } from 'date-fns/locale';

const props = defineProps({
    pageId: {
        type: Number,
        required: true
    }
});

const editors = ref([]);
const messages = ref([]);
const newMessage = ref('');
const showChat = ref(false);
const unreadMessages = ref(0);
const showChatButton = ref(true);
let editorsChannel = null;
let heartbeatInterval = null;
let messagesContainer = null;

const alertClass = computed(() => {
    return editors.value.length > 1
        ? 'bg-red-100 border-l-4 border-red-500 rounded-lg'
        : 'bg-yellow-100 border-l-4 border-yellow-500 rounded-lg';
});

const iconClass = computed(() => {
    return editors.value.length > 1 ? 'text-red-600' : 'text-yellow-600';
});

const warningTextClass = computed(() => {
    return editors.value.length > 1 ? 'text-red-700 font-bold' : 'text-yellow-700';
});

const isCurrentUser = (userId) => {
    return userId === $page.props.auth.user?.id;
};

const isEditorActive = (editor) => {
    const now = Math.floor(Date.now() / 1000);
    return now - editor.last_active < 60; // 如果60秒内有活动，显示为活跃
};

const formatTime = (timestamp) => {
    return formatDistanceToNow(new Date(timestamp * 1000), {
        addSuffix: true,
        locale: zhCN
    });
};

onMounted(async () => {
    await loadEditors();
    await loadMessages();
    await registerAsEditor();

    heartbeatInterval = setInterval(registerAsEditor, 30000);
    setupRealTimeListener();

    // 定期刷新编辑者列表，确保状态最新
    setInterval(loadEditors, 60000);
});

onBeforeUnmount(() => {
    unregisterAsEditor();

    if (heartbeatInterval) {
        clearInterval(heartbeatInterval);
    }

    if (editorsChannel) {
        editorsChannel.stopListening('editors.updated');
        editorsChannel.stopListening('discussion.message');
    }
});

const loadEditors = async () => {
    try {
        const response = await axios.get(route('wiki.editors', props.pageId));
        editors.value = response.data.editors;
    } catch (error) {
        console.error('加载编辑者列表失败:', error);
    }
};

const loadMessages = async () => {
    try {
        const response = await axios.get(route('wiki.discussion', props.pageId));
        messages.value = response.data.messages;
        scrollToBottom();
    } catch (error) {
        console.error('加载消息失败:', error);
    }
};

const registerAsEditor = async () => {
    try {
        await axios.post(route('wiki.editors.register', props.pageId));
    } catch (error) {
        console.error('注册编辑者失败:', error);
    }
};

const unregisterAsEditor = async () => {
    try {
        await axios.post(route('wiki.editors.unregister', props.pageId));
    } catch (error) {
        console.error('注销编辑者状态失败:', error);
    }
};

const setupRealTimeListener = () => {
    editorsChannel = window.Echo.channel(`wiki.page.${props.pageId}`);

    editorsChannel.listen('editors.updated', (data) => {
        editors.value = data.editors;
    });

    editorsChannel.listen('discussion.message', (data) => {
        messages.value.push(data.message);
        scrollToBottom();

        // 如果聊天窗口未打开，增加未读消息数
        if (!showChat.value && !isCurrentUser(data.message.user_id)) {
            unreadMessages.value++;
        }
    });
};

const toggleChat = () => {
    showChat.value = !showChat.value;

    if (showChat.value) {
        unreadMessages.value = 0;
        scrollToBottom();
    }
};

const sendMessage = async () => {
    if (!newMessage.value.trim()) return;

    try {
        await axios.post(route('wiki.discussion.send', props.pageId), {
            message: newMessage.value
        });

        newMessage.value = '';
    } catch (error) {
        console.error('发送消息失败:', error);
    }
};

const scrollToBottom = () => {
    // 使用nextTick确保DOM已更新
    nextTick(() => {
        const messageContainer = document.querySelector('.chat-messages');
        if (messageContainer) {
            messageContainer.scrollTop = messageContainer.scrollHeight;
        }
    });
};

// 当messages变化时滚动到底部
watch(messages, () => {
    scrollToBottom();
});
</script>