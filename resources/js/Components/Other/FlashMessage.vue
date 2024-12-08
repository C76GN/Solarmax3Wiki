<template>
    <div class="fixed inset-x-0 bottom-0 z-50 space-y-4 pointer-events-none">
        <TransitionGroup enter-active-class="transition duration-300 ease-out"
            enter-from-class="transform translate-y-2 opacity-0" enter-to-class="transform translate-y-0 opacity-100"
            leave-active-class="transition duration-200 ease-in" leave-from-class="transform translate-y-0 opacity-100"
            leave-to-class="transform translate-y-2 opacity-0">
            <div v-for="message in messages" :key="message.id"
                class="flex items-center p-4 rounded-lg shadow-lg backdrop-blur-sm max-w-sm" :class="[
                    message.type === 'success' && 'bg-green-500/90 text-white',
                    message.type === 'error' && 'bg-red-500/90 text-white',
                    message.type === 'warning' && 'bg-yellow-500/90 text-white',
                    message.type === 'info' && 'bg-blue-500/90 text-white'
                ]">
                <!-- 图标 -->
                <div class="flex-shrink-0 mr-3">
                    <svg v-if="message.type === 'success'" class="w-5 h-5" xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                            clip-rule="evenodd" />
                    </svg>
                    <svg v-else-if="message.type === 'error'" class="w-5 h-5" xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                            clip-rule="evenodd" />
                    </svg>
                    <svg v-else-if="message.type === 'warning'" class="w-5 h-5" xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                            clip-rule="evenodd" />
                    </svg>
                    <svg v-else class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                        fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                            clip-rule="evenodd" />
                    </svg>
                </div>

                <!-- 消息文本 -->
                <div class="flex-1 mr-2">{{ message.text }}</div>

                <!-- 关闭按钮 -->
                <button @click="removeMessage(message.id)"
                    class="flex-shrink-0 text-white/80 hover:text-white transition duration-150">
                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                            clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
        </TransitionGroup>
    </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import { TransitionGroup } from 'vue'
import { usePage } from '@inertiajs/vue3'

const messages = ref([])
let nextId = 0

const addMessage = (type, text) => {
    const id = nextId++
    messages.value.push({ id, type, text })

    // 3秒后自动移除消息
    setTimeout(() => {
        removeMessage(id)
    }, 3000)
}

const removeMessage = (id) => {
    const index = messages.value.findIndex(m => m.id === id)
    if (index > -1) {
        messages.value.splice(index, 1)
    }
}

// 监听闪现消息
onMounted(() => {
    const page = usePage()

    watch(() => page.props.flash, (flash) => {
        if (flash && flash.message) {
            const { type = 'info', text } = flash.message
            if (text) {
                addMessage(type, text)
            }
        }
    }, { immediate: true, deep: true })
})

// 暴露方法供外部使用
defineExpose({
    addMessage
})
</script>