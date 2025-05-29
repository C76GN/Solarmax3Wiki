<template>
    <!-- 使用 Teleport 将模态框渲染到 body 元素下，确保其在最顶层，避免Z轴层级问题 -->
    <Teleport to="body">
        <!-- 模态框整体过渡效果，当模态框显示/隐藏时应用 -->
        <Transition leave-active-class="duration-200">
            <!-- 模态框外部容器：固定定位，覆盖整个视口，控制内部滚动。z-50确保其位于页面最上层。 -->
            <div v-show="show" class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 sm:px-0" :class="positionClass"
                scroll-region>
                <!-- 半透明背景遮罩层，点击此层可关闭模态框 -->
                <Transition enter-active-class="ease-out duration-300" enter-from-class="opacity-0"
                    enter-to-class="opacity-100" leave-active-class="ease-in duration-200"
                    leave-from-class="opacity-100" leave-to-class="opacity-0">
                    <!-- 点击遮罩关闭模态框 -->
                    <div v-show="show" class="fixed inset-0 transform transition-all" @click="close">
                        <div class="absolute inset-0 bg-black opacity-75" />
                    </div>
                </Transition>

                <!-- 模态框内容区域，包含进入/离开过渡效果 -->
                <Transition enter-active-class="ease-out duration-300"
                    enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    enter-to-class="opacity-100 translate-y-0 sm:scale-100" leave-active-class="ease-in duration-200"
                    leave-from-class="opacity-100 translate-y-0 sm:scale-100"
                    leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
                    <div v-show="show"
                        class="dialog-content mb-6 transform overflow-hidden rounded-lg bg-white dark:bg-gray-800 shadow-xl transition-all sm:w-full sm:mx-auto"
                        :class="maxWidthClass" :style="customStyle">
                        <!-- 模态框标题区域，根据 title prop 动态显示 -->
                        <div v-if="title"
                            class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ title }}</h3>
                            <!-- 关闭按钮，根据 closeable prop 动态显示 -->
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

                        <!-- 模态框主要内容插槽，用于插入自定义内容 -->
                        <slot />

                        <!-- 模态框底部操作区域，根据 showFooter prop 动态显示 -->
                        <div v-if="showFooter"
                            class="px-6 py-4 bg-gray-50 dark:bg-gray-700 flex justify-end space-x-3 border-t border-gray-200 dark:border-gray-600 rounded-b-lg">
                            <!-- 取消按钮，根据 showCancel prop 动态显示 -->
                            <button v-if="showCancel" @click="close"
                                class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2 transition text-sm font-medium dark:bg-gray-600 dark:text-gray-200 dark:hover:bg-gray-500 dark:focus:ring-offset-gray-700">
                                {{ cancelText }}
                            </button>
                            <!-- 确认按钮，根据 showConfirm, dangerAction 和 confirmDisabled props 动态样式和禁用状态 -->
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

// 定义组件的属性，用于配置模态框的行为和外观
const props = defineProps({
    show: { type: Boolean, default: false }, // 控制模态框的显示与隐藏
    maxWidth: { type: String, default: '2xl' }, // 模态框的最大宽度，使用 Tailwind CSS 尺寸约定
    closeable: { type: Boolean, default: true }, // 是否允许通过点击模态框外部区域或按 Esc 键关闭
    position: { type: String, default: 'center' }, // 预设的模态框在视口中的定位方式
    customPosition: { type: Object, default: null }, // 允许传入自定义的 CSS 定位样式对象（如 { top: '10px', left: '10px' }）
    title: { type: String, default: '' }, // 模态框的标题文本
    showFooter: { type: Boolean, default: false }, // 是否显示模态框底部的操作按钮区域
    showCancel: { type: Boolean, default: true }, // 是否显示取消按钮
    showConfirm: { type: Boolean, default: true }, // 是否显示确认按钮
    cancelText: { type: String, default: '取消' }, // 取消按钮的文本内容
    confirmText: { type: String, default: '确认' }, // 确认按钮的文本内容
    dangerAction: { type: Boolean, default: false }, // 确认按钮是否应呈现为危险（红色）样式
    confirmDisabled: { type: Boolean, default: false } // 确认按钮是否应被禁用
});

