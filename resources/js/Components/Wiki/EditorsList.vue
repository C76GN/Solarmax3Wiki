<template>
    <div class="mb-4">
        <!-- 编辑者列表和警告 -->
        <div v-if="editors.length > 0" class="p-3 rounded-lg border" :class="alertClass">
            <div class="flex items-start">
                <font-awesome-icon :icon="['fas', 'users']" class="mr-2 mt-1 flex-shrink-0" :class="iconClass" />
                <div class="w-full">
                    <div class="flex justify-between items-center mb-1">
                        <p class="font-medium text-sm md:text-base">当前编辑者 ({{ editors.length }}):</p>
                        <!-- 讨论按钮 -->
                        <button v-if="showChatButton" @click="toggleChat"
                            class="text-xs px-2 py-1 rounded bg-blue-500 text-white hover:bg-blue-600 transition relative">
                            <font-awesome-icon :icon="['fas', 'comments']" class="mr-1" />
                            实时讨论
                            <span v-if="unreadMessages > 0 && !showChat" class="absolute -top-1 -right-1 flex h-4 w-4">
                                <span
                                    class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                                <span
                                    class="relative inline-flex rounded-full h-4 w-4 bg-red-500 items-center justify-center text-white text-[10px] leading-none">
                                    {{ unreadMessages > 9 ? '9+' : unreadMessages }}
                                </span>
                            </span>
                            <span v-else-if="unreadMessages > 0 && showChat"
                                class="ml-1 inline-block bg-red-500 text-white text-xs rounded-full px-1.5 py-0.5 leading-none">
                                {{ unreadMessages }}
                            </span>
                        </button>
                    </div>
                    <!-- 编辑者头像/名称列表 -->
                    <div class="flex flex-wrap items-center mt-1 gap-1">
                        <div v-for="editor in editors" :key="editor.id"
                            class="inline-flex items-center px-2 py-0.5 rounded-full bg-gray-100 border text-gray-700 text-xs"
                            :title="`${editor.name} - ${isEditorActive(editor) ? '活跃中' : '可能已离开 (' + formatTime(editor.last_active) + ')'}`">
                            <span v-if="editor.avatar" class="mr-1">
                                <img :src="editor.avatar" class="w-4 h-4 rounded-full" :alt="editor.name" />
                            </span>
                            <span v-else
                                class="mr-1 inline-flex items-center justify-center w-4 h-4 rounded-full bg-gray-300 text-gray-600 text-[10px] font-semibold">
                                {{ getInitials(editor.name) }}
                            </span>
                            <span class="font-medium truncate max-w-[100px]">{{ editor.name }}</span>
                            <span v-if="isEditorActive(editor)"
                                class="ml-1.5 w-1.5 h-1.5 bg-green-500 rounded-full flex-shrink-0" title="活跃中"></span>
                            <span v-else class="ml-1.5 w-1.5 h-1.5 bg-gray-400 rounded-full flex-shrink-0"
                                :title="`最后活跃: ${formatTime(editor.last_active)}`"></span>
                        </div>
                    </div>
                    <!-- 强化警告信息 -->
                    <p v-if="editors.length > 1" class="text-xs mt-2 p-2 rounded border"
                        :class="warningTextClass + ' ' + warningBgClass">
                        <font-awesome-icon :icon="['fas', 'exclamation-triangle']" class="mr-1" />
                        <strong>注意:</strong> 多人同时编辑极易导致内容冲突！强烈建议通过下方的 **实时讨论** 功能进行协调，避免同时保存。
                    </p>
                </div>
            </div>
        </div>

        <!-- 实时讨论区域 -->
        <Transition enter-active-class="transition ease-out duration-300" enter-from-class="opacity-0 translate-y-2"
            enter-to-class="opacity-100 translate-y-0" leave-active-class="transition ease-in duration-200"
            leave-from-class="opacity-100 translate-y-0" leave-to-class="opacity-0 translate-y-2">
            <div v-if="showChat" class="bg-white border rounded-lg shadow-md p-3 mb-4">
                <div class="flex justify-between items-center mb-2 border-b pb-2">
                    <h3 class="font-medium text-base">实时讨论</h3>
                    <button @click="toggleChat" class="text-gray-500 hover:text-gray-700">
                        <font-awesome-icon :icon="['fas', 'times']" />
                    </button>
                </div>
                <div ref="messagesContainer"
                    class="chat-messages h-48 overflow-y-auto border p-2 rounded mb-2 bg-gray-50 text-sm">
                    <div v-if="messages.length === 0"
                        class="flex items-center justify-center h-full text-gray-500 text-xs italic">
                        暂无消息，开始讨论吧！
                    </div>
                    <div v-else class="space-y-3">
                        <!-- 修改了消息展示结构和样式 -->
                        <div v-for="msg in messages" :key="msg.id" class="flex message-item"
                            :class="{ 'justify-end': isCurrentUser(msg.user_id), 'new-message': isNewMessage(msg.id) }">
                            <div class="flex items-start max-w-[80%]"
                                :class="{ 'flex-row-reverse': isCurrentUser(msg.user_id) }">
                                <div class="flex-shrink-0 rounded-full w-6 h-6 flex items-center justify-center text-xs font-semibold mx-2"
                                    :class="getAvatarBgClass(msg.user_id)">
                                    {{ getInitials(msg.user_name) }}
                                </div>
                                <div class="flex flex-col" :class="{ 'items-end': isCurrentUser(msg.user_id) }">
                                    <div class="text-xs text-gray-500 mb-0.5">
                                        <span class="font-medium mr-1">{{ msg.user_name }}</span> {{
                                        formatTime(msg.timestamp) }}
                                    </div>
                                    <div class="p-2 rounded-lg text-sm break-words"
                                        :class="[isCurrentUser(msg.user_id) ? 'bg-blue-500 text-white rounded-br-none' : 'bg-gray-200 text-gray-800 rounded-bl-none']">
                                        {{ msg.message }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex">
                    <input v-model="newMessage" @keyup.enter="sendMessage" type="text" placeholder="发送消息 (Enter 发送)..."
                        :disabled="sendingMessage"
                        class="flex-grow px-3 py-1.5 border border-gray-300 rounded-l-md focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 text-sm disabled:bg-gray-100" />
                    <button @click="sendMessage"
                        class="w-[70px] px-4 py-1.5 bg-blue-500 text-white rounded-r-md hover:bg-blue-600 disabled:opacity-50 disabled:cursor-not-allowed transition flex items-center justify-center"
                        :disabled="!newMessage.trim() || sendingMessage">
                        <!-- 添加了加载状态 -->
                        <font-awesome-icon v-if="!sendingMessage" :icon="['fas', 'paper-plane']" />
                        <font-awesome-icon v-else :icon="['fas', 'spinner']" spin />
                    </button>
                </div>
                <!-- 添加了发送错误提示 -->
                <div v-if="sendMessageError" class="text-red-500 text-xs mt-1">{{ sendMessageError }}</div>
            </div>
        </Transition>
    </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, computed, watch, nextTick } from 'vue';
