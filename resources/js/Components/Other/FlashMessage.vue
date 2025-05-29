<template>
    <!-- 消息通知容器，固定在页面右上角 -->
    <div class="fixed top-6 right-6 z-[100] w-full max-w-sm space-y-4 pointer-events-none">
        <!-- 动画过渡组，用于消息的进入/离开动画 -->
        <TransitionGroup enter-active-class="transition duration-300 ease-out"
            enter-from-class="transform translate-x-full opacity-0" enter-to-class="transform translate-x-0 opacity-100"
            leave-active-class="transition duration-200 ease-in" leave-from-class="transform translate-x-0 opacity-100"
            leave-to-class="transform translate-x-full opacity-0">
            <!-- 遍历并渲染每个消息 -->
            <div v-for="message in messages" :key="message.id"
                class="flex items-center p-4 rounded-lg shadow-lg backdrop-blur-sm pointer-events-auto w-full" :class="[
                    // 根据消息类型动态绑定背景色和文字颜色
                    message.type === 'success' && 'bg-green-500/90 text-white',
                    message.type === 'error' && 'bg-red-500/90 text-white',
                    message.type === 'warning' && 'bg-yellow-500/90 text-black',
                    message.type === 'info' && 'bg-blue-500/90 text-white'
                ]">
                <!-- 消息图标部分 -->
                <div class="flex-shrink-0 mr-3">
                    <font-awesome-icon v-if="message.type === 'success'" :icon="['fas', 'check-circle']"
                        class="w-5 h-5" />
                    <font-awesome-icon v-else-if="message.type === 'error'" :icon="['fas', 'exclamation-circle']"
                        class="w-5 h-5" />
                    <font-awesome-icon v-else-if="message.type === 'warning'" :icon="['fas', 'exclamation-triangle']"
                        class="w-5 h-5" />
                    <font-awesome-icon v-else :icon="['fas', 'info-circle']" class="w-5 h-5" />
                </div>
                <!-- 消息文本内容 -->
                <div class="flex-1 text-sm font-medium mr-2">{{ message.text }}</div>
                <!-- 关闭消息按钮 -->
                <button @click="removeMessage(message.id)" class="flex-shrink-0 ml-auto p-1 rounded-full -mr-1 -my-1"
                    :class="[
                        // 根据消息类型调整关闭按钮的颜色，确保对比度
                        message.type === 'warning' ? 'text-black/70 hover:text-black hover:bg-black/10' : 'text-white/80 hover:text-white hover:bg-white/20'
                    ]" aria-label="Close">
                    <font-awesome-icon :icon="['fas', 'times']" class="w-4 h-4" />
                </button>
            </div>
        </TransitionGroup>
    </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
import { TransitionGroup } from 'vue';
import { usePage, router } from '@inertiajs/vue3';

// 存储当前显示的消息列表
const messages = ref([]);
// 用于生成唯一消息ID
let nextId = 0;

/**
 * 添加一条消息到通知列表
 * @param {string} type - 消息类型 ('success', 'error', 'warning', 'info')
 * @param {string} text - 消息文本内容
 * @param {number} [duration=5000] - 消息自动消失的毫秒数，默认为5秒
 */
const addMessage = (type, text, duration = 5000) => {
    const id = nextId++;
    messages.value.push({ id, type, text });
    // 设置定时器，在指定时间后自动移除消息
    setTimeout(() => {
        removeMessage(id);
    }, duration);
};

/**
 * 从通知列表中移除指定ID的消息
 * @param {number} id - 要移除的消息ID
 */
const removeMessage = (id) => {
    const index = messages.value.findIndex(m => m.id === id);
    if (index > -1) {
        messages.value.splice(index, 1);
    }
};

// 组件挂载后执行
onMounted(() => {
    const page = usePage();

    // 监听 Inertia 的 flash props，处理后端发送的一次性消息
    watch(() => page.props.flash, (flash) => {
        if (flash && flash.message) {
            const { type = 'info', text } = flash.message;
            if (text) {
                addMessage(type, text);
            }
            // 清空 flash 数据，防止消息在页面重新渲染后重复显示
            if (router && router.page && router.page.props.flash) {
                router.page.props.flash = {};
            }
        }
    }, { immediate: true, deep: true }); // immediate: true 确保在组件挂载时立即运行一次监听

    // 监听 Inertia 的 errors props，通常用于处理后端验证错误
    watch(() => page.props.errors, (errors) => {
        // 如果存在 'general' 错误，则将其作为错误消息显示
        if (errors && errors.general) {
            addMessage('error', errors.general);
            // 尝试清除 'general' 错误，避免重复显示
            if (router && router.page && router.page.props.errors) {
                const newErrors = { ...router.page.props.errors };
                delete newErrors.general; // 移除 general 错误
                router.page.props.errors = newErrors;
            }
        }
    }, { deep: true });
});

// 使用 defineExpose 暴露 addMessage 方法，使父组件可以通过模板引用调用它
defineExpose({
    addMessage
});
</script>