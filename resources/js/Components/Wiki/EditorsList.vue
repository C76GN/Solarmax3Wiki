<template>
    <div class="mb-4">
        <!-- 修改提示框背景、边框和文字颜色 -->
        <div v-if="editors.length > 0" class="p-3 rounded-lg border" :class="alertClass">
            <div class="flex items-start">
                <font-awesome-icon :icon="['fas', 'users']" class="mr-2 mt-1 flex-shrink-0" :class="iconClass" />
                <div class="w-full">
                    <!-- 修改标题文字颜色 -->
                    <div class="flex justify-between items-center mb-1">
                        <p class="font-medium text-sm md:text-base text-gray-200">当前编辑者 ({{ editors.length }}):</p>
                        <button v-if="showChatButton" @click="toggleChat"
                            class="text-xs px-2 py-1 rounded bg-blue-600 text-white hover:bg-blue-500 transition relative">
                            <font-awesome-icon :icon="['fas', 'comments']" class="mr-1" />
                            实时讨论
                            <!-- 未读消息样式不变 -->
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
                    <!-- 修改编辑者药丸样式 -->
                    <div class="flex flex-wrap items-center mt-1 gap-1">
                        <div v-for="editor in editors" :key="editor.id"
                            class="inline-flex items-center px-2 py-0.5 rounded-full bg-gray-700 border border-gray-600 text-gray-300 text-xs"
                            :title="`${editor.name} - ${isEditorActive(editor) ? '活跃中' : '可能已离开 (' + formatTime(editor.last_active) + ')'}`">
                            <span v-if="editor.avatar" class="mr-1">
                                <img :src="editor.avatar" class="w-4 h-4 rounded-full" :alt="editor.name" />
                            </span>
                            <!-- 头像背景调整 -->
                            <span v-else
                                class="mr-1 inline-flex items-center justify-center w-4 h-4 rounded-full bg-gray-500 text-gray-100 text-[10px] font-semibold">
                                {{ getInitials(editor.name) }}
                            </span>
                            <span class="font-medium truncate max-w-[100px]">{{ editor.name }}</span>
                            <!-- 状态点样式不变 -->
                            <span v-if="isEditorActive(editor)"
                                class="ml-1.5 w-1.5 h-1.5 bg-green-500 rounded-full flex-shrink-0" title="活跃中"></span>
                            <span v-else class="ml-1.5 w-1.5 h-1.5 bg-gray-400 rounded-full flex-shrink-0"
                                :title="`最后活跃: ${formatTime(editor.last_active)}`"></span>
                        </div>
                    </div>
                    <!-- 修改警告信息样式 -->
                    <p v-if="editors.length > 1" class="text-xs mt-2 p-2 rounded border"
                        :class="warningTextClass + ' ' + warningBgClass">
                        <font-awesome-icon :icon="['fas', 'exclamation-triangle']" class="mr-1" />
                        <strong>注意:</strong> 多人同时编辑极易导致内容冲突！强烈建议通过下方的 **实时讨论** 功能进行协调，避免同时保存。
                    </p>
                </div>
            </div>
        </div>
        <!-- 聊天窗口样式调整 -->
        <Transition enter-active-class="transition ease-out duration-300" enter-from-class="opacity-0 translate-y-2"
            enter-to-class="opacity-100 translate-y-0" leave-active-class="transition ease-in duration-200"
            leave-from-class="opacity-100 translate-y-0" leave-to-class="opacity-0 translate-y-2">
            <div v-if="showChat" class="bg-gray-800 border border-gray-700 rounded-lg shadow-md p-3 mb-4">
                <!-- 聊天窗口头部样式 -->
                <div class="flex justify-between items-center mb-2 border-b border-gray-600 pb-2">
                    <h3 class="font-medium text-base text-gray-200">实时讨论</h3>
                    <button @click="toggleChat" class="text-gray-400 hover:text-gray-200">
                        <font-awesome-icon :icon="['fas', 'times']" />
                    </button>
                </div>
                <!-- 消息容器样式 -->
                <div ref="messagesContainer"
                    class="chat-messages h-48 overflow-y-auto border border-gray-600 p-2 rounded mb-2 bg-gray-900 text-sm">
                    <div v-if="messages.length === 0"
                        class="flex items-center justify-center h-full text-gray-500 text-xs italic">
                        暂无消息，开始讨论吧！
                    </div>
                    <div v-else class="space-y-3">
                        <div v-for="msg in messages" :key="msg.id" class="flex message-item"
                            :class="{ 'justify-end': isCurrentUser(msg.user_id), 'new-message': isNewMessage(msg.id) }">
                            <div class="flex items-start max-w-[80%]"
                                :class="{ 'flex-row-reverse': isCurrentUser(msg.user_id) }">
                                <!-- 调整头像背景 -->
                                <div class="flex-shrink-0 rounded-full w-6 h-6 flex items-center justify-center text-xs font-semibold mx-2"
                                    :class="getAvatarBgClass(msg.user_id)">
                                    {{ getInitials(msg.user_name) }}
                                </div>
                                <div class="flex flex-col" :class="{ 'items-end': isCurrentUser(msg.user_id) }">
                                    <!-- 调整名字和时间颜色 -->
                                    <div class="text-xs text-gray-400 mb-0.5">
                                        <span class="font-medium mr-1 text-gray-300">{{ msg.user_name }}</span> {{
                                            formatTime(msg.timestamp) }}
                                    </div>
                                    <!-- 调整他人消息气泡背景和文字颜色 -->
                                    <div class="p-2 rounded-lg text-sm break-words" :class="[isCurrentUser(msg.user_id)
                                        ? 'bg-blue-600 text-white rounded-br-none' // 自己消息不变
                                        : 'bg-gray-600 text-gray-200 rounded-bl-none' // 他人消息深色
                                    ]">
                                        {{ msg.message }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- 输入框和发送按钮样式 -->
                <div class="flex">
                    <input v-model="newMessage" @keyup.enter="sendMessage" type="text" placeholder="发送消息 (Enter 发送)..."
                        :disabled="sendingMessage"
                        class="flex-grow px-3 py-1.5 border border-gray-600 rounded-l-md focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 text-sm bg-gray-700 text-gray-200 placeholder-gray-500 disabled:bg-gray-600 disabled:text-gray-400" />
                    <button @click="sendMessage"
                        class="w-[70px] px-4 py-1.5 bg-blue-600 text-white rounded-r-md hover:bg-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition flex items-center justify-center"
                        :disabled="!newMessage.trim() || sendingMessage">
                        <font-awesome-icon v-if="!sendingMessage" :icon="['fas', 'paper-plane']" />
                        <font-awesome-icon v-else :icon="['fas', 'spinner']" spin />
                    </button>
                </div>
                <!-- 错误信息颜色 -->
                <div v-if="sendMessageError" class="text-red-400 text-xs mt-1">{{ sendMessageError }}</div>
            </div>
        </Transition>
    </div>
</template>

<script setup>
// Script 部分保持不变
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
const showChatButton = ref(true); // 控制聊天按钮是否显示
const sendingMessage = ref(false); // 发送中状态
const sendMessageError = ref(''); // 发送错误信息

let echoChannel = null;
let heartbeatInterval = null;
const messagesContainer = ref(null); // Ref for scrolling
const newMessagesQueue = ref([]); // 用于追踪新消息ID
let highlightTimeout = null; // 用于清除高亮

// --- Helper Functions ---
const scrollToMessageBottom = () => {
    nextTick(() => {
        const container = messagesContainer.value;
        if (container) {
            const isScrolledToBottom = container.scrollHeight - container.scrollTop <= container.clientHeight + 50;
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
    const chineseMatch = nameTrimmed.match(/[\u4E00-\u9FA5]/);
    if (chineseMatch) {
        return chineseMatch[0];
    }
    const parts = nameTrimmed.split(/\s+/);
    if (parts.length > 0 && parts[0]) {
        return parts[0].charAt(0).toUpperCase();
    }
    return nameTrimmed.charAt(0).toUpperCase() || '?';
};

// 调整头像背景色（深色模式）
const userColors = {};
const bgColors = [
    'bg-blue-900/50 text-blue-300', 'bg-green-900/50 text-green-300',
    'bg-yellow-900/50 text-yellow-300', 'bg-purple-900/50 text-purple-300',
    'bg-pink-900/50 text-pink-300', 'bg-indigo-900/50 text-indigo-300',
    'bg-red-900/50 text-red-300', 'bg-teal-900/50 text-teal-300'
];
let colorIndex = 0;
const getAvatarBgClass = (userId) => {
    if (!userId) return bgColors[0]; // Default for null/0 userId
    if (!userColors[userId]) {
        userColors[userId] = bgColors[colorIndex % bgColors.length];
        colorIndex++;
    }
    return userColors[userId];
};


// 调整警告/提示框样式（深色模式）
const alertClass = computed(() => {
    return editors.value.length > 1
        ? 'bg-red-900/50 border-l-4 border-red-500' // 深红背景
        : 'bg-yellow-900/50 border-l-4 border-yellow-500'; // 深黄背景
});

const iconClass = computed(() => {
    return editors.value.length > 1 ? 'text-red-400' : 'text-yellow-400';
});

const warningTextClass = computed(() => {
    return editors.value.length > 1 ? 'text-red-300 border-red-700' : 'text-yellow-300 border-yellow-700';
});

const warningBgClass = computed(() => {
    return editors.value.length > 1 ? 'bg-red-900/40' : 'bg-yellow-900/40';
})


const isCurrentUser = (userId) => {
    return userId === page.props.auth.user?.id;
};

const isEditorActive = (editor) => {
    const now = Math.floor(Date.now() / 1000);
    return editor.last_active && (now - editor.last_active < 70); // 70秒内算活跃
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
    await registerAsEditor(); // 初始注册
    heartbeatInterval = setInterval(registerAsEditor, 45000); // 保持心跳
    setupRealTimeListener(); // 设置监听器
});

onBeforeUnmount(() => {
    unregisterAsEditor(); // 组件卸载时注销
    if (heartbeatInterval) {
        clearInterval(heartbeatInterval);
    }
    // 清理 Echo 监听器
    if (echoChannel) {
        echoChannel.stopListening('.editors.updated');
        echoChannel.stopListening('.discussion.message');
        echoChannel.stopListening('.page.version.updated');
        try {
            window.Echo.leave(`wiki.page.${props.pageId}`);
        } catch (e) {
            console.error("Error leaving Echo channel:", e);
        }
        echoChannel = null;
    }
    // 清理高亮计时器
    if (highlightTimeout) {
        clearTimeout(highlightTimeout);
    }
});

// --- API Calls ---
const loadEditors = async () => {
    /* 保持不变 */
    if (!props.pageId) return;
    try {
        const response = await axios.get(route('wiki.editors', { page: props.pageId }));
        editors.value = response.data.editors || [];
    } catch (error) {
        console.error(`EditorsList: Failed to load editors for page ${props.pageId}:`, error.response?.data || error.message);
    }
};

const loadMessages = async () => {
    /* 保持不变 */
    try {
        const response = await axios.get(route('wiki.discussion', { page: props.pageId }));
        messages.value = response.data.messages || [];
        scrollToMessageBottom();
    } catch (error) {
        console.error(`EditorsList: Failed to load messages for page ${props.pageId}:`, error.response?.data || error.message);
        messages.value = [];
    }
};

const registerAsEditor = async () => {
    /* 保持不变 */
    if (!props.pageId || !page.props.auth.user) return; // 未登录用户不注册
    try {
        await axios.post(route('wiki.editors.register', { page: props.pageId }));
    } catch (error) {
        console.error(`EditorsList: Failed to register/heartbeat for page ${props.pageId}:`, error.response?.data || error.message);
    }
};

const unregisterAsEditor = async () => {
    /* 保持不变 */
    if (!props.pageId || !page.props.auth.user) return;
    try {
        // 使用 navigator.sendBeacon 尝试在页面关闭时发送请求
        if (navigator.sendBeacon) {
            const url = route('wiki.editors.unregister', { page: props.pageId });
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const formData = new FormData();
            if (csrfToken) formData.append('_token', csrfToken);
            navigator.sendBeacon(url, formData);
        } else {
            // Fallback to axios if sendBeacon is not available
            await axios.post(route('wiki.editors.unregister', { page: props.pageId }));
        }
    } catch (error) {
        // 避免在页面卸载时显示错误，只在控制台记录
        console.error(`EditorsList: Failed to unregister from page ${props.pageId}:`, error.response?.data || error.message);
    }
};


const sendMessage = async () => {
    /* 保持不变 */
    if (!newMessage.value.trim() || sendingMessage.value) return;

    sendingMessage.value = true;
    sendMessageError.value = ''; // 清除之前的错误

    try {
        const response = await axios.post(route('wiki.discussion.send', { page: props.pageId }), {
            message: newMessage.value
        });
        // 成功时不再清除输入框，交给 Echo 监听处理
    } catch (error) {
        console.error(`EditorsList: Failed to send message for page ${props.pageId}:`, error.response?.data || error.message);
        sendMessageError.value = `发送失败: ${error.response?.data?.message || '网络错误，请稍后重试'}`;
        // 发送失败时需要手动结束 loading 状态
        sendingMessage.value = false;
    }
    // finally {
    //     sendingMessage.value = false; // 不论成功失败都结束 loading，但在成功时由 Echo 监听处理更好
    // }
};


// --- Real-time Listener ---
const isNewMessage = (messageId) => {
    return newMessagesQueue.value.includes(messageId);
};

const setupRealTimeListener = () => {
    const channelName = `wiki.page.${props.pageId}`;

    if (!window.Echo) {
        console.error("Echo is not initialized!");
        return;
    }

    try {
        echoChannel = window.Echo.channel(channelName);

        // 监听编辑者更新
        echoChannel.listen('.editors.updated', (data) => {
            console.log('Received editors.updated:', data);
            editors.value = data.editors || [];
        });

        // 监听新消息
        echoChannel.listen('.discussion.message', (data) => {
            console.log('Received discussion.message:', data);
            if (data.message) {
                const newMessageData = data.message;
                // 防止重复添加（虽然理论上 Echo 不会重复发）
                if (!messages.value.some(msg => msg.id === newMessageData.id)) {
                    messages.value.push(newMessageData);

                    // 如果是当前用户发送的消息，清空输入框并结束 loading
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

                    scrollToMessageBottom(); // 滚动到底部
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
            console.log('Received page.version.updated:', data);
            // 触发一个全局事件或使用状态管理通知 Edit.vue
            const event = new CustomEvent('page-version-updated', { detail: { pageId: props.pageId, newVersionId: data.newVersionId } });
            window.dispatchEvent(event);
        });

        // 添加错误处理
        echoChannel.error((error) => {
            console.error(`Echo channel error on ${channelName}:`, error);
            // 这里可以添加一些UI提示，告知用户实时功能可能中断
        });

        console.log(`Listening on channel: ${channelName}`);

    } catch (error) {
        console.error(`EditorsList: Error setting up listener for channel ${channelName}:`, error);
    }

    // 可选：监听连接状态
    window.Echo.connector.pusher.connection.bind('state_change', (states) => {
        console.log("Echo connection state changed:", states.current);
    });
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
        background-color: #67e8f9;
    }

    /* Light cyan */
    100% {
        background-color: inherit;
    }

    /* Revert to original (set by class) */
}

/* 为自己发送的消息设置不同的高亮颜色 */
.justify-end .message-item.new-message>div>div:last-child>div:last-child {
    animation: highlight-new-message-user 1.5s ease-out;
}

@keyframes highlight-new-message-user {
    0% {
        background-color: #22d3ee;
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

/* 确保长名字能被截断 */
.truncate {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.max-w-\[100px\] {
    max-width: 100px;
}
</style>