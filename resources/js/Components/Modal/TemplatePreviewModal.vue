<template>
    <Transition name="modal">
        <div v-if="isOpen" class="fixed inset-0 z-50 overflow-y-auto" role="dialog">
            <!-- 背景遮罩 -->
            <div class="fixed inset-0 bg-black/25" @click="closeModal"></div>

            <!-- 模态框内容 -->
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-3xl bg-white rounded-lg shadow-xl p-6">
                    <!-- 标题和关闭按钮 -->
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium text-gray-900">
                            预览模板：{{ template.name }}
                        </h3>
                        <button @click="closeModal" class="text-gray-400 hover:text-gray-500">
                            <span class="text-2xl">&times;</span>
                        </button>
                    </div>

                    <div class="mt-4">
                        <div class="text-sm text-gray-500 mb-4">{{ template.description }}</div>

                        <!-- 预览字段列表 -->
                        <div class="space-y-6">
                            <div v-for="field in template.fields" :key="field.name"
                                class="border border-gray-200 rounded-md p-4">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="font-medium text-gray-700">
                                        {{ field.name }}
                                        <span v-if="field.required" class="text-red-500">*</span>
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        类型：{{ getFieldTypeName(field.type) }}
                                    </div>
                                </div>

                                <!-- 字段预览 -->
                                <div class="mt-2">
                                    <!-- 文本输入 -->
                                    <input v-if="field.type === 'text'" type="text" disabled placeholder="文本输入示例"
                                        class="w-full px-3 py-2 bg-gray-50 border border-gray-300 rounded-md">

                                    <!-- 数字输入 -->
                                    <input v-else-if="field.type === 'number'" type="number" disabled placeholder="0"
                                        class="w-full px-3 py-2 bg-gray-50 border border-gray-300 rounded-md">

                                    <!-- 日期输入 -->
                                    <input v-else-if="field.type === 'date'" type="date" disabled
                                        class="w-full px-3 py-2 bg-gray-50 border border-gray-300 rounded-md">

                                    <!-- 布尔值输入 -->
                                    <div v-else-if="field.type === 'boolean'" class="flex items-center">
                                        <input type="checkbox" disabled
                                            class="rounded border-gray-300 text-blue-500 shadow-sm">
                                        <span class="ml-2 text-sm text-gray-600">是/否</span>
                                    </div>

                                    <!-- Markdown 编辑器 -->
                                    <textarea v-else-if="field.type === 'markdown'" disabled rows="3"
                                        placeholder="Markdown 内容"
                                        class="w-full px-3 py-2 bg-gray-50 border border-gray-300 rounded-md"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end">
                        <button @click="closeModal"
                            class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 transition duration-150 ease-in-out">
                            关闭预览
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Transition>
</template>

<script setup>
const props = defineProps({
    isOpen: {
        type: Boolean,
        required: true
    },
    template: {
        type: Object,
        required: true
    }
})

const emit = defineEmits(['close'])

const closeModal = () => {
    emit('close')
}

const getFieldTypeName = (type) => {
    const types = {
        text: '文本',
        number: '数字',
        boolean: '布尔值',
        date: '日期',
        markdown: 'Markdown'
    }
    return types[type] || type
}
</script>

<style scoped>
.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}

.modal-enter-to,
.modal-leave-from {
    opacity: 1;
}
</style>