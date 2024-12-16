// FileName: /var/www/Solarmax3Wiki/resources/js/Components/Modal/Modal.vue
<template>
    <!-- Teleport将内容渲染到body，以确保弹窗在整个视图层级之上 -->
    <Teleport to="body">
        <!-- Transition 包裹整个弹窗，控制显示和隐藏时的动画效果 -->
        <Transition leave-active-class="duration-200">
            <div v-show="show" class="fixed inset-0 z-50 px-4 py-6 sm:px-0" :class="positionClass">
                <!-- 背景遮罩，点击背景触发关闭 -->
                <Transition enter-active-class="ease-out duration-300" enter-from-class="opacity-0"
                    enter-to-class="opacity-100" leave-active-class="ease-in duration-200"
                    leave-from-class="opacity-100" leave-to-class="opacity-0">
                    <div v-show="show" class="fixed inset-0 transform transition-all" @click="close">
                        <div class="absolute inset-0 bg-black opacity-50" />
                    </div>
                </Transition>

                <!-- 弹窗内容区域，含过渡效果 -->
                <Transition enter-active-class="ease-out duration-300"
                    enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    enter-to-class="opacity-100 translate-y-0 sm:scale-100" leave-active-class="ease-in duration-200"
                    leave-from-class="opacity-100 translate-y-0 sm:scale-100"
                    leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
                    <div v-show="show"
                        class="dialog-content mb-6 transform overflow-auto rounded-lg bg-cyan-900 shadow-xl transition-all sm:w-full"
                        :class="maxWidthClass">
                        <!-- 使用 slot 动态传入弹窗内容 -->
                        <slot v-if="show" />
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
import { computed, onMounted, onUnmounted } from 'vue';

// 接收父组件传入的 props，包括控制弹窗显示、宽度、是否可关闭、位置等
const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    maxWidth: {
        type: String,
        default: '2xl', // 默认最大宽度为 2xl
    },
    closeable: {
        type: Boolean,
        default: true, // 控制是否允许关闭弹窗
    },
    position: {
        type: String,
        default: 'center', // 默认居中显示
    },
    customPosition: {
        type: Object,  // 自定义位置，例如 { top: '10px', left: '20px' }
        default: null,
    },
});

// 定义触发事件，用于告诉父组件关闭弹窗
const emit = defineEmits(['close']);

// 定义关闭弹窗的函数
const close = () => {
    if (props.closeable && props.show) {
        emit('close');
    }
};

// 监听键盘的 Esc 按键，允许用户通过按下 Esc 关闭弹窗
const closeOnEscape = (e) => {
    if (e.key === 'Escape' && props.show) {
        close();
    }
};

// 组件挂载时添加键盘事件监听器，组件销毁时移除监听器
onMounted(() => {
    document.addEventListener('keydown', closeOnEscape);
});

onUnmounted(() => {
    document.removeEventListener('keydown', closeOnEscape);
});

// 动态计算弹窗的最大宽度类，根据传入的 maxWidth 值进行调整
const maxWidthClass = computed(() => ({
    sm: 'sm:max-w-sm',
    md: 'sm:max-w-md',
    lg: 'sm:max-w-lg',
    xl: 'sm:max-w-xl',
    '2xl': 'sm:max-w-2xl',
}[props.maxWidth]));

// 动态计算弹窗的定位类，根据传入的预设位置或自定义位置进行设置
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

// 当自定义位置时，使用 absolute 定位并应用自定义的 top 和 left
const customStyle = computed(() => props.customPosition ? { position: 'absolute', ...props.customPosition } : {});
</script>

<style scoped>
/* 默认情况下，弹窗不会缩放 */
.dialog-content {
    transform: scale(1);
    transition: transform 0.3s ease-out, opacity 0.3s ease-out;
}

/* 当屏幕宽度小于640px时，缩小弹窗到80% */
@media (max-width: 640px) {
    .dialog-content {
        transform: scale(0.8);
    }
}

/* 当屏幕宽度小于480px时，进一步缩小弹窗到70% */
@media (max-width: 320px) {
    .dialog-content {
        transform: scale(0.7);
    }
}

/* 允许弹窗根据屏幕大小做出响应式调整 */
@media (min-width: 640px) {
    .sm\:w-full {
        width: 100%;
    }
}
</style>
