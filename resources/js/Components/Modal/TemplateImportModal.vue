<template>
    <div v-if="isOpen" class="fixed inset-0 z-50 overflow-y-auto">
        <!-- 背景遮罩 -->
        <div class="fixed inset-0 bg-black/25" @click="closeModal"></div>

        <!-- 模态框内容 -->
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative w-full max-w-lg bg-white rounded-lg shadow-xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900">
                        导入模板
                    </h3>
                    <button @click="closeModal" class="text-gray-400 hover:text-gray-500">
                        <span class="text-2xl">&times;</span>
                    </button>
                </div>

                <form @submit.prevent="submitForm">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            选择模板文件
                        </label>
                        <input type="file" ref="fileInput" @change="handleFileChange" accept=".json" class="block w-full text-sm text-gray-500
                                file:mr-4 file:py-2 file:px-4
                                file:rounded-md file:border-0
                                file:text-sm file:font-semibold
                                file:bg-blue-50 file:text-blue-700
                                hover:file:bg-blue-100">
                        <p class="mt-1 text-sm text-gray-500">
                            请选择JSON格式的模板文件
                        </p>
                    </div>

                    <div class="flex justify-end gap-4">
                        <button type="button" @click="closeModal"
                            class="px-4 py-2 text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200">
                            取消
                        </button>
                        <button type="submit" :disabled="!hasFile"
                            class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 disabled:opacity-50">
                            导入
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import { useForm } from '@inertiajs/vue3'

const props = defineProps({
    isOpen: Boolean
})

const emit = defineEmits(['close'])
const fileInput = ref(null)
const hasFile = ref(false)

const form = useForm({
    template_file: null
})

const handleFileChange = (e) => {
    const file = e.target.files[0]
    if (file) {
        form.template_file = file
        hasFile.value = true
    }
}

const submitForm = () => {
    form.post(route('templates.import'), {
        preserveScroll: true,
        onSuccess: () => {
            closeModal()
        }
    })
}

const closeModal = () => {
    form.reset()
    hasFile.value = false
    if (fileInput.value) {
        fileInput.value.value = ''
    }
    emit('close')
}
</script>