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
                    <button @click="closeModal"
                        class="text-gray-400 hover:text-gray-500 bg-transparent border-0 focus:outline-none">
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
                        <Button variant="outline" @click="closeModal">
                            取消
                        </Button>
                        <Button variant="primary" type="submit" :disabled="!hasFile">
                            导入
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import Button from '@/Components/Buttons/Button.vue'
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