import axios from 'axios';
import { formatDistanceToNow } from 'date-fns';
import { zhCN } from 'date-fns/locale';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
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
const sendingMessage = ref(false); // 新增：发送中状态
const sendMessageError = ref(''); // 新增：发送错误信息
let echoChannel = null;
let heartbeatInterval = null;
const messagesContainer = ref(null);
const newMessagesQueue = ref([]); // 新增：用于追踪新消息ID
let highlightTimeout = null; // 新增：用于清除高亮

// --- Helper Functions ---
const scrollToMessageBottom = () => {
    nextTick(() => {
        const container = messagesContainer.value;
        if (container) {
            // 只有当滚动条不在顶部附近时才滚动到底部，给用户查看历史消息的机会
            // 或者当是自己发送的消息时强制滚动到底部
            const isScrolledToBottom = container.scrollHeight - container.scrollTop <= container.clientHeight + 50; // Allow some tolerance
            if (isScrolledToBottom || sendingMessage.value) { // Add condition for self-sent message if needed
                container.scrollTop = container.scrollHeight;
            }
        }
    });
};

const getInitials = (name) => {
    if (!name) return '?';
    const nameTrimmed = name.trim();
    if (!nameTrimmed) return '?';
    // 优先取中文首字
    const chineseMatch = nameTrimmed.match(/[\u4E00-\u9FA5]/);
    if (chineseMatch) {
        return chineseMatch[0];
    }
    // 英文取首字母
    const parts = nameTrimmed.split(/\s+/);
    if (parts.length > 0 && parts[0]) {
        return parts[0].charAt(0).toUpperCase();
    }
    return nameTrimmed.charAt(0).toUpperCase() || '?';
};

