<template>
    <Modal :show="show" @close="$emit('close')">
        <div class="p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">
                {{ title }}
            </h3>
            <p class="text-sm text-gray-500 mb-4">
                {{ message }}
            </p>
            <div class="mt-5 flex justify-end gap-4">
                <button type="button" @click="$emit('close')"
                    class="px-4 py-2 text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200">
                    {{ cancelText }}
                </button>
                <button type="button" @click="$emit('confirm')" :class="[
                    'px-4 py-2 text-white rounded-md',
                    dangerAction ? 'bg-red-600 hover:bg-red-700' : 'bg-blue-500 hover:bg-blue-600'
                ]">
                    {{ confirmText }}
                </button>
            </div>
        </div>
    </Modal>
</template>

<script setup>
import Modal from '@/Components/Modal/Modal.vue';

defineProps({
    show: {
        type: Boolean,
        required: true
    },
    title: {
        type: String,
        default: '确认操作'
    },
    message: {
        type: String,
        required: true
    },
    confirmText: {
        type: String,
        default: '确认'
    },
    cancelText: {
        type: String,
        default: '取消'
    },
    dangerAction: {
        type: Boolean,
        default: false
    }
});

defineEmits(['close', 'confirm']);
</script>