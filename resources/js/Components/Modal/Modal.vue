<template>
    <!-- 使用 Teleport 将模态框渲染到 body 元素下，确保其在最顶层 -->
    <Teleport to="body">
        <!-- 模态框整体过渡效果 -->
        <Transition leave-active-class="duration-200">
            <!-- 模态框外部容器：固定定位，覆盖整个视口，控制滚动 -->
            <div v-show="show" class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 sm:px-0" :class="positionClass"
                scroll-region>
                <!-- 半透明背景遮罩 -->
                <Transition enter-active-class="ease-out duration-300" enter-from-class="opacity-0"
                    enter-to-class="opacity-100" leave-active-class="ease-in duration-200"
                    leave-from-class="opacity-100" leave-to-class="opacity-0">
                    <!-- 点击遮罩关闭模态框 -->
                    <div v-show="show" class="fixed inset-0 transform transition-all" @click="close">
                        <div class="absolute inset-0 bg-black opacity-75" />
                    </div>
                </Transition>

                <!-- 模态框内容区域 -->
                <Transition enter-active-class="ease-out duration-300"
                    enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    enter-to-class="opacity-100 translate-y-0 sm:scale-100" leave-active-class="ease-in duration-200"
                    leave-from-class="opacity-100 translate-y-0 sm:scale-100"
                    leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
                    <div v-show="show"
                        class="dialog-content mb-6 transform overflow-hidden rounded-lg bg-white dark:bg-gray-800 shadow-xl transition-all sm:w-full sm:mx-auto"
                        :class="maxWidthClass" :style="customStyle">
                        <!-- 模态框标题区域（可选） -->
                        <div v-if="title"
                            class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ title }}</h3>
                            <!-- 关闭按钮（可选） -->
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

                        <!-- 模态框主要内容插槽 -->
                        <slot />

                        <!-- 模态框底部（可选） -->
                        <div v-if="showFooter"
                            class="px-6 py-4 bg-gray-50 dark:bg-gray-700 flex justify-end space-x-3 border-t border-gray-200 dark:border-gray-600 rounded-b-lg">
                            <!-- 取消按钮（可选） -->
                            <button v-if="showCancel" @click="close"
                                class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2 transition text-sm font-medium dark:bg-gray-600 dark:text-gray-200 dark:hover:bg-gray-500 dark:focus:ring-offset-gray-700">
                                {{ cancelText }}
                            </button>
                            <!-- 确认按钮（可选），根据 dangerAction 和 confirmDisabled 动态样式和禁用状态 -->
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

// 定义组件的 props
const props = defineProps({
    show: { type: Boolean, default: false }, // 控制模态框是否显示
    maxWidth: { type: String, default: '2xl' }, // 模态框的最大宽度
    closeable: { type: Boolean, default: true }, // 是否可以通过点击外部或按 Esc 键关闭
    position: { type: String, default: 'center' }, // 预设的定位方式
    customPosition: { type: Object, default: null }, // 自定义 CSS 定位，如 { top: '10px', left: '10px' }
    title: { type: String, default: '' }, // 模态框标题
    showFooter: { type: Boolean, default: false }, // 是否显示底部按钮区域
    showCancel: { type: Boolean, default: true }, // 是否显示取消按钮
    showConfirm: { type: Boolean, default: true }, // 是否显示确认按钮
    cancelText: { type: String, default: '取消' }, // 取消按钮的文本
    confirmText: { type: String, default: '确认' }, // 确认按钮的文本
    dangerAction: { type: Boolean, default: false }, // 确认按钮是否显示为危险（红色）样式
    confirmDisabled: { type: Boolean, default: false } // 确认按钮是否禁用
});

// 定义组件可以发出的事件
const emit = defineEmits(['close', 'confirm']);

// 关闭模态框的方法
const close = () => {
    if (props.closeable && props.show) {
        emit('close');
    }
};

// 确认操作的方法
const confirm = () => {
    if (!props.confirmDisabled) {
        emit('confirm');
    }
};

// 监听键盘 Esc 键，用于关闭模态框
const closeOnEscape = (e) => {
    if (e.key === 'Escape' && props.show) {
        close();
    }
};

// 组件挂载时添加事件监听器
onMounted(() => document.addEventListener('keydown', closeOnEscape));
// 组件卸载时移除事件监听器并恢复 body 溢出样式
onUnmounted(() => {
    document.removeEventListener('keydown', closeOnEscape);
    document.body.style.overflow = '';
});

// 监听 `show` prop 的变化，控制 `body` 的滚动行为
watch(() => props.show, (show) => {
    if (typeof window !== 'undefined') {
        if (show) {
            document.body.style.overflow = 'hidden'; // 显示时禁用 body 滚动
        } else {
            // 延迟恢复 body 滚动，以允许模态框关闭动画完成
            setTimeout(() => {
                document.body.style.overflow = '';
            }, 200); // 过渡时长与 CSS 保持一致
        }
    }
}, { immediate: true }); // 立即执行一次监听

// 根据 maxWidth prop 计算 Tailwind CSS 宽度类
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
    }[props.maxWidth] || 'sm:max-w-2xl'; // 默认尺寸为 2xl
});

// 根据 position prop 计算 Tailwind CSS 定位类
const positionClass = computed(() => {
    if (props.customPosition) return ''; // 如果有自定义定位，则不应用预设定位
    return {
        'center': 'flex items-center justify-center',
        'top-center': 'flex items-start justify-center pt-10', // 顶部居中，带内边距
        'bottom-center': 'flex items-end justify-center pb-10', // 底部居中，带内边距
        'top-left': 'flex items-start justify-start pt-10 pl-10',
        'top-right': 'flex items-start justify-end pt-10 pr-10',
        'bottom-left': 'flex items-end justify-start pb-10 pl-10',
        'bottom-right': 'flex items-end justify-end pb-10 pr-10',
    }[props.position] || 'flex items-center justify-center'; // 默认居中
});

// 根据 customPosition prop 返回自定义样式对象
const customStyle = computed(() => {
    return props.customPosition ? { position: 'absolute', ...props.customPosition } : {};
});
</script>

<style scoped>
/* 模态框内容的基本样式，过渡效果由 Tailwind 类提供 */
.dialog-content {
    /* 例如，如果需要额外的非 Tailwind 属性，可以在这里添加 */
}

/* 针对小屏幕设备的响应式调整（可选，根据设计需求保留或移除） */
@media (max-width: 640px) {
    .dialog-content {
        /* 例如，可以在小屏幕上调整一些样式 */
    }
}
</style>