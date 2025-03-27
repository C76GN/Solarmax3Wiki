<template>
    <button :type="type" :class="[
        'relative mt-4 inline-flex items-center overflow-hidden rounded-md border border-transparent justify-center px-4 py-2 text-sm font-medium uppercase tracking-widest transition duration-300 ease-in-out focus:outline-none active:scale-95',
        variantClasses,
        fullWidth ? 'w-full' : '',
        className
    ]" @click="createRipple">
        <slot />
    </button>
</template>

<script setup>
import { computed } from 'vue';

/**
 * BaseButton 组件
 * 
 * 基础按钮组件，支持多种样式变体、全宽选项和涟漪效果
 */
const props = defineProps({
    /**
     * 按钮类型
     * @type {'button'|'submit'|'reset'}
     */
    type: {
        type: String,
        default: 'button',
    },
    /**
     * 按钮样式变体
     * @type {'primary'|'secondary'|'danger'|'login'}
     */
    variant: {
        type: String,
        default: 'primary',
        validator: (value) => ['primary', 'secondary', 'danger', 'login'].includes(value)
    },
    /**
     * 是否全宽显示
     */
    fullWidth: {
        type: Boolean,
        default: false
    },
    /**
     * 额外的自定义样式类
     */
    className: {
        type: String,
        default: ''
    }
});

/**
 * 根据variant属性计算对应的样式类
 */
const variantClasses = computed(() => {
    const variants = {
        primary: 'bg-cyan-500 text-white hover:bg-cyan-600 hover:ring-2 hover:ring-teal-800 active:bg-cyan-700',
        secondary: 'bg-black text-white hover:bg-gray-900 hover:ring-2 hover:ring-black active:bg-gray-950',
        danger: 'bg-red-600 text-white hover:bg-red-500 hover:ring-2 hover:ring-rose-600 active:bg-red-700',
        login: 'bg-cyan-500 text-white hover:bg-cyan-600 hover:ring-2 hover:ring-teal-800 active:bg-cyan-700'
    };

    return variants[props.variant] || variants.primary;
});

/**
 * 创建点击涟漪效果
 * @param {MouseEvent} event - 鼠标点击事件
 */
const createRipple = (event) => {
    const button = event.currentTarget;

    // 计算点击位置相对于按钮的坐标
    const rect = button.getBoundingClientRect();
    const x = event.clientX - rect.left;
    const y = event.clientY - rect.top;

    // 创建涟漪元素
    const circle = document.createElement('span');
    circle.classList.add('circle');
    circle.style.top = `${y}px`;
    circle.style.left = `${x}px`;

    // 添加到按钮中
    button.appendChild(circle);

    // 动画结束后移除元素
    circle.addEventListener('animationend', () => {
        circle.remove();
    });
};
</script>

<style scoped>
:deep(.circle) {
    position: absolute;
    background-color: rgba(255, 255, 255, 0.5);
    width: 100px;
    height: 100px;
    border-radius: 50%;
    transform: translate(-50%, -50%) scale(0);
    animation: ripple-scale 0.5s ease-out;
    pointer-events: none;
}

@keyframes ripple-scale {
    to {
        transform: translate(-50%, -50%) scale(3);
        opacity: 0;
    }
}
</style>