// 新增：为不同用户分配头像背景色
const userColors = {};
const bgColors = [
    'bg-blue-200 text-blue-800', 'bg-green-200 text-green-800',
    'bg-yellow-200 text-yellow-800', 'bg-purple-200 text-purple-800',
    'bg-pink-200 text-pink-800', 'bg-indigo-200 text-indigo-800',
    'bg-red-200 text-red-800', 'bg-teal-200 text-teal-800'
];
let colorIndex = 0;

const getAvatarBgClass = (userId) => {
    if (!userColors[userId]) {
        userColors[userId] = bgColors[colorIndex % bgColors.length];
        colorIndex++;
    }
    return userColors[userId];
};


// --- Computed Properties ---
// 强化警告样式
const alertClass = computed(() => {
    return editors.value.length > 1
        ? 'bg-red-50 border-l-4 border-red-400'
        : 'bg-yellow-50 border-l-4 border-yellow-400'; // 可以为单人编辑时也加个提示
});

const iconClass = computed(() => {
    return editors.value.length > 1 ? 'text-red-500' : 'text-yellow-500';
});

const warningTextClass = computed(() => {
    return editors.value.length > 1 ? 'text-red-700 border-red-200' : 'text-yellow-700 border-yellow-200';
});
const warningBgClass = computed(() => {
    return editors.value.length > 1 ? 'bg-red-50' : 'bg-yellow-50';
})


const isCurrentUser = (userId) => {
    return userId === page.props.auth.user?.id;
};

const isEditorActive = (editor) => {
    const now = Math.floor(Date.now() / 1000);
    return editor.last_active && (now - editor.last_active < 70); // 稍微延长判断时间
};

const formatTime = (timestamp) => {
    if (!timestamp) return '';
    try {
        // 将 Unix 时间戳转换为毫秒
        const date = new Date(timestamp * 1000);
        return formatDistanceToNow(date, {
            addSuffix: true,
            locale: zhCN
        });
    } catch (e) {
        console.error("Error formatting time:", e);
        return '时间错误';
    }
};

// --- Lifecycle Hooks ---
onMounted(async () => {
    if (!props.pageId) {
        console.error("EditorsList: Page ID not provided on mount.");
        return;
    }
    await loadEditors();
    await loadMessages();
    await registerAsEditor();
    heartbeatInterval = setInterval(registerAsEditor, 45000); // 心跳间隔调整为45秒
    setupRealTimeListener();
});

onBeforeUnmount(() => {
    unregisterAsEditor();
    if (heartbeatInterval) {
        clearInterval(heartbeatInterval);
    }
    if (echoChannel) {
        echoChannel.stopListening('.editors.updated');
        echoChannel.stopListening('.discussion.message');
        // 增加版本更新事件监听的停止
        echoChannel.stopListening('.page.version.updated');
        try {
            window.Echo.leave(`wiki.page.${props.pageId}`);
        } catch (e) {
            console.error("Error leaving Echo channel:", e);
        }
        echoChannel = null;
    }
    if (highlightTimeout) { // 清除高亮定时器
        clearTimeout(highlightTimeout);
    }
});

// --- API Calls ---
const loadEditors = async () => { /* ... 保持不变 ... */ };
const loadMessages = async () => {
    try {
        const response = await axios.get(route('wiki.discussion', { page: props.pageId }));
        messages.value = response.data.messages || [];
        scrollToMessageBottom();
    } catch (error) {
        console.error(`EditorsList: Failed to load messages for page ${props.pageId}:`, error.response?.data || error.message);
        messages.value = [];
    }
};
const registerAsEditor = async () => { /* ... 保持不变 ... */ };
const unregisterAsEditor = async () => { /* ... 保持不变 ... */ };

// 修改：增加发送状态和错误处理
const sendMessage = async () => {
    if (!newMessage.value.trim() || sendingMessage.value) return;
    sendingMessage.value = true;
    sendMessageError.value = '';
    try {
        const response = await axios.post(route('wiki.discussion.send', { page: props.pageId }), {
            message: newMessage.value
        });
        // 假设后端成功后会通过WebSocket广播，前端在这里不清空输入框，等待广播消息
        // newMessage.value = ''; // 清空输入框移到接收到自己消息时
    } catch (error) {
        console.error(`EditorsList: Failed to send message for page ${props.pageId}:`, error.response?.data || error.message);
        sendMessageError.value = `发送失败: ${error.response?.data?.message || '网络错误，请稍后重试'}`;
    } finally {
        // 不论成功失败，最终都要结束loading状态
        // sendingMessage.value = false; // 发送状态由接收到回显消息或错误时结束
    }
};


