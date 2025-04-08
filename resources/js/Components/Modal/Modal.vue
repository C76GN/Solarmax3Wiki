<template>
    <Teleport to="body">
        <Transition leave-active-class="duration-200">
            <div v-show="show" class="fixed inset-0 z-50 px-4 py-6 sm:px-0" :class="positionClass">
                <Transition enter-active-class="ease-out duration-300" enter-from-class="opacity-0"
                    enter-to-class="opacity-100" leave-active-class="ease-in duration-200"
                    leave-from-class="opacity-100" leave-to-class="opacity-0">
                    <div v-show="show" class="fixed inset-0 transform transition-all" @click="close">
                        <div class="absolute inset-0 bg-black opacity-50" />
                    </div>
                </Transition>
                <Transition enter-active-class="ease-out duration-300"
                    enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    enter-to-class="opacity-100 translate-y-0 sm:scale-100" leave-active-class="ease-in duration-200"
                    leave-from-class="opacity-100 translate-y-0 sm:scale-100"
                    leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
                    <div v-show="show"
                        class="dialog-content mb-6 transform overflow-auto rounded-lg bg-white shadow-xl transition-all sm:w-full"
                        :class="maxWidthClass">
                        <div v-if="title" class="p-4 border-b flex justify-between items-center">
                            <h3 class="text-lg font-medium text-gray-900">{{ title }}</h3>
                            <button v-if="closeable" @click="close" class="text-gray-500 hover:text-gray-700">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20"
                                    fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                        clip-rule="evenodd" />
                                </svg>
                            </button>
                        </div>
                        <slot />
                        <div v-if="showFooter" class="px-6 py-4 bg-gray-50 flex justify-end space-x-3">
                            <button v-if="showCancel" @click="close"
                                class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">
                                {{ cancelText }}
                            </button>
                            <button v-if="showConfirm" @click="confirm" :class="[
                                'px-4 py-2 text-white rounded',
                                dangerAction ? 'bg-red-600 hover:bg-red-700' : 'bg-blue-600 hover:bg-blue-700'
                            ]">
                                {{ confirmText }}
                            </button>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
import { computed, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    maxWidth: {
        type: String,
        default: '2xl',
    },
    closeable: {
        type: Boolean,
        default: true,
    },
    position: {
        type: String,
        default: 'center',
    },
    customPosition: {
        type: Object,
        default: null,
    },
    title: {
        type: String,
        default: '',
    },
    showFooter: {
        type: Boolean,
        default: false,
    },
    showCancel: {
        type: Boolean,
        default: true,
    },
    showConfirm: {
        type: Boolean,
        default: true,
    },
    cancelText: {
        type: String,
        default: '取消',
    },
    confirmText: {
        type: String,
        default: '确认',
    },
    dangerAction: {
        type: Boolean,
        default: false,
    },
    message: {
        type: String,
        default: '',
    },
});

const emit = defineEmits(['close', 'confirm']);

const close = () => {
    if (props.closeable && props.show) {
        emit('close');
    }
};

const confirm = () => {
    emit('confirm');
};

const closeOnEscape = (e) => {
    if (e.key === 'Escape' && props.show) {
        close();
    }
};

onMounted(() => {
    document.addEventListener('keydown', closeOnEscape);
});

onUnmounted(() => {
    document.removeEventListener('keydown', closeOnEscape);
});

const maxWidthClass = computed(() => ({
    sm: 'sm:max-w-sm',
    md: 'sm:max-w-md',
    lg: 'sm:max-w-lg',
    xl: 'sm:max-w-xl',
    '2xl': 'sm:max-w-2xl',
}[props.maxWidth]));

const positionClass = computed(() => {
    if (props.customPosition) return ''; // 自定义位置时不使用预设位置的类
    const presetPositions = {
        center: 'flex items-center justify-center',
        'top-center': 'flex items-start justify-center',
        'bottom-center': 'flex items-end justify-center',
        'top-left': 'flex items-start justify-start',
        'top-right': 'flex items-start justify-end',
        'bottom-left': 'flex items-end justify-start',
        'bottom-right': 'flex items-end justify-end',
    };
    return presetPositions[props.position] || presetPositions.center;
});

const customStyle = computed(() => props.customPosition ? { position: 'absolute', ...props.customPosition } : {});
</script>

<style scoped>
.dialog-content {
    transform: scale(1);
    transition: transform 0.3s ease-out, opacity 0.3s ease-out;
}

@media (max-width: 640px) {
    .dialog-content {
        transform: scale(0.8);
    }
}

@media (max-width: 320px) {
    .dialog-content {
        transform: scale(0.7);
    }
}

@media (min-width: 640px) {
    .sm\:w-full {
        width: 100%;
    }
}
</style>