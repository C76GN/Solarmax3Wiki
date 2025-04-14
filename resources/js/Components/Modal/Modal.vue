<template>
    <Teleport to="body">
        <Transition leave-active-class="duration-200">
            <!-- Outer container -->
            <div v-show="show" class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 sm:px-0" :class="positionClass"
                scroll-region>
                <!-- Overlay -->
                <Transition enter-active-class="ease-out duration-300" enter-from-class="opacity-0"
                    enter-to-class="opacity-100" leave-active-class="ease-in duration-200"
                    leave-from-class="opacity-100" leave-to-class="opacity-0">
                    <div v-show="show" class="fixed inset-0 transform transition-all" @click="close">
                        <div class="absolute inset-0 bg-black opacity-75" />
                    </div>
                </Transition>

                <!-- Dialog Content -->
                <Transition enter-active-class="ease-out duration-300"
                    enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    enter-to-class="opacity-100 translate-y-0 sm:scale-100" leave-active-class="ease-in duration-200"
                    leave-from-class="opacity-100 translate-y-0 sm:scale-100"
                    leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
                    <div v-show="show"
                        class="dialog-content mb-6 transform overflow-hidden rounded-lg bg-white dark:bg-gray-800 shadow-xl transition-all sm:w-full sm:mx-auto"
                        :class="maxWidthClass" :style="customStyle">
                        <!-- Optional Title -->
                        <div v-if="title"
                            class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ title }}</h3>
                            <button v-if="closeable" @click="close"
                                class="text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300 transition">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20"
                                    fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                        clip-rule="evenodd" />
                                </svg>
                            </button>
                        </div>

                        <!-- Main Content Slot -->
                        <slot />

                        <!-- Optional Footer -->
                        <div v-if="showFooter"
                            class="px-6 py-4 bg-gray-50 dark:bg-gray-700 flex justify-end space-x-3 border-t border-gray-200 dark:border-gray-600 rounded-b-lg">
                            <button v-if="showCancel" @click="close"
                                class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2 transition text-sm font-medium dark:bg-gray-600 dark:text-gray-200 dark:hover:bg-gray-500 dark:focus:ring-offset-gray-700">
                                {{ cancelText }}
                            </button>
                            <button v-if="showConfirm" @click="confirm"
                                class="px-4 py-2 text-white rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 transition text-sm font-medium disabled:opacity-50"
                                :class="[
                                    dangerAction
                                        ? 'bg-red-600 hover:bg-red-700 focus:ring-red-500 dark:focus:ring-offset-gray-700'
                                        : 'bg-blue-600 hover:bg-blue-700 focus:ring-blue-500 dark:focus:ring-offset-gray-700'
                                ]" :disabled="confirmDisabled">
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
import { computed, onMounted, onUnmounted, watch } from 'vue';

const props = defineProps({
    show: { type: Boolean, default: false },
    maxWidth: { type: String, default: '2xl' }, // sm, md, lg, xl, 2xl, etc.
    closeable: { type: Boolean, default: true },
    position: { type: String, default: 'center' }, // center, top-center, bottom-center, etc.
    customPosition: { type: Object, default: null }, // { top: '10px', left: '10px' }
    title: { type: String, default: '' },
    showFooter: { type: Boolean, default: false },
    showCancel: { type: Boolean, default: true },
    showConfirm: { type: Boolean, default: true },
    cancelText: { type: String, default: '取消' },
    confirmText: { type: String, default: '确认' },
    dangerAction: { type: Boolean, default: false }, // Makes confirm button red
    confirmDisabled: { type: Boolean, default: false } // Allows disabling confirm button
});

const emit = defineEmits(['close', 'confirm']);

const close = () => {
    if (props.closeable && props.show) {
        emit('close');
    }
};

const confirm = () => {
    if (!props.confirmDisabled) {
        emit('confirm');
    }
};

const closeOnEscape = (e) => {
    if (e.key === 'Escape' && props.show) {
        close();
    }
};

onMounted(() => document.addEventListener('keydown', closeOnEscape));
onUnmounted(() => {
    document.removeEventListener('keydown', closeOnEscape);
    // Ensure overflow is restored if modal was open
    document.body.style.overflow = '';
});

// Watch 'show' prop to manage body overflow
watch(() => props.show, (show) => {
    if (typeof window !== 'undefined') {
        if (show) {
            document.body.style.overflow = 'hidden';
        } else {
            // Delay removing overflow hidden to allow closing animation
            setTimeout(() => {
                document.body.style.overflow = '';
            }, 200); // Match transition duration
        }
    }
}, { immediate: true });


// Tailwind classes for max width
const maxWidthClass = computed(() => {
    return {
        'sm': 'sm:max-w-sm',
        'md': 'sm:max-w-md',
        'lg': 'sm:max-w-lg',
        'xl': 'sm:max-w-xl',
        '2xl': 'sm:max-w-2xl',
        '3xl': 'sm:max-w-3xl',
        '4xl': 'sm:max-w-4xl',
        '5xl': 'sm:max-w-5xl',
        '6xl': 'sm:max-w-6xl',
        '7xl': 'sm:max-w-7xl',
    }[props.maxWidth] || 'sm:max-w-2xl'; // Default size
});

// Tailwind classes for positioning
const positionClass = computed(() => {
    if (props.customPosition) return ''; // Don't apply preset if custom is used
    return {
        'center': 'flex items-center justify-center',
        'top-center': 'flex items-start justify-center pt-10', // Add some top padding
        'bottom-center': 'flex items-end justify-center pb-10',
        'top-left': 'flex items-start justify-start pt-10 pl-10',
        'top-right': 'flex items-start justify-end pt-10 pr-10',
        'bottom-left': 'flex items-end justify-start pb-10 pl-10',
        'bottom-right': 'flex items-end justify-end pb-10 pr-10',
    }[props.position] || 'flex items-center justify-center'; // Default to center
});

// Style object for custom positioning
const customStyle = computed(() => {
    return props.customPosition ? { position: 'absolute', ...props.customPosition } : {};
});
</script>

<style scoped>
/* Keep existing transition styles if needed, or rely purely on Tailwind transitions */
.dialog-content {
    /* Base scale and transitions applied by Tailwind classes */
}

/* Optional: Keep media query scaling for very small screens if desired */
@media (max-width: 640px) {
    .dialog-content {
        /* Example: slightly smaller scale on small screens */
        /* transform: scale(0.9); You might not need this with overflow-y-auto */
    }
}
</style>