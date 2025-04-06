<template>
    <div class="wiki-discussion border rounded-lg bg-gray-50">
        <div class="discussion-header p-3 bg-gray-100 border-b flex justify-between items-center">
            <h3 class="font-medium">页面讨论</h3>
            <button @click="toggleDiscussion" class="text-gray-500 hover:text-gray-700">
                <font-awesome-icon :icon="['fas', isCollapsed ? 'chevron-down' : 'chevron-up']" />
            </button>
        </div>

        <div v-if="!isCollapsed" class="discussion-content">
            <div class="messages p-3 h-64 overflow-y-auto">
                <div v-if="messages.length === 0" class="text-center text-gray-500 py-4">
                    暂无讨论消息
                </div>
                <div v-for="msg in messages" :key="msg.id" class="message mb-3">
                    <div class="flex items-start">
                        <div
                            class="flex-shrink-0 bg-blue-100 text-blue-800 rounded-full w-8 h-8 flex items-center justify-center">
                            {{ getInitials(msg.user_name) }}
                        </div>
                        <div class="ml-2 flex-grow">
                            <div class="flex justify-between items-center">
                                <span class="font-medium text-sm">{{ msg.user_name }}</span>
                                <span class="text-xs text-gray-500">{{ formatTime(msg.timestamp) }}</span>
                            </div>
                            <div class="text-sm mt-1 break-words">{{ msg.message }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="input-area p-3 border-t">
                <div class="flex">
                    <input v-model="newMessage" @keyup.enter="sendMessage" type="text" placeholder="发送消息..."
                        class="flex-grow px-3 py-2 border rounded-l-lg focus:outline-none focus:ring-2 focus:ring-blue-500" />
                    <button @click="sendMessage" class="px-4 py-2 bg-blue-500 text-white rounded-r-lg hover:bg-blue-600"
                        :disabled="!newMessage.trim()">
                        <font-awesome-icon :icon="['fas', 'paper-plane']" />
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';
import { formatDistance } from 'date-fns';
import axios from 'axios';

const props = defineProps({
    pageId: {
        type: Number,
        required: true
    }
});

const messages = ref([]);
const newMessage = ref('');
const isCollapsed = ref(true);

// 初始化Echo监听器
let messageChannel = null;

onMounted(async () => {
    // 获取初始消息
    await loadMessages();

    // 设置实时消息监听
    setupRealTimeListener();
});

onBeforeUnmount(() => {
    // 清理监听器
    if (messageChannel) {
        messageChannel.stopListening('discussion.message');
    }
});

// 加载消息
const loadMessages = async () => {
    try {
        const response = await axios.get(route('wiki.discussion', props.pageId));
        messages.value = response.data.messages;

        // 如果有消息，自动展开讨论区
        if (messages.value.length > 0) {
            isCollapsed.value = false;
        }
    } catch (error) {
        console.error('加载讨论消息失败:', error);
    }
};

// 发送消息
const sendMessage = async () => {
    if (!newMessage.value.trim()) return;

    try {
        await axios.post(route('wiki.discussion.send', props.pageId), {
            message: newMessage.value
        });

        // 清空输入框
        newMessage.value = '';
    } catch (error) {
        console.error('发送消息失败:', error);
    }
};

// 设置实时消息监听
const setupRealTimeListener = () => {
    messageChannel = window.Echo.channel(`wiki.page.${props.pageId}`);

    messageChannel.listen('discussion.message', (data) => {
        // 添加新消息到列表
        messages.value.push(data.message);

        // 如果讨论区折叠，提示有新消息
        if (isCollapsed.value) {
            // TODO: 添加新消息提示，如闪烁标题等
        }
    });
};

// 切换讨论区折叠状态
const toggleDiscussion = () => {
    isCollapsed.value = !isCollapsed.value;
};

// 格式化时间
const formatTime = (timestamp) => {
    return formatDistance(new Date(timestamp * 1000), new Date(), { addSuffix: true });
};

// 获取用户名首字母
const getInitials = (name) => {
    return name.charAt(0).toUpperCase();
};
</script>