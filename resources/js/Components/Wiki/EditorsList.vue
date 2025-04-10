<template>
    <div class="mb-4">
        <!-- 编辑者列表 -->
        <div v-if="editors.length > 0" class="p-3 rounded-lg border" :class="alertClass">
            <div class="flex items-start">
                <font-awesome-icon :icon="['fas', 'users']" class="mr-2 mt-1 flex-shrink-0" :class="iconClass" />
                <div class="w-full">
                    <div class="flex justify-between items-center">
                        <p class="font-medium text-sm md:text-base">当前编辑者 ({{ editors.length }}):</p>
                        <button v-if="showChatButton" @click="toggleChat"
                            class="text-xs px-2 py-1 rounded bg-blue-500 text-white hover:bg-blue-600 transition">
                            <!-- Changed icon -->
                            <font-awesome-icon :icon="['fas', 'comments']" class="mr-1" />
                            实时讨论
                            <!-- Improved unread badge -->
                            <span v-if="unreadMessages > 0" class="ml-1 inline-block bg-red-500 text-white text-xs rounded-full px-1.5 py-0.5 leading-none">
                                {{ unreadMessages }}
                            </span>
                        </button>
                    </div>
                    <!-- Improved editor list display -->
                    <div class="flex flex-wrap items-center mt-1 gap-1">
                        <div v-for="editor in editors" :key="editor.id"
                            class="inline-flex items-center px-2 py-0.5 rounded-full bg-gray-100 border text-gray-700 text-xs">
                            <span v-if="editor.avatar" class="mr-1">
                                <img :src="editor.avatar" class="w-4 h-4 rounded-full" :alt="editor.name" />
                            </span>
                            <span class="font-medium">{{ editor.name }}</span>
                            <!-- Improved activity indicator -->
                            <span v-if="isEditorActive(editor)" class="ml-1.5 w-1.5 h-1.5 bg-green-500 rounded-full" title="活跃中"></span>
                            <span v-else class="ml-1.5 w-1.5 h-1.5 bg-gray-400 rounded-full" title="可能已离开"></span>
                        </div>
                    </div>
                     <!-- Warning message -->
                    <p v-if="editors.length > 1" class="text-xs mt-2" :class="warningTextClass">
                        <font-awesome-icon :icon="['fas', 'exclamation-triangle']" class="mr-1" />
                        <strong>注意:</strong> 多人同时编辑可能导致内容冲突！建议通过下方实时讨论进行协调。
                    </p>
                </div>
            </div>
        </div>

        <!-- 实时讨论区 -->
        <Transition
            enter-active-class="transition ease-out duration-300"
            enter-from-class="opacity-0 translate-y-2"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition ease-in duration-200"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 translate-y-2"
        >
            <div v-if="showChat" class="bg-white border rounded-lg shadow-md p-3 mb-4">
                <div class="flex justify-between items-center mb-2 border-b pb-2">
                    <h3 class="font-medium text-base">实时讨论</h3>
                    <button @click="toggleChat" class="text-gray-500 hover:text-gray-700">
                        <font-awesome-icon :icon="['fas', 'times']" />
                    </button>
                </div>
                <!-- Chat Messages Area -->
                <div ref="messagesContainer" class="chat-messages h-48 overflow-y-auto border p-2 rounded mb-2 bg-gray-50 text-sm">
                    <div v-if="messages.length === 0" class="flex items-center justify-center h-full text-gray-500 text-xs italic">
                        暂无消息，开始讨论吧！
                    </div>
                    <!-- Message List -->
                    <div v-else class="space-y-3">
                         <div v-for="msg in messages" :key="msg.id" class="flex" :class="{'justify-end': isCurrentUser(msg.user_id)}">
                            <div class="flex items-start max-w-[80%]" :class="{'flex-row-reverse': isCurrentUser(msg.user_id)}">
                                <!-- Avatar Placeholder -->
                                <div class="flex-shrink-0 bg-blue-100 text-blue-800 rounded-full w-6 h-6 flex items-center justify-center text-xs font-semibold mx-2">
                                    {{ getInitials(msg.user_name) }}
                                </div>
                                <!-- Message Bubble -->
                                <div>
                                    <div class="text-xs text-gray-500 mb-0.5" :class="{'text-right': isCurrentUser(msg.user_id)}">
                                        <span class="font-medium mr-1">{{ msg.user_name }}</span> {{ formatTime(msg.timestamp) }}
                                    </div>
                                    <div class="p-2 rounded-lg" :class="[isCurrentUser(msg.user_id) ? 'bg-blue-100' : 'bg-gray-100']">
                                        {{ msg.message }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                 <!-- Input Area -->
                <div class="flex">
                    <input v-model="newMessage" @keyup.enter="sendMessage" type="text" placeholder="发送消息 (Enter 发送)..."
                        class="flex-grow px-3 py-1.5 border border-gray-300 rounded-l-md focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 text-sm" />
                    <button @click="sendMessage" class="px-4 py-1.5 bg-blue-500 text-white rounded-r-md hover:bg-blue-600 disabled:opacity-50 transition"
                        :disabled="!newMessage.trim() || sendingMessage">
                        <font-awesome-icon v-if="!sendingMessage" :icon="['fas', 'paper-plane']" />
                        <font-awesome-icon v-else :icon="['fas', 'spinner']" spin />
                    </button>
                </div>
                <!-- Error Message -->
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
const showChatButton = ref(true); // Control chat button visibility
const sendingMessage = ref(false); // Sending status
const sendMessageError = ref(''); // Sending error message

let echoChannel = null; // Combined channel
let heartbeatInterval = null;
const messagesContainer = ref(null); // Ref for chat messages container

// --- Helper Functions ---
const scrollToMessageBottom = (selector = '.chat-messages') => {
    nextTick(() => {
        const container = messagesContainer.value; // Use the ref
        if (container) {
            container.scrollTop = container.scrollHeight;
        }
    });
};

const getInitials = (name) => {
    if (!name) return '?';
    const nameTrimmed = name.trim();
    if (!nameTrimmed) return '?';
    // Handle potential Chinese characters (often no space)
    if (/[\u4E00-\u9FA5]/.test(nameTrimmed)) {
        return nameTrimmed.substring(0, 1); // Take first char for Chinese names
    }
    const parts = nameTrimmed.split(/\s+/); // Split by any whitespace
    if (parts.length > 1) {
        return (parts[0].charAt(0) + (parts[parts.length - 1].charAt(0) || '')).toUpperCase();
    }
    return nameTrimmed.charAt(0).toUpperCase();
};


// --- Computed Properties ---
const alertClass = computed(() => {
    return editors.value.length > 1
        ? 'bg-red-50 border-l-4 border-red-400' // More subtle red
        : 'bg-yellow-50 border-l-4 border-yellow-400'; // More subtle yellow
});

const iconClass = computed(() => {
    return editors.value.length > 1 ? 'text-red-500' : 'text-yellow-500';
});

const warningTextClass = computed(() => {
    return editors.value.length > 1 ? 'text-red-600' : 'text-yellow-600';
});

const isCurrentUser = (userId) => {
    return userId === page.props.auth.user?.id;
};

const isEditorActive = (editor) => {
    const now = Math.floor(Date.now() / 1000);
    // Consider active if last active within the last 60 seconds (adjust as needed)
    return editor.last_active && (now - editor.last_active < 60);
};

const formatTime = (timestamp) => {
    if (!timestamp) return '';
    try {
        return formatDistanceToNow(new Date(timestamp * 1000), {
            addSuffix: true,
            locale: zhCN // Use Chinese locale
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
    // console.log(`EditorsList mounted for page ID: ${props.pageId}`);
    await loadEditors();
    await loadMessages();
    await registerAsEditor();
    heartbeatInterval = setInterval(registerAsEditor, 30000); // Send heartbeat
    setupRealTimeListener();
    // Optional: Periodic refresh in case websocket misses an event (less critical with heartbeats)
    // setInterval(loadEditors, 60000);
});

onBeforeUnmount(() => {
    // console.log(`EditorsList unmounting for page ID: ${props.pageId}`);
    unregisterAsEditor(); // Attempt to unregister on component leave/destroy
    if (heartbeatInterval) {
        clearInterval(heartbeatInterval);
    }
    if (echoChannel) {
        // console.log(`EditorsList: Leaving channel wiki.page.${props.pageId}`);
        echoChannel.stopListening('.editors.updated');
        echoChannel.stopListening('.discussion.message');
        try {
            window.Echo.leave(`wiki.page.${props.pageId}`);
        } catch(e) {
            console.error("Error leaving Echo channel:", e);
        }
        echoChannel = null;
    }
});

// --- API Calls ---
const loadEditors = async () => {
    try {
        // console.log(`EditorsList: Loading editors for page ${props.pageId}`);
        const response = await axios.get(route('wiki.editors', { page: props.pageId }));
        editors.value = response.data.editors || [];
        // console.log(`EditorsList: Editors loaded successfully:`, editors.value);
    } catch (error) {
        console.error(`EditorsList: Failed to load editors for page ${props.pageId}:`, error.response?.data || error.message);
        editors.value = []; // Reset on error
    }
};

const loadMessages = async () => {
    try {
        // console.log(`EditorsList: Loading messages for page ${props.pageId}`);
        const response = await axios.get(route('wiki.discussion', { page: props.pageId }));
        messages.value = response.data.messages || [];
        // console.log(`EditorsList: Messages loaded successfully:`, messages.value);
        scrollToMessageBottom();
    } catch (error) {
        console.error(`EditorsList: Failed to load messages for page ${props.pageId}:`, error.response?.data || error.message);
        messages.value = []; // Reset on error
    }
};

const registerAsEditor = async () => {
     // Don't send heartbeat if user is not logged in
     if (!page.props.auth.user) return;
    try {
        // console.log(`EditorsList: Registering/heartbeat for page ${props.pageId}`);
        await axios.post(route('wiki.editors.register', { page: props.pageId }));
        // console.log(`EditorsList: Register/heartbeat successful for page ${props.pageId}`);
    } catch (error) {
        console.error(`EditorsList: Failed to register/heartbeat for page ${props.pageId}:`, error.response?.data || error.message);
        // Handle specific errors? e.g., 401 Unauthorized
    }
};

const unregisterAsEditor = async () => {
     // Don't send if user is not logged in
     if (!page.props.auth.user) return;
    try {
        // console.log(`EditorsList: Unregistering for page ${props.pageId}`);
        // Use sendBeacon for reliability on page close, needs CSRF if backend requires
        if (navigator.sendBeacon) {
             const url = route('wiki.editors.unregister', { page: props.pageId });
             // sendBeacon often sends as 'text/plain' by default, check backend needs
             // For complex data or specific content types, FormData or Blob might be needed.
             // Simple POST often doesn't work directly like axios.
             // Sending CSRF with sendBeacon is tricky, might need backend adjustment or alternative.
             // For simplicity here, we assume backend handles beacon without CSRF for this specific action IF NEEDED.
             // It's generally better to rely on heartbeats timing out for presence.
             navigator.sendBeacon(url); // Might need adjustment based on backend
             // console.log(`EditorsList: Sent unregister beacon for page ${props.pageId}`);
        } else {
            await axios.post(route('wiki.editors.unregister', { page: props.pageId }));
            // console.log(`EditorsList: Unregister successful via axios for page ${props.pageId}`);
        }
    } catch (error) {
        // Don't worry too much about errors here, server timeout will handle presence
        console.warn(`EditorsList: Failed to unregister for page ${props.pageId} (might be expected on close):`, error.response?.data || error.message);
    }
};


const sendMessage = async () => {
    if (!newMessage.value.trim() || sendingMessage.value) return;
    sendingMessage.value = true;
    sendMessageError.value = '';
    // console.log(`EditorsList: Sending message for page ${props.pageId}`);
    try {
        await axios.post(route('wiki.discussion.send', { page: props.pageId }), {
            message: newMessage.value
        });
        newMessage.value = '';
        // console.log(`EditorsList: Message sent successfully for page ${props.pageId}`);
    } catch (error) {
        console.error(`EditorsList: Failed to send message for page ${props.pageId}:`, error.response?.data || error.message);
        sendMessageError.value = `发送失败: ${error.response?.data?.message || '网络错误'}`;
    } finally {
        sendingMessage.value = false;
    }
};

// --- Real-time Listener Setup ---
const setupRealTimeListener = () => {
    const channelName = `wiki.page.${props.pageId}`;
    // console.log(`EditorsList: Setting up listener for channel: ${channelName}`);

    if (!window.Echo) {
        console.error("Echo is not initialized!");
        return;
    }

    try {
        // Join a public channel
        echoChannel = window.Echo.channel(channelName);

        // Listen for editor updates
        echoChannel.listen('.editors.updated', (data) => {
            // console.log('EditorsList: Received editors.updated:', data);
            // Directly update the ref's value
            editors.value = data.editors || [];
        });

        // Listen for new discussion messages
        echoChannel.listen('.discussion.message', (data) => {
            // console.log('EditorsList: Received discussion.message:', data);
            if (data.message) {
                 // Check for duplicate messages by ID before pushing
                if (!messages.value.some(msg => msg.id === data.message.id)) {
                    messages.value.push(data.message);
                    scrollToMessageBottom();
                    if (!showChat.value && !isCurrentUser(data.message.user_id)) {
                        unreadMessages.value++;
                    }
                 } else {
                     // console.log("EditorsList: Duplicate message received, ignoring:", data.message.id);
                 }
            } else {
                console.warn("EditorsList: Received discussion.message event missing 'message' data:", data);
            }
        });

        // console.log(`EditorsList: Successfully listening to channel ${channelName}`);

    } catch (error) {
        console.error(`EditorsList: Error setting up listener for channel ${channelName}:`, error);
    }

    // Optional: Debug Echo connection state
    window.Echo.connector.pusher.connection.bind('state_change', (states) => {
        // console.log(`EditorsList: Echo connection state changed: ${states.previous} -> ${states.current}`);
    });
     window.Echo.connector.pusher.connection.bind('error', (err) => {
        console.error('EditorsList: Echo connection error:', err);
    });
};


// --- UI Interaction ---
const toggleChat = () => {
    showChat.value = !showChat.value;
    if (showChat.value) {
        unreadMessages.value = 0; // Reset unread count when opening chat
        scrollToMessageBottom(); // Scroll to bottom when opening
    }
};

// --- Watchers ---
// Watch messages array changes to scroll down
watch(messages, () => {
    if (showChat.value) {
        scrollToMessageBottom();
    }
}, { deep: true }); // Use deep watch if message objects might change internally

</script>

<style scoped>
/* Add smooth scrolling behavior */
.chat-messages {
    scroll-behavior: smooth;
}
/* Optional: Add slight spacing between message bubbles */
.chat-messages > .space-y-3 > div:not(:last-child) {
  /* margin-bottom: 0.5rem; */ /* Already handled by space-y-3 */
}
</style>