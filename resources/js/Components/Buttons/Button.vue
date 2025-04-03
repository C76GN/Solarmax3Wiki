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
const props = defineProps({
    type: {
        type: String,
        default: 'button',
    },
    variant: {
        type: String,
        default: 'primary',
        validator: (value) => ['primary', 'secondary', 'danger', 'login'].includes(value)
    },
    fullWidth: {
        type: Boolean,
        default: false
    },
    className: {
        type: String,
        default: ''
    },
    size: {
        type: String,
        default: 'md',
        validator: (value) => ['sm', 'md', 'lg'].includes(value)
    },
});
/**
 * 根据variant属性计算对应的样式类
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

const sizeClasses = computed(() => {
    const sizes = {
        sm: 'px-2 py-1 text-xs',
        md: 'px-4 py-2 text-sm',
        lg: 'px-6 py-3 text-base'
    };
    return sizes[props.size] || sizes.md;
});

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