// 定义组件可以触发的事件，用于与父组件通信
const emit = defineEmits(['close', 'confirm']);

// 关闭模态框的方法
const close = () => {
    // 只有当模态框可关闭且当前处于显示状态时，才触发 'close' 事件
    if (props.closeable && props.show) {
        emit('close');
    }
};

// 执行确认操作的方法
const confirm = () => {
    // 只有当确认按钮未被禁用时，才触发 'confirm' 事件
    if (!props.confirmDisabled) {
        emit('confirm');
    }
};

// 监听键盘 Esc 键按下事件，用于关闭模态框
const closeOnEscape = (e) => {
    // 如果当前模态框显示且按下了 Esc 键，则调用 close 方法
    if (e.key === 'Escape' && props.show) {
        close();
    }
};

// 组件挂载时，添加 Esc 键的事件监听器
onMounted(() => document.addEventListener('keydown', closeOnEscape));
// 组件卸载时，移除 Esc 键的事件监听器，并恢复 body 的 overflow 样式
onUnmounted(() => {
    document.removeEventListener('keydown', closeOnEscape);
    document.body.style.overflow = '';
});

// 监听 `show` prop 的变化，以控制 `body` 元素的滚动行为
watch(() => props.show, (show) => {
    // 确保在浏览器环境中执行（如服务端渲染时不执行）
    if (typeof window !== 'undefined') {
        if (show) {
            document.body.style.overflow = 'hidden'; // 模态框显示时，禁止 body 滚动
        } else {
            // 模态框隐藏后，延迟恢复 body 滚动，以确保关闭动画完成
            setTimeout(() => {
                document.body.style.overflow = '';
            }, 200); // 这里的延迟时间应与 CSS 过渡的 duration 保持一致
        }
    }
}, { immediate: true }); // 立即执行一次此 watch 函数，以在组件初次渲染时设置正确的 body 样式

// 计算模态框的最大宽度类，基于 maxWidth prop
const maxWidthClass = computed(() => {
    // 根据 props.maxWidth 的值返回对应的 Tailwind CSS 宽度类
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
    }[props.maxWidth] || 'sm:max-w-2xl'; // 如果未匹配到，则默认为 'sm:max-w-2xl'
});

// 计算模态框的定位类，基于 position prop
const positionClass = computed(() => {
    if (props.customPosition) return ''; // 如果设置了 customPosition，则不应用预设的定位类
    // 根据 props.position 的值返回对应的 Tailwind CSS flex 布局类，实现预设定位
    return {
        'center': 'flex items-center justify-center', // 垂直和水平居中
        'top-center': 'flex items-start justify-center pt-10', // 顶部居中，带顶部内边距
        'bottom-center': 'flex items-end justify-center pb-10', // 底部居中，带底部内边距
        'top-left': 'flex items-start justify-start pt-10 pl-10', // 左上角，带内边距
        'top-right': 'flex items-start justify-end pt-10 pr-10', // 右上角，带内边距
        'bottom-left': 'flex items-end justify-start pb-10 pl-10', // 左下角，带内边距
        'bottom-right': 'flex items-end justify-end pb-10 pr-10', // 右下角，带内边距
    }[props.position] || 'flex items-center justify-center'; // 默认居中
});

// 计算自定义样式对象，如果设置了 customPosition prop
const customStyle = computed(() => {
    // 如果 props.customPosition 存在，则返回一个包含 'position: absolute' 和所有 customPosition 属性的对象
    return props.customPosition ? { position: 'absolute', ...props.customPosition } : {};
});
</script>

<style scoped>
/* 此处可以添加组件特有的、非 Tailwind CSS 的样式规则 */
.dialog-content {
    /* 可以在这里添加一些通用的非 Tailwind CSS 属性，例如： */
    /* min-height: 100px; */
}

/* 媒体查询示例：针对小屏幕设备进行样式调整 */
@media (max-width: 640px) {
    .dialog-content {
        /* 在小屏幕上可能需要调整某些样式，例如让模态框占据更多宽度 */
        /* width: 95%; */
    }
}
</style>