// 新增：判断是否为新消息
const isNewMessage = (messageId) => {
    return newMessagesQueue.value.includes(messageId);
};

// --- Real-time Listener Setup ---
const setupRealTimeListener = () => {
    const channelName = `wiki.page.${props.pageId}`;
    if (!window.Echo) {
        console.error("Echo is not initialized!");
        return;
    }
    try {
        echoChannel = window.Echo.channel(channelName);
        echoChannel.listen('.editors.updated', (data) => {
            editors.value = data.editors || [];
        });

        echoChannel.listen('.discussion.message', (data) => {
            if (data.message) {
                const newMessageData = data.message;
                if (!messages.value.some(msg => msg.id === newMessageData.id)) {
                    messages.value.push(newMessageData);

                    // 如果是当前用户发送的消息，清空输入框并结束loading
                    if (isCurrentUser(newMessageData.user_id)) {
                        newMessage.value = '';
                        sendingMessage.value = false;
                        sendMessageError.value = ''; // 清除错误
                    }

                    // 如果聊天窗口未打开且不是自己发的消息，增加未读计数
                    if (!showChat.value && !isCurrentUser(newMessageData.user_id)) {
                        unreadMessages.value++;
                    }

                    // 添加到新消息队列并设置高亮
                    newMessagesQueue.value.push(newMessageData.id);
                    if (highlightTimeout) clearTimeout(highlightTimeout);
                    highlightTimeout = setTimeout(() => {
                        newMessagesQueue.value = newMessagesQueue.value.filter(id => id !== newMessageData.id);
                    }, 1500); // 高亮持续1.5秒


                    scrollToMessageBottom();
                }
            } else {
                console.warn("EditorsList: Received discussion.message event missing 'message' data:", data);
                // 如果发送失败，也需要结束loading状态
                if (sendingMessage.value) {
                    sendingMessage.value = false;
                    // sendMessageError 已在 catch 中设置
                }
            }
        });

        // 监听页面版本更新事件 (需要后端配合广播)
        echoChannel.listen('.page.version.updated', (data) => {
            // 触发一个全局事件或使用状态管理通知 Edit.vue
            const event = new CustomEvent('page-version-updated', { detail: { pageId: props.pageId, newVersionId: data.newVersionId } });
            window.dispatchEvent(event);
        });


    } catch (error) {
        console.error(`EditorsList: Error setting up listener for channel ${channelName}:`, error);
    }
    /* ... Echo connection state/error binding ... */
};

// --- UI Interaction ---
const toggleChat = () => {
    showChat.value = !showChat.value;
    if (showChat.value) {
        unreadMessages.value = 0; // 打开时清除未读
        scrollToMessageBottom(); // 打开时滚动到底部
    }
};

// 监听消息变化，如果聊天窗口打开，自动滚动
watch(messages, () => {
    if (showChat.value) {
        // scrollToMessageBottom(); // 现在由接收消息时滚动
    }
}, { deep: true });

</script>

<style scoped>
.chat-messages {
    scroll-behavior: smooth;
}

/* 新消息高亮效果 */
.message-item.new-message>div>div:last-child>div:last-child {
    /* Target the message bubble */
    animation: highlight-new-message 1.5s ease-out;
}

@keyframes highlight-new-message {
    0% {
        background-color: #a5f3fc;
    }

    /* Light cyan */
    100% {
        background-color: inherit;
    }

    /* Revert to original (set by class) */
}

.justify-end .message-item.new-message>div>div:last-child>div:last-child {
    animation: highlight-new-message-user 1.5s ease-out;
}

@keyframes highlight-new-message-user {
    0% {
        background-color: #67e8f9;
    }

    /* Slightly darker cyan for user */
    100% {
        background-color: inherit;
    }
}

/* 使头像背景更柔和 */
.rounded-full[class*="bg-"] {
    opacity: 0.85;
}

/* 截断长用户名 */
.truncate {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.max-w-\[100px\] {
    max-width: 100px;
}
</style>