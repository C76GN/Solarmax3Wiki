<template>
    <button :type="type" :class="[
        'relative inline-flex items-center overflow-hidden rounded-md border border-transparent justify-center font-medium uppercase tracking-widest transition duration-300 ease-in-out focus:outline-none active:scale-95',
        variantClasses,
        sizeClasses,
        fullWidth ? 'w-full' : '',
        className
    ]" @click="createRipple">
        <slot />
    </button>
</template>

<script setup>
import { computed } from 'vue';

/**
 * 按钮组件
 * 提供不同类型、大小和涟漪效果的通用按钮。
 */
const props = defineProps({
    /**
     * 按钮的HTML类型，如 'button', 'submit', 'reset'。
     */
    type: {
        type: String,
        default: 'button',
    },
    /**
     * 按钮的视觉样式变体。
     * 可选值: 'primary', 'secondary', 'danger', 'login', 'outline', 'success', 'info', 'text'。
     */
    variant: {
        type: String,
        default: 'primary',
        validator: (value) => ['primary', 'secondary', 'danger', 'login', 'outline', 'success', 'info', 'text'].includes(value)
    },
    /**
     * 按钮是否占据其父容器的全部宽度。
     */
    fullWidth: {
        type: Boolean,
        default: false
    },
    /**
     * 额外的CSS类名，用于自定义样式。
     */
    className: {
        type: String,
        default: ''
    },
    /**
     * 按钮的尺寸。
     * 可选值: 'sm' (小), 'md' (中), 'lg' (大)。
     */
    size: {
        type: String,
        default: 'md',
        validator: (value) => ['sm', 'md', 'lg'].includes(value)
    },
});

/**
 * 根据 `variant` 属性动态计算按钮的背景、文字和悬停样式。
 */
const variantClasses = computed(() => {
    const variants = {
        primary: 'bg-cyan-500 text-white hover:bg-cyan-600 hover:ring-2 hover:ring-teal-800 active:bg-cyan-700',
        secondary: 'bg-black text-white hover:bg-gray-900 hover:ring-2 hover:ring-black active:bg-gray-950',
        danger: 'bg-red-600 text-white hover:bg-red-500 hover:ring-2 hover:ring-rose-600 active:bg-red-700',
        login: 'bg-cyan-500 text-white hover:bg-cyan-600 hover:ring-2 hover:ring-teal-800 active:bg-cyan-700',
        outline: 'border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 shadow-sm',
        success: 'bg-green-600 text-white hover:bg-green-500 hover:ring-2 hover:ring-green-700 active:bg-green-700',
        info: 'bg-blue-500 text-white hover:bg-blue-600 hover:ring-2 hover:ring-blue-700 active:bg-blue-700',
        text: 'bg-transparent text-gray-700 hover:text-gray-900 hover:bg-gray-100'
    };
    return variants[props.variant] || variants.primary;
});

/**
 * 根据 `size` 属性动态计算按钮的内边距和字体大小。
 */
const sizeClasses = computed(() => {
    const sizes = {
        sm: 'px-2 py-1 text-xs',
        md: 'px-4 py-2 text-sm',
        lg: 'px-6 py-3 text-base'
    };
    return sizes[props.size] || sizes.md;
});

/**
 * 在按钮点击时创建并播放涟漪（ripple）动画效果。
 * @param {Event} event - 点击事件对象。
 */
const createRipple = (event) => {
    const button = event.currentTarget;
    const rect = button.getBoundingClientRect();
    const x = event.clientX - rect.left;
    const y = event.clientY - rect.top;
    const circle = document.createElement('span');
    circle.classList.add('circle');
    circle.style.top = `${y}px`;
    circle.style.left = `${x}px`;
    button.appendChild(circle);
    // 动画结束后移除涟漪元素
    circle.addEventListener('animationend', () => {
        circle.remove();
    });
};
</script>

<style scoped>
/* 定义涟漪效果的CSS */
:deep(.circle) {
    position: absolute;
    background-color: rgba(255, 255, 255, 0.5);
    width: 100px;
    height: 100px;
    border-radius: 50%;
    transform: translate(-50%, -50%) scale(0);
    animation: ripple-scale 0.5s ease-out;
    pointer-events: none;
    /* 确保涟漪不影响鼠标事件 */
}

/* 涟漪扩散动画 */
@keyframes ripple-scale {
    to {
        transform: translate(-50%, -50%) scale(3);
        opacity: 0;
    }
}
</style>