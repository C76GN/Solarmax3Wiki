<template>
    <div class="discussion-panel bg-white rounded-lg shadow overflow-hidden">
        <div class="panel-header bg-gray-50 px-4 py-3 border-b flex justify-between items-center">
            <h3 class="text-lg font-medium text-gray-700">编辑讨论</h3>
            <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd"
                        d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                        clip-rule="evenodd" />
                </svg>
            </button>
        </div>

        <div class="messages-container p-4 h-64 overflow-y-auto" ref="messagesContainer">
            <div v-if="loading" class="flex justify-center items-center h-full">
                <svg class="animate-spin h-6 w-6 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none"
                    viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                    </path>
                </svg>
            </div>

            <div v-else-if="messages.length === 0" class="flex justify-center items-center h-full">
                <p class="text-gray-500">暂无讨论消息</p>
            </div>

            <div v-else class="space-y-4">
                <div v-for="message in sortedMessages" :key="message.id" class="message-item">
                    <div class="flex items-start">
                        <div class="flex-shrink-0 bg-blue-100 rounded-full p-2 text-blue-500">
                            {{ message.user.name.charAt(0).toUpperCase() }}
                        </div>
                        <div class="ml-3 bg-gray-100 rounded-lg p-3 max-w-xs">
                            <div class="flex justify-between items-center">
                                <span class="font-medium text-gray-900">{{ message.user.name }}</span>
                                <span class="text-xs text-gray-500">{{ message.created_at }}</span>
                            </div>
                            <p class="text-sm text-gray-700 mt-1">{{ message.message }}</p>
                            <div v-if="message.editing_section" class="mt-1 text-xs text-blue-600">
                                正在编辑: {{ message.editing_section }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="message-input p-4 border-t">
            <div class="flex">
                <input type="text" v-model="newMessage" @keyup.enter="sendMessage" placeholder="输入讨论内容..."
                    class="flex-1 border border-gray-300 px-3 py-2 rounded-l-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
                <button @click="sendMessage" :disabled="!newMessage.trim()"
                    class="bg-blue-500 text-white px-4 py-2 rounded-r-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50">
                    发送
                </button>
            </div>
            <div v-if="editingSection" class="mt-2 text-xs text-blue-600 flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
                    <path
                        d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                </svg>
                正在编辑: {{ editingSection }}
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch, nextTick } from 'vue';
import axios from 'axios';

const props = defineProps({
    pageId: {
        type: [Number, String],
        required: true
    },
    editingSection: {
        type: String,
        default: null
    }
});

const emit = defineEmits(['close']);

const loading = ref(true);
const messages = ref([]);
const newMessage = ref('');
const messagesContainer = ref(null);
const pollingInterval = ref(null);

// 按时间排序的消息
const sortedMessages = computed(() => {
    return [...messages.value].sort((a, b) => {
        return new Date(a.timestamp) - new Date(b.timestamp);
    });
});

// 加载消息
const loadMessages = async () => {
    try {
        const response = await axios.get(`/wiki/${props.pageId}/discussions`);
        messages.value = response.data.messages;
        loading.value = false;

        // 滚动到最新消息
        await nextTick();
        scrollToBottom();
    } catch (error) {
        console.error('加载讨论消息失败:', error);
        loading.value = false;
    }
};

// 发送消息
const sendMessage = async () => {
    if (!newMessage.value.trim()) return;

    try {
        const response = await axios.post(`/wiki/${props.pageId}/discussions`, {
            message: newMessage.value,
            editing_section: props.editingSection
        });

        if (response.data.success) {
            messages.value.push(response.data.message);
            newMessage.value = '';

            // 滚动到最新消息
            await nextTick();
            scrollToBottom();
        }
    } catch (error) {
        console.error('发送消息失败:', error);
        alert('发送消息失败，请重试');
    }
};

// 滚动到底部
const scrollToBottom = () => {
    if (messagesContainer.value) {
        messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
    }
};

// 定时轮询新消息
const startPolling = () => {
    pollingInterval.value = setInterval(async () => {
        try {
            const response = await axios.get(`/wiki/${props.pageId}/discussions`);

            // 对比新消息，只添加未加载的
            const newMessages = response.data.messages.filter(newMsg =>
                !messages.value.some(oldMsg => oldMsg.id === newMsg.id)
            );

            if (newMessages.length > 0) {
                messages.value = [...messages.value, ...newMessages];

                // 滚动到底部
                await nextTick();
                scrollToBottom();
            }
        } catch (error) {
            console.error('轮询新消息失败:', error);
        }
    }, 10000); // 每10秒轮询一次
};

// 监听编辑区域变化
watch(() => props.editingSection, (newSection) => {
    // 当编辑区域变化时，可以选择自动发送一条通知消息
    if (newSection && messages.value.length > 0) {
        const lastMessage = messages.value[messages.value.length - 1];
        // 如果最后一条消息不是当前用户发的关于相同区域的编辑通知，则发送新通知
        if (!(lastMessage.editing_section === newSection && lastMessage.message.includes('正在编辑'))) {
            // 自动发送编辑通知消息的逻辑可以根据需要添加
        }
    }
});

onMounted(() => {
    loadMessages();
    startPolling();
});

onUnmounted(() => {
    if (pollingInterval.value) {
        clearInterval(pollingInterval.value);
    }
});
</script>